<?php

namespace App\Services\SpinPremia;

class Response{
    /**
     * The collection of the data returned for
     *
     * @var array
     */
    protected $json = array();

    /**
     * Response constructor
     *
     * @param $json
     */
    public function __construct($json){
        $this->json = is_array($json) ? $json : [];
    }

    /**
     * Check if response is success
     *
     * @return bool
     */
    public function isSuccess(){
        return $this->getStatus() == 'success';
    }

    /**
     * Check if response is error
     *
     * @return bool
     */
    public function isError(){
        $status = $this->getStatus();

        return empty($status) || $status == 'error';
    }

    /**
     * Get the status of the response
     *
     * @return string|null
     */
    public function getStatus(){
        return data_get($this->json, 'status');
    }

    /**
     * Get the message of the last response
     *
     * @return mixed
     */
    public function getMessage(){
        return data_get($this->json, 'desc');
    }

    /**
     * If is error response getCode will return the error code of the response
     *
     * @return mixed
     */
    public function getCode(){
        return data_get($this->json, 'data.code');
    }

    /**
     * Get the response data if exists, empty array otherwise.
     *
     * @return array
     */
    public function getData(){
        return data_get($this->json, 'data', []);
    }

    /**
     * Return the value of the field "$key" present in "data". If not found default value will be returned
     *
     * @param string $key     The name of the field present in "data". You can use dot notation to access the values
     * @param null   $default The default value to return if $key is not found in "data"
     *
     * @return mixed
     */
    public function get($key, $default = null){
        $key = ltrim(trim($key), '.');

        return data_get($this->json, 'data.' . $key, $default);
    }

    /**
     * Return this request json
     *
     * @return array
     */
    public function getJson(){
        return $this->json;
    }
}
