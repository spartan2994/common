<?php

namespace Caliente\Common\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;

class WhitelistIp{

	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * The Response Factory our app uses
	 *
	 * @var ResponseFactory
	 */
	protected $factory;

	/**
	 * Create a new middleware instance.
	 *
	 * @param  Application  $app
	 * @param \Illuminate\Contracts\Routing\ResponseFactory $factory
	 *
	 * @return void
	 */
	public function __construct(Application $app, ResponseFactory $factory){
		$this->app = $app;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param Closure $next
	 *
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next, $config = ''){
		$ips = $this->app['config']->get($config);
		$ips = is_array($ips) ? $ips : [];
		if(!in_array($request->getClientIp(), $ips)){
			//abort(403, "Forbidden");
			//TODO: Change to not just handle api requests but also web requests
			return $this->factory->json('Forbidden', 403);
		}

		return $next($request);
	}
}