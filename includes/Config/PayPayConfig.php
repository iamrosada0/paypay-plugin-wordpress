<?php
namespace PayPayCheckoutSdk\Config;

class PayPayConfig {
    private $endPoint;
    private $partnerId;
    private $privateKey;
    private $publicKey;
    private $lang;

    public function setEndPoint($endPoint) {
        $this->endPoint = $endPoint;
    }

    public function getEndPoint() {
        return $this->endPoint;
    }

    public function setPartnerId($partnerId) {
        $this->partnerId = $partnerId;
    }

    public function getPartnerId() {
        return $this->partnerId;
    }

    public function setMyPrivateKey($privateKey) {
        $this->privateKey = $privateKey;
    }

    public function getMyPrivateKey() {
        return $this->privateKey;
    }

    public function setPayPayPublicKey($publicKey) {
        $this->publicKey = $publicKey;
    }

    public function getPayPayPublicKey() {
        return $this->publicKey;
    }

    public function setLang($lang) {
        $this->lang = $lang;
    }

    public function getLang() {
        return $this->lang;
    }
}