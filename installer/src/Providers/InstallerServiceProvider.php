<?php
вввввв
namespace Gtx\Installer\Providers;

use BaseHelper;
use Gtx\Base\Events\FinishedSeederEvent;
use Gtx\Base\Events\UpdatedEvent;
use Gtx\Base\Traits\LoadAndPublishDataTrait;
use Gtx\Installer\Http\Middleware\CheckIfInstalledMiddleware;
use Gtx\Installer\Http\Middleware\CheckIfInstallingMiddleware;
use Gtx\Installer\Http\Middleware\RedirectIfNotInstalledMiddleware;
use Carbon\Carbon;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

class InstallerServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        $this->setNamespace('packages/installer')
            ->loadHelpers()
            ->loadAndPublishConfigurations('installer')
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes()
            ->publishAssets();

        Event::listen(RouteMatched::class, function () {
            if (defined('INSTALLED_SESSION_NAME')) {
                $router = $this->app->make('router');

                $router->middlewareGroup('install', [CheckIfInstalledMiddleware::class]);
                $router->middlewareGroup('installing', [CheckIfInstallingMiddleware::class]);

                $router->pushMiddlewareToGroup('web', RedirectIfNotInstalledMiddleware::class);
            }
        });

        try {
            Event::listen([UpdatedEvent::class, FinishedSeederEvent::class], function () {
                BaseHelper::saveFileData(storage_path(INSTALLED_SESSION_NAME), Carbon::now()->toDateTimeString());
            });
        } catch (Throwable $exception) {
            info($exception->getMessage());
        }
    }
}
