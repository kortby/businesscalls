<?php

namespace App\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SqsS3Queue extends SqsQueue
{
    /**
     * The maximum SQS payload size in bytes before offloading to S3 (1 MB).
     */
    protected const MAX_PAYLOAD_SIZE = 1048576;

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if (strlen($payload) >= self::MAX_PAYLOAD_SIZE) {
            $path = 'sqs-offload/'.Str::uuid().'.json';

            Storage::disk('s3')->put($path, $payload);

            $payload = json_encode([
                'is_offloaded' => true,
                's3_key' => $path,
            ]);
        }

        return parent::pushRaw($payload, $queue, $options);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new SqsS3Job(
                $this->container,
                $this->sqs,
                $response['Messages'][0],
                $this->connectionName,
                $queue
            );
        }

        return null;
    }
}
