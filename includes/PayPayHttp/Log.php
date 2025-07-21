<?php
namespace PayPayCheckoutSdk\PayPayHttp;

class Log {
    public function log($message) {
        error_log('[PayPay Multicaixa] ' . $message);
    }
}