<?php

class API
{
    private $curl;
    private $base;
    private $key;
    private $headers = array();
    private $error = null;
    private $json = true;
    private $http_code;
    public function __construct($api_key, $base_url = null, $send_json = true)
    {
        if (isset($base_url)) {
            $this->base = $base_url;
        }
        if (isset($api_key)) {
            $this->key = $api_key;
            $this->header('Authorization', $this->key);
        }
        $this->json = $send_json;
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
        $this->curl = null;
        $this->base = null;
        $this->key = null;
        $this->headers = null;
        $this->error = null;
        $this->json = null;
        $this->http_code = null;
    }
    private function init()
    {
        if (function_exists('curl_reset') && (is_resource($this->curl) || $this->curl instanceof \CurlHandle)) {
            curl_reset($this->curl);
        } else {
            $this->curl = curl_init();
        }
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
    }
    public function error()
    {
        return json_decode($this->error, true);
    }
    private function request($url, $fields, $method = null)
    {
        if (!$this->curl) {
            // new request for the same API
            $this->init();
        }
        // set URL
        curl_setopt($this->curl, CURLOPT_URL, "{$this->base}$url");
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
        if (isset($fields)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
        }
        // finish
        $output = curl_exec($this->curl);
        $this->http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        http_response_code($this->http_code);
        if (curl_error($this->curl)) {
            $this->error = curl_error($this->curl);
            return false;
        } else {
            if ($this->http_code === 200) {
                return $output;
            } elseif (isset($output)) {
                $this->error = $output;
                return false;
            } else {
                return $this->http_code;
            }
        }
        curl_close($this->curl);
        $this->curl = null;
    }
    public function header($key, $value)
    {
        array_push($this->headers, $key.': '.$value);
    }
    public function get($url = null, $fields = array())
    {
        if (count($fields)) {
            $query_string = '';
            $query_mark = strpos($url, '?') > 0 ? '&' : '?';
            $query_string .= $query_mark . http_build_query($fields, '', '&');
            $url = $url . $query_string;
        }
        return $this->request($url, false, "GET");
    }
    public function post($url = null, $fields = array())
    {
        return $this->request($url, $fields, "POST");
    }
    public function put($url = null, $fields = array())
    {
        return $this->request($url, $fields, "PUT");
    }
    public function patch($url = null, $fields = array())
    {
        return $this->request($url, $fields, "PATCH");
    }
    public function delete($url = null, $fields = array())
    {
        return $this->request($url, $fields, "DELETE");
    }
}
