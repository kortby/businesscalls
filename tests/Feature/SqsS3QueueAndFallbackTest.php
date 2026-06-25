<?php

use App\Jobs\EvaluateVoiceQualityJob;
use App\Models\AuditLog;
use App\Models\CallLog;
use App\Models\Tenant;
use App\Queue\SqsS3Job;
use App\Queue\SqsS3Queue;
use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Twilio\Security\RequestValidator;

// 1. SQS S3 Offloader Tests
test('large queue job is written to S3 and pointer is pushed to queue', function () {
    Storage::fake('s3');

    // Mock SQS client
    $sqs = Mockery::mock(SqsClient::class);
    $sqs->shouldReceive('sendMessage')
        ->once()
        ->with(Mockery::on(function ($argument) {
            $body = json_decode($argument['MessageBody'], true);

            return is_array($body) && ($body['is_offloaded'] ?? false) === true && isset($body['s3_key']);
        }))
        ->andReturn(new class
        {
            public function get($key)
            {
                return 'mocked-message-id';
            }
        });

    $queue = new SqsS3Queue($sqs, 'default');
    $queue->setContainer(Container::getInstance());

    // Generate massive payload (1MB)
    $payload = str_repeat('a', 1048576);

    $messageId = $queue->pushRaw($payload);

    expect($messageId)->toBe('mocked-message-id');

    // Assert file exists on S3
    $files = Storage::disk('s3')->allFiles('sqs-offload');
    expect($files)->toHaveCount(1);

    // Assert S3 content is the original massive payload
    $s3Content = Storage::disk('s3')->get($files[0]);
    expect($s3Content)->toBe($payload);
});

test('sqs s3 job retrieves original payload from S3 and deletes it on complete', function () {
    Storage::fake('s3');

    $path = 'sqs-offload/test-uuid.json';
    $originalPayload = '{"job":"MyJob","data":{"text":"large"}}';
    Storage::disk('s3')->put($path, $originalPayload);

    $sqs = Mockery::mock(SqsClient::class);

    // SqsS3Job inherits parent constructor which expects raw SQS message array
    $rawJob = [
        'MessageId' => 'test-msg-id',
        'ReceiptHandle' => 'receipt-handle-123',
        'Body' => json_encode([
            'is_offloaded' => true,
            's3_key' => $path,
        ]),
        'Attributes' => [
            'ApproximateReceiveCount' => 1,
        ],
    ];

    $job = new SqsS3Job(Container::getInstance(), $sqs, $rawJob, 'sqs_s3', 'default');

    // Assert transparent retrieval
    expect($job->getRawBody())->toBe($originalPayload);

    // Delete job
    $sqs->shouldReceive('deleteMessage')->once();
    $job->delete();

    // Assert S3 file is deleted
    Storage::disk('s3')->assertMissing($path);
});

// 2. Fallback Controller TwiML & Signature Verification Tests
test('telephony fallback route verification fails without signature', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'emergency_phone' => '+15559998888',
        ],
    ]);

    $response = $this->post(route('telephony.fallback-route', ['tenant_id' => $tenant->id]), [
        'From' => '+15551112222',
        'To' => '+15553334444',
    ]);

    $response->assertStatus(403);
    $response->assertSee('Signature verification failed');

    // Assert AuditLog created for failure
    $log = AuditLog::where('action', 'signature_verification_failed')->first();
    expect($log)->not->toBeNull();
    expect($log->tenant_id)->toBe($tenant->id);
});

test('telephony fallback route succeeds with valid signature', function () {
    $token = 'test-twilio-auth-token';

    $tenant = Tenant::factory()->create([
        'settings' => [
            'twilio_auth_token' => $token,
            'emergency_phone' => '+15557778888',
            'fallback_greeting' => 'Test emergency routing active.',
            'fallback_audio_url' => 'https://example.com/audio.mp3',
        ],
    ]);

    $url = route('telephony.fallback-route', ['tenant_id' => $tenant->id]);
    $params = [
        'From' => '+15551112222',
        'To' => '+15553334444',
    ];

    // Compute valid Twilio signature for test request
    $validator = new RequestValidator($token);
    $signature = $validator->computeSignature($url, $params);

    $response = $this->withHeaders([
        'X-Twilio-Signature' => $signature,
    ])->post($url, $params);

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

    // Assert TwiML contains dial, say, play
    $response->assertSee('<Say>Test emergency routing active.</Say>', false);
    $response->assertSee('<Play>https://example.com/audio.mp3</Play>', false);
    $response->assertSee('<Dial>+15557778888</Dial>', false);

    // Assert AuditLog created for routing
    $log = AuditLog::where('action', 'telephony_fallback_routed')->first();
    expect($log)->not->toBeNull();
    expect($log->tenant_id)->toBe($tenant->id);
    expect($log->payload['emergency_phone'])->toBe('+15557778888');
});

// 3. MOS Evaluation Job Tests
test('evaluate voice quality job calculates MOS score correctly', function () {
    $tenant = Tenant::factory()->create();

    $callLog = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-uuid-123',
        'status' => 'ended',
        'customer_phone' => '+15552223333',
        'latency' => 300, // L_tts (TTS delay)
        'acoustic_intelligibility' => 0.90, // Θ_intelligibility
        'vocal_inflection_variance' => 0.80, // Φ_emotion
    ]);

    // Set config weights
    Config::set('telephony.mos_weights.alpha', 0.4);
    Config::set('telephony.mos_weights.beta', 0.4); // beta = 0.4, gamma = 0.2
    Config::set('telephony.mos_weights.gamma', 0.2);

    $job = new EvaluateVoiceQualityJob($callLog);
    $job->handle();

    // Verify database record has updated MOS score
    $callLog->refresh();

    // L_tts = 300 -> latencyTerm = 1 - 300/1500 = 1 - 0.2 = 0.8
    // MOS = 0.4 * 0.90 + 0.4 * 0.8 + 0.2 * 0.80 = 0.36 + 0.32 + 0.16 = 0.84
    expect($callLog->mos_score)->toBe(0.84);
});
