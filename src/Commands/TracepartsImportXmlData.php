<?php

namespace Amplify\System\Commands;

use Amplify\System\Services\TracepartsXmlDataImportService;
use Illuminate\Console\Command;

class TracepartsImportXmlData extends Command
{
    protected $signature = 'traceparts:import-xml-data
                            {--all}
                            {--attributes}
                            {--master-products}
                            {--attach-master-products-to-categories}
                            {--import-skus}
                            {--update_sku_id}';

    protected $description = 'Import DK-Lok XML data into the database';

    protected TracepartsXmlDataImportService $importService;

    public function handle()
    {
        $this->importService = app(TracepartsXmlDataImportService::class);
        $opts = $this->options();

        if ($opts['all'] || $opts['attributes']) {
            $this->info('[1/5] Importing Attributes…');
            [$created, $updated] = $this->importService->importAttributes();
            $this->info(" ✓ {$created} created, {$updated} updated");
        }

        if ($opts['all'] || $opts['master-products']) {
            $this->info('[3/5] Importing Master Products…');
            [$created, $updated, $skipped] = $this->importService->importMasterProducts();
            $this->info(" ✓ {$created} created, {$updated} updated", " ✓ {$skipped} skipped");
        }

        if ($opts['all'] || $opts['attach-master-products-to-categories']) {
            $this->info('[4/5] Attaching Master Products → Categories…');
            [$attached, $updated] = $this->importService->attachMasterProductsToCategories();
            $this->info(" ✓ {$attached} attached, {$updated} updated");
        }

        if ($opts['all'] || $opts['import-skus']) {
            $this->info('[5/5] Dispatching SKU import jobs…');
            [$totalSkus, $jobs] = $this->importService->importSkuProductsWithAllData();
            $this->info(" ✓ {$totalSkus} SKUs found, {$jobs} jobs dispatched");
        }

        if ($opts['update_sku_id']) {
            $this->info('[6/6] Dispatching SKU import jobs…');
            $totalSkus = $this->importService->updateSkuId();
            $this->info(' ✓ SKUs updated');
        }

        return 0;
    }
}
