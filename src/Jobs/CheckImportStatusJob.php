<?php

namespace Amplify\System\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckImportStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $auditId;

    /**
     * Create a new job instance.
     */
    public function __construct($auditId)
    {
        $this->auditId = $auditId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $url = "https://xmlapi.navigator.traceparts.com/DataApi/Catalog.asmx/Status?cid=7673&company=DK-LOK&serviceUserID=CtlgApi&servicePassword=8vk52l7se8&AuditID={$this->auditId}";

            $response = Http::get($url);

            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                $status = (string) $xml;

                Log::info("Catalog Import Status: {$status}");

                if ($status == '1') {
                    // Import completed, fetch catalog URLs
                    FetchAndProcessCatalogJob::dispatch();
                } else {
                    // Still in progress, retry in 30 seconds
                    CheckImportStatusJob::dispatch($this->auditId)->delay(now()->addMinutes(3));
                }
            } else {
                Log::error('Failed to check catalog status. Response: '.$response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in CheckImportStatusJob: '.$e->getMessage());
        }
    }
}
