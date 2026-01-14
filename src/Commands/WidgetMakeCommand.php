<?php

namespace Amplify\System\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class WidgetMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:widget {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a class and blade for widget of `Amplify`';

    private string $classRootNamespace = 'Amplify\Widget\Components';

    private string $classRootDir;

    private string $bladeRootDir;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->classRootDir = base_path('packages/widget/src/Components');

        $this->bladeRootDir = base_path('packages/widget/resources/views');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {

            $widget = $this->argument('name');

            $path = $this->createClassFile($widget);

            $path = str_replace(base_path('/'), '', $path);

            $this->components->info("Widget [{$path}] created successfully.");

            return self::SUCCESS;

        } catch (Exception $exception) {

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function createClassFile($widget)
    {
        // create blade file
        $this->createBladeFile($widget);

        // create class file
        $classFilePath = $this->classRootDir.'/'.$this->resolvePath($widget, '/', 'studly');

        $this->createParentDirectory($classFilePath);

        $namespace = $this->resolveNameSpace($widget);

        $class = $this->resolveClassName($widget);

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ view }}' => 'view(\'widget::'.$this->resolveViewPath($widget).'\');',
        ];

        $templateContent = file_get_contents($this->getStub());

        $content = str_replace(array_keys($replacements), array_values($replacements), $templateContent);

        file_put_contents($classFilePath.'.php', $content, LOCK_EX);

        $classFullNS = $namespace.'\\'.$class;

        $this->registerConfig($classFullNS, $this->resolvePath($widget));

        return $classFilePath.'.php';
    }

    private function createBladeFile($widget)
    {
        $bladeFullPath = $this->bladeRootDir.'/'.$this->resolvePath($widget, '/');

        $this->createParentDirectory($bladeFullPath);

        $replacements = [

        ];

        $templateContent = file_get_contents(__DIR__.'/stubs/view-component.stub');

        $content = str_replace(array_keys($replacements), array_values($replacements), $templateContent);

        file_put_contents($bladeFullPath.'.blade.php', $content, LOCK_EX);
    }

    private function resolvePath($widget, $glue = '.', $formatter = 'kebab')
    {
        $widget = str_replace(['/', '\\'], '/', $widget);

        return collect(explode('/', $widget))
            ->map(function ($part) use ($formatter) {
                return Str::{$formatter}($part);
            })
            ->implode($glue);
    }

    private function createParentDirectory($path)
    {
        $basePath = dirname($path);

        if (! is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }
    }

    private function resolveNameSpace($widget)
    {
        $fullNS = str_replace(['/', '\\'], '\\', ($this->classRootNamespace.'\\'.$widget));

        return trim(implode('\\', array_slice(explode('\\', $fullNS), 0, -1)), '\\');
    }

    private function resolveClassName($widget)
    {
        $ns = str_replace(['/', '\\'], '\\', ($this->classRootNamespace.'\\'.$widget));

        return class_basename($ns);
    }

    private function resolveViewPath($widget)
    {
        return $this->resolvePath($widget);
    }

    private function getStub()
    {
        return __DIR__.'/stubs/class-component.stub';
    }

    private function registerConfig($classPath, $name)
    {
        $config_path = base_path('packages/widget/config/widget.php');

        $file_content = file_get_contents($config_path);

        $humanName = ucfirst(str_replace(['.', '-', '_'], [' ', ' ', ' '], $name));

        $template = <<<HTML
$classPath::class => [
            "name" => "{$name}",
            "reserved" => true,
            "internal" => true,
            "model" => [],
            "@inside" => null,
            "@client" => null,
            "@attributes" => [],
            "@nestedItems" => [],
            "description" => "{$humanName} widget",
],
        //DO NOT REMOVE THIS COMMENT//
HTML;
        file_put_contents($config_path, str_replace('//DO NOT REMOVE THIS COMMENT//', $template, $file_content));

    }
}
