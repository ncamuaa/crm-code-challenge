<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
| Repository pattern: controllers depend on the interface, never the
| concrete Eloquent implementation. Makes the data layer swappable/testable.
*/

$app->singleton(
    App\Repositories\CustomerRepositoryInterface::class,
    App\Repositories\CustomerRepository::class
);

$app->singleton( App\Services\SearchServiceInterface::class, 
function ($app) { return new App\Services\ElasticsearchService(); } ); 

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
*/

$app->middleware([ App\Http\Middleware\Cors::class, ]);


/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Enable "unique"/"exists" validation rules
|--------------------------------------------------------------------------
| Lumen doesn't wire this up out of the box the way full Laravel does.
*/

$app->extend(Illuminate\Validation\Factory::class, function ($validator, $app) {
    $verifier = new Illuminate\Validation\DatabasePresenceVerifier($app['db']);
    $validator->setPresenceVerifier($verifier);

    return $validator;
});

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api',
], function ($router) {
    require __DIR__.'/../routes/api.php';
});

return $app;
