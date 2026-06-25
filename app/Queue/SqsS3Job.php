<?php

namespace App\Queue;

use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Facades\Storage;

class SqsS3Job extends SqsJob
{
    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        if ($this->cachedRawBody !== null) {
            return $this->cachedRawBody;
        }

        $body = $this->job['Body'] ?? '';
        $decoded = json_decode($body, true);

        if (is_array($decoded) && ($decoded['is_offloaded'] ?? false) === true && isset($decoded['s3_key'])) {
            $s3Key = $decoded['s3_key'];

            return $this->cachedRawBody = Storage::disk('s3')->get($s3Key);
        }

        return parent::getRawBody();
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $body = $this->job['Body'] ?? '';
        $decoded = json_decode($body, true);

        if (is_array($decoded) && ($decoded['is_offloaded'] ?? false) === true && isset($decoded['s3_key'])) {
            $s3Key = $decoded['s3_key'];
            if (Storage::disk('s3')->exists($s3Key)) {
                Storage::disk('s3')->delete($s3Key);
            }
        }
    }
}
