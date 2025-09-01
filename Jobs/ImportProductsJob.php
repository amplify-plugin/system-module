<?php

namespace Amplify\System\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $url = 'https://xmlapi.navigator.traceparts.com/DataApi/Catalog.asmx/GenerateAllCatsXML_envFlg?company=DK-LOK&serviceUserID=CtlgApi&servicePassword=8vk52l7se8&cid=7673&isPub=True';

            $response = Http::get($url);

            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                $auditId = (string) $xml;

                Log::info('Catalog Import Triggered. Audit ID: '.$auditId);

                // Dispatch job to check status
                CheckImportStatusJob::dispatch($auditId)->delay(now()->addSeconds(10));
            } else {
                Log::error('Failed to trigger catalog import. Response: '.$response->body());
            }
        } catch (\Exception $e) {
            Log::error('Exception in ImportProductsJob: '.$e->getMessage());
        }
    }
}
