<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add AJAX endpoints for frontend and backend
add_action('wp_ajax_create_paypay_payment', 'paypay_create_payment');
add_action('wp_ajax_nopriv_create_paypay_payment', 'paypay_create_payment');
add_action('wp_ajax_create_paypay_app_payment', 'paypay_create_app_payment');
add_action('wp_ajax_nopriv_create_paypay_app_payment', 'paypay_create_app_payment');

function paypay_create_payment() {
    // Verify nonce
    check_ajax_referer('paypay_payment_nonce', 'nonce');

    // Get settings
    $partner_id = get_option('paypay_partner_id');
    $private_key = get_option('paypay_private_key');
    $sale_product_code = get_option('paypay_sale_product_code');
    $api_url = get_option('paypay_api_url', 'https://testgateway.zsaipay.com:18202/gateway/recv.do');

    // Validate inputs
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $out_trade_no = (string)time();

    if ($amount <= 0 || !preg_match('/^\d{9}$/', $phone)) {
        wp_send_json_error(['message' => __('Invalid amount or phone number', 'paypay-multicaixa')]);
    }

    if (!$partner_id || !$private_key || !$sale_product_code) {
        wp_send_json_error(['message' => __('Credentials not configured', 'paypay-multicaixa')]);
    }

    // Build biz_content for Multicaixa Express
    $biz_content = [
        'cashier_type' => 'SDK',
        'payer_ip' => '123.25.68.9',
        'sale_product_code' => $sale_product_code,
        'timeout_express' => '15m',
        'trade_info' => [
            'currency' => 'AOA',
            'out_trade_no' => $out_trade_no,
            'payee_identity' => $partner_id,
            'payee_identity_type' => '1',
            'price' => number_format($amount, 2, '.', ''),
            'quantity' => '1',
            'subject' => __('WordPress Payment', 'paypay-multicaixa'),
            'total_amount' => number_format($amount, 2, '.', '')
        ],
        'pay_method' => [
            'pay_product_code' => '31',
            'amount' => number_format($amount, 2, '.', ''),
            'bank_code' => 'MUL',
            'phone_num' => $phone
        ]
    ];

    $biz_content_str = json_encode($biz_content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Encrypt biz_content
    $encrypted_biz_content = encrypt_biz_content($biz_content_str, $private_key);

    // Build request parameters
    $params = [
        'charset' => 'UTF-8',
        'biz_content' => $encrypted_biz_content,
        'partner_id' => $partner_id,
        'service' => 'instant_trade',
        'request_no' => uniqid(),
        'format' => 'JSON',
        'sign_type' => 'RSA',
        'version' => '1.0',
        'timestamp' => gmdate('Y-m-d H:i:s', time() + 3600), // GMT+1
        'language' => 'pt'
    ];

    // Generate signature
    $params['sign'] = generate_rsa_signature($params, $private_key);

    // Encode parameters
    $encoded_params = [];
    foreach (array_keys($params) as $key) {
        $encoded_params[$key] = urlencode($params[$key]);
    }

    // Send to PayPay API
    $response = wp_remote_post($api_url, [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => http_build_query($encoded_params)
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || !is_array($body)) {
        wp_send_json_error(['message' => __('Invalid API response', 'paypay-multicaixa')]);
    }

    if (isset($body['code']) && $body['code'] === 'S0001' && isset($body['biz_content']['status']) && $body['biz_content']['status'] === 'P') {
        wp_send_json_success([
            'dynamic_link' => $body['biz_content']['dynamic_link'],
            'trade_token' => $body['biz_content']['trade_token'],
            'out_trade_no' => $body['biz_content']['out_trade_no'],
            'inner_trade_no' => $body['biz_content']['trade_no'],
            'total_amount' => floatval($body['biz_content']['total_amount'] ?? $amount),
            'return_url' => $body['biz_content']['dynamic_link']
        ]);
    } else {
        wp_send_json_error(['message' => $body['sub_msg'] ?? __('Payment failed', 'paypay-multicaixa')]);
    }
}

function paypay_create_app_payment() {
    // Verify nonce
    check_ajax_referer('paypay_payment_nonce', 'nonce');

    // Get settings
    $partner_id = get_option('paypay_partner_id');
    $private_key = get_option('paypay_private_key');
    $sale_product_code = get_option('paypay_sale_product_code');
    $api_url = get_option('paypay_api_url', 'https://testgateway.zsaipay.com:18202/gateway/recv.do');

    // Validate inputs
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : __('WordPress Payment', 'paypay-multicaixa');
    $out_trade_no = (string)time();

    if ($amount <= 0) {
        wp_send_json_error(['message' => __('Invalid amount', 'paypay-multicaixa')]);
    }

    if (!$partner_id || !$private_key || !$sale_product_code) {
        wp_send_json_error(['message' => __('Credentials not configured', 'paypay-multicaixa')]);
    }

    // Build biz_content for PayPay App
    $biz_content = [
        'cashier_type' => 'SDK',
        'payer_ip' => '123.25.68.9',
        'sale_product_code' => $sale_product_code,
        'timeout_express' => '15m',
        'trade_info' => [
            'currency' => 'AOA',
            'out_trade_no' => $out_trade_no,
            'payee_identity' => $partner_id,
            'payee_identity_type' => '1',
            'price' => number_format($amount, 2, '.', ''),
            'quantity' => '1',
            'subject' => $subject,
            'total_amount' => number_format($amount, 2, '.', '')
        ]
    ];

    $biz_content_str = json_encode($biz_content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Encrypt biz_content
    $encrypted_biz_content = encrypt_biz_content($biz_content_str, $private_key);

    // Build request parameters
    $params = [
        'charset' => 'UTF-8',
        'biz_content' => $encrypted_biz_content,
        'partner_id' => $partner_id,
        'service' => 'instant_trade',
        'request_no' => uniqid(),
        'format' => 'JSON',
        'sign_type' => 'RSA',
        'version' => '1.0',
        'timestamp' => gmdate('Y-m-d H:i:s', time() + 3600), // GMT+1
        'language' => 'pt'
    ];

    // Generate signature
    $params['sign'] = generate_rsa_signature($params, $private_key);

    // Encode parameters
    $encoded_params = [];
    foreach (array_keys($params) as $key) {
        $encoded_params[$key] = urlencode($params[$key]);
    }

    // Send to PayPay API
    $response = wp_remote_post($api_url, [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => http_build_query($encoded_params)
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body) || !is_array($body)) {
        wp_send_json_error(['message' => __('Invalid API response', 'paypay-multicaixa')]);
    }

    if (isset($body['code']) && $body['code'] === 'S0001' && isset($body['biz_content']['status']) && $body['biz_content']['status'] === 'P') {
        wp_send_json_success([
            'dynamic_link' => $body['biz_content']['dynamic_link'],
            'trade_token' => $body['biz_content']['trade_token'],
            'out_trade_no' => $body['biz_content']['out_trade_no'],
            'inner_trade_no' => $body['biz_content']['trade_no'],
            'total_amount' => floatval($body['biz_content']['total_amount'] ?? $amount),
            'return_url' => $body['biz_content']['dynamic_link']
        ]);
    } else {
        wp_send_json_error(['message' => $body['sub_msg'] ?? __('Payment failed', 'paypay-multicaixa')]);
    }
}

// Helper: Encrypt biz_content with private key
function encrypt_biz_content($data, $private_key_pem) {
    $private_key = openssl_pkey_get_private($private_key_pem);
    if ($private_key === false) {
        wp_send_json_error(['message' => __('Invalid private key', 'paypay-multicaixa')]);
    }
    $chunk_size = 117;
    $output = '';
    foreach (str_split($data, $chunk_size) as $chunk) {
        if (!openssl_private_encrypt($chunk, $encrypted_chunk, $private_key, OPENSSL_PKCS1_PADDING)) {
            wp_send_json_error(['message' => __('Encryption failed', 'paypay-multicaixa')]);
        }
        $output .= $encrypted_chunk;
    }
    return base64_encode($output);
}

// Helper: Generate SHA1withRSA signature
function generate_rsa_signature($params, $private_key_pem) {
    $private_key = openssl_pkey_get_private($private_key_pem);
    if ($private_key === false) {
        wp_send_json_error(['message' => __('Invalid private key', 'paypay-multicaixa')]);
    }
    ksort($params);
    $data_to_sign = [];
    foreach ($params as $key => $value) {
        if ($key === 'sign' || $key === 'sign_type' || is_null($value)) {
            continue;
        }
        $data_to_sign[] = "{$key}={$value}";
    }
    $data_to_sign = implode('&', $data_to_sign);
    if (!openssl_sign($data_to_sign, $signature, $private_key, OPENSSL_ALGO_SHA1)) {
        wp_send_json_error(['message' => __('Signature generation failed', 'paypay-multicaixa')]);
    }
    return base64_encode($signature);
}