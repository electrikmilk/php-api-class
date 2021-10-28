<?php

class API
{
    private $key;
    private $base;
    private $curl;
    private $headers = array();
    public bool $json;
    public int $http_code;
    private $output;
    private $error;
    /**
     * Construct
     *
     * @access public
     * @param $api_key string
     *  Your API key goes here. This is used to create the 'Authorization' header, so feel free to add 'Basic', 'Bearer', etc.
     * @param $base_url string
     *  Base API URL (eg. https://api.example.com/v1/), sans the endpoint.
     * @param $send_json bool
     *  Sets the $json member, which determines whether to send fields as JSON data (only applies to non-GET requests).
     * @throws \ErrorException
     */
    public function __construct($api_key = null, $base_url = null, $send_json = true)
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }
        if (isset($api_key)) {
            $this->key = $api_key;
            $this->header('Authorization', $this->key);
        }
        if (isset($base_url)) {
            $this->base = $base_url;
        }
        if ($send_json === true) {
            $this->json = true;
        } else {
            $this->json = false;
        }
        if ($this->json !== false) {
            $this->header('Content-Type', 'application/json');
        }
        $this->init();
    }
    /**
    * Destruct
    *
    * @access public
    */
    public function __destruct()
    {
        if (is_resource($this->curl) || $this->curl instanceof \CurlHandle) {
            curl_close($this->curl);
        }
        $this->key = null;
        $this->base = null;
        $this->curl = null;
        $this->headers = array();
        $this->json = true;
        $this->http_code = 0;
        $this->output = null;
        $this->error = null;
    }
    /**
    * Init
    *
    * Starts a new cURL instance for making API requests
    *
    * @access private
    */
    private function init()
    {
        if (function_exists('curl_reset') && (is_resource($this->curl) || $this->curl instanceof \CurlHandle)) {
            curl_reset($this->curl);
        } else {
            $this->curl = curl_init();
        }
        $this->http_code = 0;
        $this->output = null;
        $this->error = null;
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }
    /**
    * Header
    *
    * Set cURL header
    *
    * @access public
    * @param $key
    * @param $value
    */
    public function header($key, $value)
    {
        array_push($this->headers, $key.': '.$value);
    }
    /**
    * Opt
    *
    * Set cURL option
    *
    * @access public
    * @param $key
    * @param $value
    */
    public function opt($key, $value)
    {
        if ($key === CURLOPT_RETURNTRANSFER) {
            return false;
        }
        curl_setopt($this->curl, $key, $value);
    }
    /**
    * Reset
    *
    * Reset cURL
    *
    * @access public
    */
    public function reset()
    {
        curl_reset($this->curl);
        $this->http_code = 0;
        $this->output = null;
        $this->error = null;
    }
    /**
    * Response
    *
    * Access response from request
    *
    * @access public
    * @param $json_output
    *   Whether or not expected output is JSON
    */
    public function response($json_output = true)
    {
        if (isset($this->output)) {
            if ($json_output === true) {
                return json_decode($this->output, true);
            } else {
                return $this->output;
            }
        } else {
            return false;
        }
    }
    /**
    * Error
    *
    * Access error from request
    *
    * @access public
    * @param $json_output
    *   Whether or not expected output is JSON
    */
    public function error($json_output = true)
    {
        if (isset($this->error)) {
            if ($json_output === true) {
                return json_decode($this->error, true);
            } else {
                return $this->error;
            }
        } else {
            return false;
        }
    }
    /**
    * Request
    *
    * Main API request function
    *
    * @access private
    * @param $endpoint
    * @param $fields
    * @param $method
    *
    * @return string
    */
    private function request($endpoint = null, $fields = array(), $method = null)
    {
        if (!$this->curl) {
            // new request for the same API
            $this->init();
        }
        // set URL
        $this->opt(CURLOPT_URL, $this->base.$endpoint);
        // set headers
        $this->opt(CURLOPT_HTTPHEADER, $this->headers);
        // set method
        if (isset($method)) {
            if ($method === "GET") {
                curl_setopt($this->curl, CURLOPT_HTTPGET, true);
            }
            if ($method === "POST") {
                curl_setopt($this->curl, CURLOPT_POST, true);
            }
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        } else {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, null);
        }
        // set fields
        if ($this->json !== false) {
            $fields = json_encode($fields);
        } else {
            $fields = http_build_query($fields);
        }
        if (isset($fields) && $method !== "GET") {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
        }
        // finish
        $this->output = curl_exec($this->curl);
        $this->http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        // set response code
        http_response_code($this->http_code);
        // output
        if (curl_error($this->curl)) {
            $this->error = curl_error($this->curl);
            return false;
        } else {
            if (isset($this->output)) {
                if ($this->http_code === 200) {
                    return $this->output;
                } else {
                    $this->error = $this->output;
                    return null;
                }
            } else {
                return $this->http_code;
            }
        }
        // close cURL, discard variable
        curl_close($this->curl);
        $this->curl = null;
    }
    /**
    * Get
    *
    * Passes 'GET' with fields appended to endpoint to request() method
    *
    * @access public
    * @param $endpoint
    * @param $fields
    */
    public function get($endpoint = null, $fields = array())
    {
        // process fields
        if (count($fields)) {
            $query_string = '';
            $query_mark = strpos($endpoint, '?') > 0 ? '&' : '?';
            $query_string .= $query_mark . http_build_query($fields, '', '&');
            $endpoint = $endpoint . $query_string;
        }
        return $this->request($endpoint, false, "GET");
    }
    /**
    * Post
    *
    * Passes 'POST' with fields to request() method
    *
    * @access public
    * @param $endpoint
    * @param $fields
    */
    public function post($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "POST");
    }
    /**
    * Put
    *
    * Passes 'PUT' with fields to request() method
    *
    * @access public
    * @param $endpoint
    * @param $fields
    */
    public function put($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "PUT");
    }
    /**
    * Patch
    *
    * Passes 'PATCH' with fields to request() method
    *
    * @access public
    * @param $endpoint
    * @param $fields
    */
    public function patch($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "PATCH");
    }
    /**
    * Delete
    *
    * Passes 'DELETE' with fields to request() method
    *
    * @access public
    * @param $endpoint
    * @param $fields
    */
    public function delete($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "DELETE");
    }
}
