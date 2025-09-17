<?php

namespace Amplify\System\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('money', function ($expression) {
                return "<?php echo currency_format({$expression}); ?>";
            });

            $bladeCompiler->directive('uom', function ($expression) {
                dd($expression);
                return "<?php e(uom({$expression})); ?>";
            });
        });
    }
}
