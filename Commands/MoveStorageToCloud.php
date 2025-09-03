<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class MoveStorageToCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:storage {from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $from = $this->argument('from');
        Config::set('filesystems.disks.local.root', base_path($from));

        $localStorage = Storage::disk('local');
        $allFiles = $localStorage->allFiles();

        foreach ($allFiles as $file) {
            $this->comment("Uploading file {$file}.");
            $uploadStatus = Storage::disk('uploads')->put($file, $localStorage->get($file));
            $this->info("Uploaded file: {$file}.");

            file_put_contents('some.uploads.log', $file.' => '.$uploadStatus.PHP_EOL, FILE_APPEND);
        }

        return 0;
    }
}
