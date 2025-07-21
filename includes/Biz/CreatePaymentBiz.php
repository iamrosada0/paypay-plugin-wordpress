<?php
namespace PayPayCheckoutSdk\Biz;

class CreatePaymentBiz extends BizContentBase {
    public function setOrderId($orderId) {
        $this->data['order_id'] = $orderId;
    }

    public function setAmount($amount) {
        $this->data['amount'] = $amount;
    }

    public function setDescription($description) {
        $this->data['description'] = $description;
    }
}