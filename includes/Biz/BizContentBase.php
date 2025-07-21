<?php
namespace PayPayCheckoutSdk\Biz;

abstract class BizContentBase {
    protected $data = [];

    public function parseJson() {
        return json_encode($this->data);
    }

    public static function apiService() {
        return 'paypay.multicaixa.payment'; // Adjust based on PayPay API docs
    }
}