<?php
namespace PayPayCheckoutSdk\Frontend;

use PayPayCheckoutSdk\Config\PayPayConfig;
use PayPayCheckoutSdk\Core\PayPayService;
use PayPayCheckoutSdk\Biz\CreatePaymentBiz;

class PayPayFrontend {
    public static function init() {
        add_shortcode('paypay_multicaixa_form', [__CLASS__, 'render_payment_form']);
        add_action('wp', [__CLASS__, 'handle_payment_submission']);
        add_action('wp', [__CLASS__, 'handle_payment_notification']);
    }

    public static function render_payment_form($atts) {
        $atts = shortcode_atts(['amount' => ''], $atts, 'paypay_multicaixa_form');
        ob_start();
        include PAYPAY_MULTICAIXA_DIR . 'templates/payment-form.php';
        return ob_get_clean();
    }

    public static function handle_payment_submission() {
        if (!isset($_POST['paypay_multicaixa_submit']) || !wp_verify_nonce($_POST['paypay_nonce'], 'paypay_multicaixa_payment')) {
            return;
        }

        $amount = floatval($_POST['amount']);
        $description = sanitize_text_field($_POST['description']);

        $options = get_option('paypay_multicaixa_options', []);
        $config = new PayPayConfig();
        $config->setEndPoint($options['api_endpoint']);
        $config->setPartnerId($options['partner_id']);
        $config->setMyPrivateKey($options['private_key']);
        $config->setPayPayPublicKey($options['public_key']);
        $config->setLang($options['language']);

        $service = new PayPayService($config);
        $payment = new CreatePaymentBiz();
        $payment->setOrderId(uniqid('paypay_'));
        $payment->setAmount($amount);
        $payment->setDescription($description);

        try {
            $result = $service->makeRequest($payment);
            wp_redirect(add_query_arg('paypay_status', 'success', home_url('/payment-confirmation')));
            exit;
        } catch (\Exception $e) {
            error_log('PayPay Payment Error: ' . $e->getMessage());
            wp_redirect(add_query_arg('paypay_status', 'error', home_url('/payment-confirmation')));
            exit;
        }
    }

    public static function handle_payment_notification() {
        if (!isset($_GET['paypay_notify']) || $_GET['paypay_notify'] !== 'callback') {
            return;
        }

        $raw_post = file_get_contents('php://input');
        if (empty($raw_post)) {
            wp_die('Invalid notification data');
        }

        $options = get_option('paypay_multicaixa_options', []);
        $config = new PayPayConfig();
        $config->setPayPayPublicKey($options['public_key']);
        $service = new PayPayService($config);

        $result = $service->handleNotify($raw_post);
        if ($result) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'paypay_transactions',
                [
                    'order_id' => sanitize_text_field($result['order_id'] ?? 'unknown'),
                    'status' => sanitize_text_field($result['status'] ?? 'pending'),
                    'amount' => floatval($result['amount'] ?? 0),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%f', '%s']
            );
            http_response_code(200);
            echo 'SUCCESS';
        } else {
            http_response_code(400);
            echo 'FAIL';
        }
        exit;
    }
}