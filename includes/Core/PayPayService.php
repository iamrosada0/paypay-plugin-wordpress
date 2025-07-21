<?php
namespace PayPayCheckoutSdk\Core;

use PayPayCheckoutSdk\Biz\BizContentBase;
use PayPayCheckoutSdk\Biz\CreatePaymentBiz;
use PayPayCheckoutSdk\Config\PayPayConfig;
use PayPayCheckoutSdk\PayPayHttp\HttpClient;
use PayPayCheckoutSdk\PayPayHttp\HttpException;
use PayPayCheckoutSdk\PayPayHttp\HttpRequest;
use PayPayCheckoutSdk\PayPayHttp\Log;

class PayPayService
{
    private $paypayConfig;
    private $logger;

    function __construct(PayPayConfig $paypayConfig, Log $logger = null)
    {
        $this->paypayConfig = $paypayConfig;
        $this->logger = $logger ?? new Log();
    }

    public function makeRequest(BizContentBase $content) {
        $privateKey = openssl_pkey_get_private($this->paypayConfig->getMyPrivateKey());
        if ($privateKey === false) {
            $this->logger->log("Your private key error!");
            throw new APIException("Your private key error.", 0);
        }

        $payPayRequestBase = $this->createPayPayRequestBase();
        $bizContent = $content->parseJson();
        $this->logger->log("Biz Content: " . wp_json_encode(wp_kses_post_deep(json_decode($bizContent, true))));
        $encryptedBase64 = $this->encryptBizContentWithPrivateKey($bizContent, $privateKey);
        $payPayRequestBase->setBizContent($encryptedBase64);
        $payPayRequestBase->setService($content::apiService());
        $beforeSignText = $this->formatSignOriText($payPayRequestBase->getParams());
        $this->logger->log("Sign ori text: " . $beforeSignText);
        $sign = $this->signWithPrivateKey($beforeSignText, $privateKey);
        $payPayRequestBase->setSign($sign);
        $payPayRequestBase->setSignType("RSA");
        $requestJson = $this->createRequestJsonWithUrlEncode($payPayRequestBase->getParams());
        $this->logger->log("Request json: " . wp_json_encode(wp_kses_post_deep(json_decode($requestJson, true))));

        $httpClient = new HttpClient($this->logger);
        $request = new HttpRequest($this->paypayConfig->getEndPoint(), "POST", $requestJson);
        $httpResponse = $httpClient->execute($request);
        openssl_free_key($privateKey);

        if ($httpResponse->statusCode >= 200 && $httpResponse->statusCode < 300) {
            $json = $httpResponse->result;
            $result = json_decode($json, true);
            if ($result["code"] == "S0001" && !empty($result["biz_content"])) {
                return $result["biz_content"];
            } else {
                throw new APIException("API response: [{$json}]", $httpResponse->statusCode);
            }
        } else {
            throw new APIException("API Error [{$httpResponse->statusCode}] {$httpResponse->result}", $httpResponse->statusCode);
        }
    }

    public function handleNotify($urlFormDataString) {
        if (empty($urlFormDataString) || !is_string($urlFormDataString)) {
            $this->logger->log("Invalid notify content");
            return null;
        }

        $this->logger->log("Notify content: " . $urlFormDataString);
        $data = null;
        parse_str($urlFormDataString, $data);
        $beforeSignText = $this->formatSignOriText($data);
        $this->logger->log("Notify sign ori text: " . $beforeSignText);
        $sign = $data['sign'] ?? '';
        if ($this->verifySignWithPublicKey($beforeSignText, $sign, $this->paypayConfig->getPayPayPublicKey())) {
            $this->logger->log("Notify sign verify success: " . wp_json_encode($data));
            return $data;
        } else {
            $this->logger->log("Notify sign verify fail: " . wp_json_encode($data));
            return null;
        }
    }

    private function createRequestJsonWithUrlEncode($map) {
        $params = [];
        foreach ($map as $k => $v) {
            $params[$k] = urlencode($v);
        }
        return json_encode($params);
    }

    private function signWithPrivateKey($plainText, $privateKey) {
        $sign = "";
        openssl_sign($plainText, $sign, $privateKey);
        $result = base64_encode($sign);
        openssl_free_key($privateKey);
        return $result;
    }

    private function formatSignOriText($map) {
        ksort($map);
        $arr = [];
        foreach ($map as $k => $v) {
            if (is_null($v) || $k == "sign" || $k == "sign_type") {
                continue;
            }
            $arr[] = "{$k}={$v}";
        }
        return implode("&", $arr);
    }

    private function verifySignWithPublicKey($plainText, $sign, $publicKey) {
        $key = openssl_pkey_get_public($publicKey);
        if ($key === false) {
            $this->logger->log("Public key error!!");
            return false;
        }
        $oriSign = base64_decode($sign);
        $ret = openssl_verify($plainText, $oriSign, $key);
        openssl_free_key($key);
        $this->logger->log("Sign verify result: " . $ret);
        return $ret === 1;
    }

    private function encryptBizContentWithPrivateKey($json, $privateKey) {
        $ret = '';
        $strArr = str_split($json, 117);
        foreach ($strArr as $chunk) {
            $partial = '';
            openssl_private_encrypt($chunk, $partial, $privateKey);
            $ret .= $partial;
        }
        openssl_free_key($privateKey);
        return base64_encode($ret);
    }

    private function createPayPayRequestBase() {
        $requestBase = new \PayPayCheckoutSdk\Models\PayPayRequestBase();
        $requestBase->setRequestNo(wp_generate_uuid4());
        $requestBase->setTimestamp(date("Y-m-d H:i:s"));
        $requestBase->setVersion("1.0");
        $requestBase->setPartnerId($this->paypayConfig->getPartnerId());
        $requestBase->setCharset("UTF-8");
        $requestBase->setLanguage($this->paypayConfig->getLang());
        $requestBase->setFormat("JSON");
        return $requestBase;
    }
}