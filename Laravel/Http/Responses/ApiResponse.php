<?php

namespace Caliente\Common\Laravel\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * API routes common response
 */
class ApiResponse extends JsonResponse
{

    /**
     * Create a new API json response instance.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @param  bool  $json
     *
     * @return void
     */
    public function __construct(
        $data = ['status' => 'success'],
        $status = 200,
        $headers = [],
        $options = 0,
        $json = false
    ){

        parent::__construct($data, $status, $headers, $json);
    }

    /**
     * Set custom value in the json array
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return self
     */
    public function setJsonData(string $key, $value = null)
    {
        $data       = $this->getData(true);
        $data       = is_array($data) ? $data : [];
        $data[$key] = $value;
        $this->setData($data);
        return $this;
    }

    /**
     * Set the status as success
     *
     * @return $this
     */
    public function success()
    {
        return $this->setJsonData('status', 'success');
    }

    /**
     * Set the status as fail
     *
     * @return $this
     */
    public function fail()
    {
        return $this->setJsonData('status', 'fail');
    }

    /**
     * Set the status as error
     *
     * @return $this
     */
    public function error()
    {
        return $this->setJsonData('status', 'error');
    }

    /**
     * Set the message field of the response
     *
     * @param  string  $message
     *
     * @return $this
     */
    public function message(string $message)
    {
        return $this->setJsonData('message', $message);
    }

    /**
     * Set the code field of the response
     *
     * @param  string|int  $code
     *
     * @return $this
     */
    public function code(string|int $code)
    {
        return $this->setJsonData('code', $code);
    }

    /**
     * Set the data field of the response
     *
     * @param  mixed  $data
     *
     * @return $this
     */
    public function data($data)
    {
        return $this->setJsonData('data', $data);
    }
}
