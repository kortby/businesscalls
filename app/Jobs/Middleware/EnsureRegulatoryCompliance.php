<?php

namespace App\Jobs\Middleware;

use App\Models\AuditLog;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EnsureRegulatoryCompliance
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        $campaign = $job->campaign;

        // Enforce multi-tenant database isolation bounds
        TenantScope::setTenantId($campaign->tenant_id);

        // Fetch pending recipients who have not been called yet
        $recipients = $campaign->recipients()->whereNull('call_id')->get();

        foreach ($recipients as $recipient) {
            $phoneNumber = $recipient->phone_number;
            $timezone = $this->getTimezoneForPhone($phoneNumber);

            // Evaluate current time in target timezone
            $localTime = Carbon::now($timezone);
            $hour = (int) $localTime->format('H');

            // TCPA standard calling window is 8:00 AM to 9:00 PM local time
            if ($hour < 8 || $hour >= 21) {
                Log::warning("TCPA Compliance Triggered: Recipient phone {$phoneNumber} in timezone {$timezone} is at local hour {$hour}. Rescheduling campaign.");

                // Calculate backoff delay seconds to tomorrow 8:00 AM in the recipient's timezone
                $targetTime = Carbon::tomorrow($timezone)->setTime(8, 0, 0);
                $delaySeconds = max(0, $targetTime->diffInSeconds(Carbon::now($timezone)));

                // Log the compliance bypass event in the isolated audit logs table
                AuditLog::create([
                    'tenant_id' => $campaign->tenant_id,
                    'user_id' => null,
                    'action' => 'tcpa_compliance_violation',
                    'ip_address' => '127.0.0.1',
                    'browser_agent' => 'QueueWorker',
                    'payload' => [
                        'campaign_id' => $campaign->id,
                        'recipient_id' => $recipient->id,
                        'phone_number' => $phoneNumber,
                        'timezone' => $timezone,
                        'local_time' => $localTime->toDateTimeString(),
                        'rescheduled_delay_seconds' => $delaySeconds,
                    ],
                ]);

                // Reschedule the campaign batch job using $job->release()
                $job->release($delaySeconds);

                // Bypass job dialing execution immediately
                return;
            }
        }

        return $next($job);
    }

    /**
     * Map US/Canada area codes to native time zones.
     */
    public function getTimezoneForPhone(string $phoneNumber): string
    {
        $clean = preg_replace('/\D/', '', $phoneNumber);

        if (str_starts_with($clean, '1') && strlen($clean) >= 4) {
            $areaCode = substr($clean, 1, 3);
        } elseif (strlen($clean) >= 3) {
            $areaCode = substr($clean, 0, 3);
        } else {
            return 'America/New_York';
        }

        $timezoneMap = [
            // EST / Eastern Time
            '201' => 'America/New_York', '202' => 'America/New_York', '203' => 'America/New_York', '207' => 'America/New_York',
            '212' => 'America/New_York', '215' => 'America/New_York', '216' => 'America/New_York', '226' => 'America/New_York',
            '229' => 'America/New_York', '234' => 'America/New_York', '239' => 'America/New_York', '240' => 'America/New_York',
            '248' => 'America/New_York', '249' => 'America/New_York', '252' => 'America/New_York', '260' => 'America/New_York',
            '267' => 'America/New_York', '270' => 'America/New_York', '272' => 'America/New_York', '289' => 'America/New_York',
            '301' => 'America/New_York', '302' => 'America/New_York', '304' => 'America/New_York', '305' => 'America/New_York',
            '313' => 'America/New_York', '315' => 'America/New_York', '321' => 'America/New_York', '330' => 'America/New_York',
            '339' => 'America/New_York', '343' => 'America/New_York', '347' => 'America/New_York', '351' => 'America/New_York',
            '352' => 'America/New_York', '365' => 'America/New_York', '386' => 'America/New_York', '401' => 'America/New_York',
            '407' => 'America/New_York', '410' => 'America/New_York', '412' => 'America/New_York', '416' => 'America/New_York',
            '418' => 'America/New_York', '437' => 'America/New_York', '438' => 'America/New_York', '440' => 'America/New_York',
            '443' => 'America/New_York', '450' => 'America/New_York', '470' => 'America/New_York', '475' => 'America/New_York',
            '478' => 'America/New_York', '484' => 'America/New_York', '502' => 'America/New_York', '506' => 'America/New_York',
            '513' => 'America/New_York', '514' => 'America/New_York', '518' => 'America/New_York', '519' => 'America/New_York',
            '540' => 'America/New_York', '567' => 'America/New_York', '570' => 'America/New_York', '571' => 'America/New_York',
            '579' => 'America/New_York', '581' => 'America/New_York', '585' => 'America/New_York', '586' => 'America/New_York',
            '606' => 'America/New_York', '607' => 'America/New_York', '609' => 'America/New_York', '613' => 'America/New_York',
            '614' => 'America/New_York', '617' => 'America/New_York', '631' => 'America/New_York', '646' => 'America/New_York',
            '678' => 'America/New_York', '681' => 'America/New_York', '703' => 'America/New_York', '704' => 'America/New_York',
            '705' => 'America/New_York', '716' => 'America/New_York', '717' => 'America/New_York', '718' => 'America/New_York',
            '724' => 'America/New_York', '732' => 'America/New_York', '734' => 'America/New_York', '740' => 'America/New_York',
            '754' => 'America/New_York', '772' => 'America/New_York', '774' => 'America/New_York', '781' => 'America/New_York',
            '786' => 'America/New_York', '803' => 'America/New_York', '804' => 'America/New_York', '807' => 'America/New_York',
            '810' => 'America/New_York', '814' => 'America/New_York', '819' => 'America/New_York', '828' => 'America/New_York',
            '838' => 'America/New_York', '845' => 'America/New_York', '848' => 'America/New_York', '856' => 'America/New_York',
            '857' => 'America/New_York', '860' => 'America/New_York', '862' => 'America/New_York', '864' => 'America/New_York',
            '865' => 'America/New_York', '873' => 'America/New_York', '878' => 'America/New_York', '901' => 'America/New_York',
            '902' => 'America/New_York', '905' => 'America/New_York', '908' => 'America/New_York', '910' => 'America/New_York',
            '914' => 'America/New_York', '917' => 'America/New_York', '919' => 'America/New_York', '937' => 'America/New_York',
            '941' => 'America/New_York', '954' => 'America/New_York', '973' => 'America/New_York', '978' => 'America/New_York',
            '980' => 'America/New_York', '984' => 'America/New_York', '989' => 'America/New_York',

            // CST / Central Time
            '204' => 'America/Chicago', '205' => 'America/Chicago', '217' => 'America/Chicago', '218' => 'America/Chicago',
            '224' => 'America/Chicago', '228' => 'America/Chicago', '251' => 'America/Chicago', '256' => 'America/Chicago',
            '262' => 'America/Chicago', '306' => 'America/Chicago', '309' => 'America/Chicago', '312' => 'America/Chicago',
            '314' => 'America/Chicago', '318' => 'America/Chicago', '319' => 'America/Chicago', '325' => 'America/Chicago',
            '331' => 'America/Chicago', '334' => 'America/Chicago', '337' => 'America/Chicago', '361' => 'America/Chicago',
            '402' => 'America/Chicago', '405' => 'America/Chicago', '409' => 'America/Chicago', '414' => 'America/Chicago',
            '417' => 'America/Chicago', '430' => 'America/Chicago', '432' => 'America/Chicago', '469' => 'America/Chicago',
            '479' => 'America/Chicago', '504' => 'America/Chicago', '507' => 'America/Chicago', '512' => 'America/Chicago',
            '515' => 'America/Chicago', '563' => 'America/Chicago', '573' => 'America/Chicago', '580' => 'America/Chicago',
            '601' => 'America/Chicago', '605' => 'America/Chicago', '608' => 'America/Chicago', '612' => 'America/Chicago',
            '615' => 'America/Chicago', '618' => 'America/Chicago', '630' => 'America/Chicago', '636' => 'America/Chicago',
            '641' => 'America/Chicago', '651' => 'America/Chicago', '660' => 'America/Chicago', '662' => 'America/Chicago',
            '701' => 'America/Chicago', '708' => 'America/Chicago', '712' => 'America/Chicago', '713' => 'America/Chicago',
            '715' => 'America/Chicago', '731' => 'America/Chicago', '757' => 'America/Chicago', '763' => 'America/Chicago',
            '769' => 'America/Chicago', '773' => 'America/Chicago', '779' => 'America/Chicago', '812' => 'America/Chicago',
            '815' => 'America/Chicago', '816' => 'America/Chicago', '817' => 'America/Chicago', '830' => 'America/Chicago',
            '832' => 'America/Chicago', '847' => 'America/Chicago', '903' => 'America/Chicago', '913' => 'America/Chicago',
            '915' => 'America/Chicago', '918' => 'America/Chicago', '920' => 'America/Chicago', '931' => 'America/Chicago',
            '936' => 'America/Chicago', '940' => 'America/Chicago', '947' => 'America/Chicago', '952' => 'America/Chicago',
            '956' => 'America/Chicago', '972' => 'America/Chicago', '979' => 'America/Chicago', '985' => 'America/Chicago',

            // MST / Mountain Time
            '208' => 'America/Denver', '250' => 'America/Denver', '303' => 'America/Denver', '307' => 'America/Denver',
            '385' => 'America/Denver', '403' => 'America/Denver', '406' => 'America/Denver', '435' => 'America/Denver',
            '480' => 'America/Denver', '505' => 'America/Denver', '520' => 'America/Denver', '587' => 'America/Denver',
            '602' => 'America/Denver', '623' => 'America/Denver', '719' => 'America/Denver', '720' => 'America/Denver',
            '778' => 'America/Denver', '780' => 'America/Denver', '801' => 'America/Denver', '825' => 'America/Denver',
            '928' => 'America/Denver', '970' => 'America/Denver',

            // PST / Pacific Time
            '206' => 'America/Los_Angeles', '236' => 'America/Los_Angeles', '253' => 'America/Los_Angeles',
            '360' => 'America/Los_Angeles', '408' => 'America/Los_Angeles', '415' => 'America/Los_Angeles',
            '425' => 'America/Los_Angeles', '503' => 'America/Los_Angeles', '509' => 'America/Los_Angeles',
            '510' => 'America/Los_Angeles', '530' => 'America/Los_Angeles', '541' => 'America/Los_Angeles',
            '562' => 'America/Los_Angeles', '604' => 'America/Los_Angeles', '626' => 'America/Los_Angeles',
            '650' => 'America/Los_Angeles', '661' => 'America/Los_Angeles', '702' => 'America/Los_Angeles',
            '707' => 'America/Los_Angeles', '714' => 'America/Los_Angeles', '725' => 'America/Los_Angeles',
            '760' => 'America/Los_Angeles', '805' => 'America/Los_Angeles', '818' => 'America/Los_Angeles',
            '831' => 'America/Los_Angeles', '858' => 'America/Los_Angeles', '907' => 'America/Los_Angeles',
            '909' => 'America/Los_Angeles', '916' => 'America/Los_Angeles', '925' => 'America/Los_Angeles',
            '949' => 'America/Los_Angeles', '951' => 'America/Los_Angeles', '971' => 'America/Los_Angeles',
        ];

        return $timezoneMap[$areaCode] ?? 'America/New_York';
    }
}
