<?php

namespace Caliente\Common\Spin;

use Illuminate\Support\ServiceProvider;

class SpinProvider extends ServiceProvider{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(){
        $this->app->singleton('spin', function($app){
            return new Spin();
        });
    }

    /**
     * Get the services provided by the provider.
     * Defer class loading.
     *
     * @return array
     */
    public function provides(){
        return [Spin::class];
    }
}