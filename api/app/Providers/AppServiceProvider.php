<?php

namespace App\Providers;

use App\Services\ElasticsearchService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(ElasticsearchService::class)
            ->needs(Client::class)
            ->give(function () {
                return new Client([
                    'base_uri' => config('services.elasticsearch.host'),
                    'timeout'  => 5.0,
                ]);
            });
    }

    public function boot(): void
    {
        //
    }
}