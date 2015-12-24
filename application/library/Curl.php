<?php
/**
 * session 级别的讨厌啊
 */
class Curl {
    private $cookies = '';
    private $data;
    private $url = '';
    private $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36';
    private $time_out = 20;
    private $curl;
    private $http_code = 0;
    private $http_info = '';

    public function __construct() {
        $this->curl = curl_init();
    }

    public function get($url, array $data = null) {
        $this->url = $url;
        if (!empty($data)) $this->data =  http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_POST, 0);
        return $this->httpRequest();
    }

    public function post($url, array $data = null) {
        $this->url = $url;
        $this->data = http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        return $this->httpRequest();
    }

    public function clearCookies() {
        $this->cookies = '';
    }

    public function setCookies($cookies) {
        $this->cookies = $cookies;
    }

    public function getHttpInfo() {
        return $this->http_info;
    }

    public function getHttpCode() {
        return $this->http_code;
    }

    public function test() {
        var_dump($this->cookies);
    }

    public function setHeader($header) {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
    }

    private function httpRequest() {
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->time_out);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->time_out);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookies);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookies);
        if (!empty($this->data)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);
        }
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        $content = curl_exec($this->curl);
        $this->http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->http_info = curl_getinfo($this->curl);
        curl_close($this->curl);
        return $content;
    }
}