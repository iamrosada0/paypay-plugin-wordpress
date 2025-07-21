<?php
namespace PayPayCheckoutSdk\Models;

class PayPayRequestBase {
    private $params = [];

    public function setRequestNo($requestNo) {
        $this->params['request_no'] = $requestNo;
    }

    public function setTimestamp($timestamp) {
        $this->params['timestamp'] = $timestamp;
    }

    public function setVersion($version) {
        $this->params['version'] = $version;
    }

    public function setPartnerId($partnerId) {
        $this->params['partner_id'] = $partnerId;
    }

    public function setCharset($charset) {
        $this->params['charset'] = $charset;
    }

    public function setLanguage($language) {
        $this->params['language'] = $language;
    }

    public function setFormat($format) {
        $this->params['format'] = $format;
    }

    public function setBizContent($bizContent) {
        $this->params['biz_content'] = $bizContent;
    }

    public function setSign($sign) {
        $this->params['sign'] = $sign;
    }

    public function setSignType($signType) {
        $this->params['sign_type'] = $signType;
    }

    public function getParams() {
        return $this->params;
    }
}