<?php

namespace Caliente\Common\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Caliente\Common\Classes\Webapi;

/**
 * IMS Service provider
 */
class ImsServiceProvider extends ServiceProvider{

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register(){
		$this->app->singleton(Webapi::class, function($app){
			return new Webapi($app['config']['webapi']['url']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 * Defer class loading.
	 *
	 * @return array
	 */
	public function provides(){
		return [Webapi::class];
	}
}