<?php

namespace Caliente\Common\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * This middleware forces all response to be a JSON.
 * It's intended to be use in api routes
 */
class ApiResponse{

	/**
	 * The Response Factory our app uses
	 *
	 * @var  Illuminate\Contracts\Routing\ResponseFactory
	 */
	protected $factory;

	/**
	 * JsonMiddleware constructor.
	 *
	 * @param Illuminate\Contracts\Routing\ResponseFactory $factory
	 */
	public function __construct(ResponseFactory $factory){
		$this->factory = $factory;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  Request  $request
	 * @param  Closure  $next
	 *
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next){
		// First, set the header so any other middleware knows we're dealing with a should-be JSON response.
		$request->headers->set('Accept', 'application/json');

		// Get the response
		$response = $next($request);

		// If the response is not strictly a JsonResponse, we make it
		if(!$response instanceof JsonResponse){
			$response = $this->factory->json(
				$response->content(),
				$response->status(),
				$response->headers->all()
			);
		}

		return $response;
	}
}