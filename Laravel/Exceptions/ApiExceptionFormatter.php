<?php

namespace Caliente\Common\Laravel\Exceptions;

use Caliente\Common\Laravel\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

/**
 * @mixin \Illuminate\Foundation\Exceptions\Handler
 */
trait ApiExceptionFormatter
{
    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return (new ApiResponse())
            ->fail()
            ->setStatusCode($exception->status)
            ->code('validation-fail')
            ->message('Validation error')
            ->data(collect($exception->errors())->map(function ($errors){
                return $errors[0] ?? '';
            })->all());
    }

    /**
     * Prepare a JSON response for the given exception.
     *
     * @param  Request  $request
     * @param  \Exception  $e
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        $message = 'Server Error';
        if (config('app.debug') || $this->isHttpException($e)) {
            $message = $e->getMessage();
        }

        $data = config('app.debug') ? [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => collect($e->getTrace())->map(function ($trace){
                return Arr::except($trace, ['args']);
            })->all(),
        ] : null;

        /**
         * Validation exceptions
         */
        if ($e instanceof ValidationException) {
            return (new ApiResponse())
                ->fail()
                ->setStatusCode($e->status)
                ->code('validation-fail')
                ->message('Validation error')
                ->data(collect($e->validator->errors()->messages())->map(function ($errors){
                    return $errors[0] ?? '';
                })->all());
        }

        return (new ApiResponse())
            ->error()
            ->message($message)
            ->code($e->getCode())
            ->data($data)
            ->setStatusCode($this->isHttpException($e) ? $e->getStatusCode() : 500)
            ->withHeaders($this->isHttpException($e) ? $e->getHeaders() : []);
    }

    /**
     * Determine if the exception handler response should be JSON.
     *
     * @param Request   $request
     * @param Throwable $e
     *
     * @return bool
     */
    protected function shouldReturnJson($request, Throwable $e){
        return $request->expectsJson() || $request->is('api/*');
    }

    /**
     * Render an exception into a JSON response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof BaseException)
        {
            return (new ApiResponse())
                ->setStatusCode($this->isHttpException($e) ? $e->getStatusCode() : 500)
                ->error()
                ->code($e->getCode())
                ->message($e->getMessage());
        }

        $e = $this->prepareException($e);

        return $e instanceof Response ? $e : parent::render($request, $e);
    }
}
