<?php
namespace PayPayMulticaixa\Core;

class PayPayPayment {
    private $config;
    private $logger;

    public function __construct($config) {
        $this->config = $config;
        $this->logger = function ($message) {
            error_log('[PayPay Multicaixa] ' . $message);
        };
    }

    public function make_request($payment_data) {
        $private_key = openssl_pkey_get_private($this->config['private_key']);
        if ($private_key === false) {
            call_user_func($this->logger, 'Private key error');
            throw new \Exception('Invalid private key');
        }

        $request_data = [
            'request_no' => wp_generate_uuid4(),
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'partner_id' => $this->config['partner_id'],
            'charset' => 'UTF-8',
            'language' => $this->config['language'],
            'format' => 'JSON',
            'service' => 'paypay.multicaixa.payment', // Adjust per API docs
            'sale_product_code' => $this->config['sale_product_code'],
        ];

        $biz_content = json_encode($payment_data);
        call_user_func($this->logger, 'Biz Content: ' . wp_json_encode(wp_kses_post_deep(json_decode($biz_content, true))));
        
        $encrypted = $this->encrypt_content($biz_content, $private_key);
        $request_data['biz_content'] = base64_encode($encrypted);
        
        $sign_text = $this->format_sign_text($request_data);
        call_user_func($this->logger, 'Sign text: ' . $sign_text);
        
        $sign = $this->sign($sign_text, $private_key);
        $request_data['sign'] = $sign;
        $request_data['sign_type'] = 'RSA';

        $request_json = json_encode(array_map('urlencode', $request_data));
        call_user_func($this->logger, 'Request JSON: ' . wp_json_encode(wp_kses_post_deep(json_decode($request_json, true))));

        $response = $this->send_request($request_json);
        openssl_free_key($private_key);

        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            $result = json_decode($response['body'], true);
            if (isset($result['code']) && $result['code'] === 'S0001' && !empty($result['biz_content'])) {
                return $result['biz_content'];
            }
            throw new \Exception("API response error: {$response['body']}", $response['status_code']);
        }
        throw new \Exception("HTTP error [{$response['status_code']}] {$response['body']}", $response['status_code']);
    }

    public function handle_notification($data) {
        if (empty($data) || !is_string($data)) {
            call_user_func($this->logger, 'Invalid notification data');
            return null;
        }

        call_user_func($this->logger, 'Notify content: ' . $data);
        parse_str($data, $parsed_data);
        $sign_text = $this->format_sign_text($parsed_data);
        call_user_func($this->logger, 'Notify sign text: ' . $sign_text);
        
        $sign = $parsed_data['sign'] ?? '';
        if ($this->verify_sign($sign_text, $sign, $this->config['public_key'])) {
            call_user_func($this->logger, 'Notify sign verified: ' . wp_json_encode($parsed_data));
            return $parsed_data;
        }
        call_user_func($this->logger, 'Notify sign verification failed: ' . wp_json_encode($parsed_data));
        return null;
    }

    private function encrypt_content($content, $private_key) {
        $encrypted = '';
        $chunks = str_split($content, 117);
        foreach ($chunks as $chunk) {
            $partial = '';
            openssl_private_encrypt($chunk, $partial, $private_key);
            $encrypted .= $partial;
        }
        return $encrypted;
    }

    private function sign($text, $private_key) {
        $sign = '';
        openssl_sign($text, $sign, $private_key);
        $result = base64_encode($sign);
        openssl_free_key($private_key);
        return $result;
    }

    private function verify_sign($text, $sign, $public_key) {
        $key = openssl_pkey_get_public($public_key);
        if ($key === false) {
            call_user_func($this->logger, 'Public key error');
            return false;
        }
        $decoded_sign = base64_decode($sign);
        $result = openssl_verify($text, $decoded_sign, $key);
        openssl_free_key($key);
        return $result === 1;
    }

    private function format_sign_text($data) {
        ksort($data);
        $parts = [];
        foreach ($data as $key => $value) {
            if (is_null($value) || $key === 'sign' || $key === 'sign_type') {
                continue;
            }
            $parts[] = "$key=$value";
        }
        return implode('&', $parts);
    }

    private function send_request($json) {
        $response = wp_remote_post($this->config['api_endpoint'], [
            'body' => $json,
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            call_user_func($this->logger, 'HTTP request error: ' . $response->get_error_message());
            throw new \Exception($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        call_user_func($this->logger, "HTTP response: [$status_code] $body");

        return [
            'status_code' => $status_code,
            'body' => $body,
        ];
    }
}