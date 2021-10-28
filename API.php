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
    public function __construct($api_key = null, $base_url = null, $send_json = true)
    {
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
    public function header($key, $value)
    {
        array_push($this->headers, $key.': '.$value);
    }
    public function opt($key, $value)
    {
        if ($key === CURLOPT_RETURNTRANSFER) {
            return false;
        }
        curl_setopt($this->curl, $key, $value);
    }
    public function reset()
    {
        curl_reset($this->curl);
        $this->http_code = 0;
        $this->output = null;
        $this->error = null;
    }
    public function response($json_output = true)
    {
        if ($this->output) {
            if ($json_output === true) {
                return json_decode($this->output, true);
            } else {
                return $this->output;
            }
        } else {
            return false;
        }
    }
    public function error($json_output = true)
    {
        if ($this->error) {
            if ($json_output === true) {
                return json_decode($this->error, true);
            } else {
                return $this->error;
            }
        } else {
            return false;
        }
    }
    private function request($endpoint, $fields, $method = null)
    {
        if (!$this->curl) {
            // new request for the same API
            $this->init();
        }
        // set URL
        $this->opt(CURLOPT_URL, $this->base.$endpoint);
        // set headers
        $this->opt(CURLOPT_HTTPHEADER, $this->headers);
        // method
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
        // add fields
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
        http_response_code($this->http_code);
        if (curl_error($this->curl)) {
            $this->error = curl_error($this->curl);
            return false;
        } else {
            if (isset($this->output)) {
                if ($this->http_code === 200) {
                    return $this->output;
                } else {
                    $this->error = $this->output;
                    return false;
                }
            } else {
                return $this->http_code;
            }
        }
        curl_close($this->curl);
        $this->curl = null;
    }
    public function get($endpoint = null, $fields = array())
    {
        if (count($fields)) {
            $query_string = '';
            $query_mark = strpos($endpoint, '?') > 0 ? '&' : '?';
            $query_string .= $query_mark . http_build_query($fields, '', '&');
            $endpoint = $endpoint . $query_string;
        }
        return $this->request($endpoint, false, "GET");
    }
    public function post($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "POST");
    }
    public function put($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "PUT");
    }
    public function patch($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "PATCH");
    }
    public function delete($endpoint = null, $fields = array())
    {
        return $this->request($endpoint, $fields, "DELETE");
    }
}
