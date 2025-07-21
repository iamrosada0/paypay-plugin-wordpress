<?php
namespace PayPayCheckoutSdk\PayPayHttp;

class HttpRequest {
    private $url;
    private $method;
    private $data;

    public function __construct($url, $method, $data) {
        $this->url = $url;
        $this->method = $method;
        $this->data = $data;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getData() {
        return $this->data;
    }
}