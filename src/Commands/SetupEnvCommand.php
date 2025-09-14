<?php

namespace Amplify\System\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SetupEnvCommand extends Command
{
    private $rootDir = 'packages';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:publish-module {--force : Remove the existing destination folder.}';

    protected $description = 'Setup Developer Environment Setup';

    protected $modules = ['all', 'system', 'custom-item', 'cms',
        'utility', 'marketing', 'ticket', 'media', 'erp',
        'order-rule', 'sayt', 'captcha', 'message', 'payment',
        'frontend', 'api', 'widget', 'backend'];

    private $packages = [];

    private function removeDir(string $dir): void
    {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            $this->packages = $this->choice('Which plugins do you want to setup? (comma-space separated)', $this->modules, 0, 5, true);

            if (in_array('all', $this->packages)) {
                unset($this->modules[0]);
                $this->packages = $this->modules;
            }

            if (!is_dir(base_path($this->rootDir))) {
                mkdir(base_path($this->rootDir));
            }

            $this->runGitOperation();

            $this->info('[' . implode(',', $this->packages) . '] module(s) configured. Run `composer update` command to update class autoloader.');

            return self::SUCCESS;

        } catch (\Exception $exception) {

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function runGitOperation()
    {
        foreach ($this->packages as $package) {

            $directory = base_path($this->rootDir . DIRECTORY_SEPARATOR . $package);

            $this->info('Configuring: ' . Str::studly($package) . ' module into [.' . str_replace('\\', '/', str_replace(base_path(), '', $directory)) . ']');

            if (is_dir($directory) && $this->option('force', false)) {
                $this->removeDir($directory);
            }

            shell_exec("git clone https://github.com/amplify-plugin/{$package}-module.git {$directory}");

        }

        $this->updateComposerFile();

        shell_exec("composer update --no-cache");
    }

    private function updateComposerFile()
    {
        $file = base_path('composer.json');

        $composer = json_decode(file_get_contents($file), true);

        $repositories = $composer['repositories'] ?? [];

        $packagesExists = false;

        $repositories = array_filter($repositories, function ($item) use(&$packagesExists) {
            if (in_array($item['type'], ['composer', 'vcs'])) {
                return true;
            }

            if ($item['type'] === 'path' && $item['url'] == "./packages/*") {
                $packagesExists = true;
                return true;
            }

            return false;
        });

        if (!$packagesExists) {
            $repositories[] = [
                "type" => "path",
                "url" => "./packages/*",
                "options" => [
                    "symlink" => true
                ]
            ];
        }

        $composer['repositories'] = $repositories;

        file_put_contents($file, str_replace("\/", '/', json_encode($composer, JSON_PRETTY_PRINT)));

        if (file_exists(base_path('composer.lock'))) {
            unlink(base_path('composer.lock'));
        }
    }
}
