<?php

namespace Caliente\Common\Laravel\Http\Requests;

use Caliente\Common\Webapi\Facade\Webapi;
use Illuminate\Foundation\Http\FormRequest;
use Closure;

class AuthenticationRequest extends FormRequest{

	public function handle($request, Closure $next){
		// Get the user and token from the request
		$user     = $request->input('user');
		$identity = $request->input('identity');
		$token    = $request->input('token');

		$webApi = new Webapi();

		// If authentication fails, return "auth failed" response
		if(!isset($webApi)){
			return response('auth failed', 401);
		}

		// SetIdentity
		$identity = $this->getIdentity(strtolower($identity));
		$webApi->setIdentity($identity);

		// Validate the request using getPlayerInfo
		$data = $webApi->getPlayerInfo($user, "", array("username", "playerCode"));

		// Validate is error
		if($data->isError()){
			return response($data->getData());
		}

		// Store the authentication result in the request for later use
		$request->request->add(['authResult' => $data]);

		// Call the next middleware/controller in the chain
		return $next($request);
	}

	protected function getIdentity($identity){

		switch(trim($identity)){
			case Webapi::IDENTITY_USERNAME:
				return Webapi::IDENTITY_USERNAME;
			case Webapi::IDENTITY_PLAYER_CODE:
				return Webapi::IDENTITY_PLAYER_CODE;
			case Webapi::IDENTITY_EMAIL:
				return Webapi::IDENTITY_EMAIL;
			case Webapi::IDENTITY_CASINO:
				return Webapi::IDENTITY_CASINO;
			default:
				return "";
		}
	}
}