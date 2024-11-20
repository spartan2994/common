<?php

namespace Caliente\Common\Laravel\Providers;

use Caliente\Common\Laravel\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

/**
 * Api response using jsend specification
 */
class ResponseApiServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Response::macro('api', function (){
            return new ApiResponse();
        });
    }
}
