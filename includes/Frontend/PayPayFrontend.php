<?php
namespace PayPayMulticaixa\Frontend;

use PayPayMulticaixa\Core\PayPayPayment;

class PayPayFrontend {
    public static function init() {
        add_shortcode('paypay_payment_form', [__CLASS__, 'render_payment_form']);
        add_action('wp', [__CLASS__, 'handle_payment_submission']);
        add_action('wp', [__CLASS__, 'handle_payment_notification']);
        // Add AJAX handler from old code
        add_action('wp_ajax_paypay_process_payment', [__CLASS__, 'handle_payment_submission']);
        add_action('wp_ajax_nopriv_paypay_process_payment', [__CLASS__, 'handle_payment_submission']);
        // Enqueue scripts from old code
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    public static function enqueue_scripts() {
        wp_enqueue_script('paypay-frontend', PAYPAY_MULTICAIXA_URL . 'assets/paypay.js', ['jquery'], '1.0.1', true);
        wp_localize_script('paypay-frontend', 'PayPayData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('paypay_payment_nonce')
        ]);
    }

    public static function render_payment_form($atts) {
        $atts = shortcode_atts(['amount' => ''], $atts, 'paypay_payment_form');
        ob_start();
        ?>
        <form id="paypay-form" method="post">
            <p>
                <label for="paypay_amount"><?php _e('Amount (AOA)', 'paypay-multicaixa'); ?></label>
                <input type="number" id="paypay_amount" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" step="0.01" min="1" required>
            </p>
            <p>
                <label for="paypay_phone"><?php _e('Phone Number', 'paypay-multicaixa'); ?></label>
                <input type="text" id="paypay_phone" name="phone" pattern="\d{9}" placeholder="923123456" required>
            </p>
            <input type="hidden" name="action" value="paypay_process_payment">
            <?php wp_nonce_field('paypay_payment_nonce', 'paypay_nonce'); ?>
            <button type="submit" id="paypay-submit"><?php _e('Pay Now', 'paypay-multicaixa'); ?></button>
        </form>
        <div id="paypay-message"></div>
        <?php
        return ob_get_clean();
    }

    public static function handle_payment_submission() {
        if (!isset($_POST['paypay_nonce']) || !wp_verify_nonce($_POST['paypay_nonce'], 'paypay_payment_nonce')) {
            wp_send_json_error(['message' => __('Invalid nonce', 'paypay-multicaixa')]);
            return;
        }

        $amount = floatval($_POST['amount']);
        $phone = sanitize_text_field($_POST['phone']);
        $description = sanitize_text_field($_POST['description'] ?? 'Payment via Multicaixa Express');

        $config = [
            'api_endpoint' => get_option('paypay_api_url', 'https://testgateway.zsaipay.com:18202/gateway/recv.do'),
            'partner_id' => get_option('paypay_partner_id'),
            'private_key' => get_option('paypay_private_key'),
            'public_key' => get_option('paypay_public_key'),
            'language' => get_option('paypay_language', 'en'),
            'sale_product_code' => get_option('paypay_sale_product_code'),
        ];

        $payment = new PayPayPayment($config);
        $payment_data = [
            'order_id' => uniqid('paypay_'),
            'amount' => $amount,
            'phone' => $phone,
            'description' => $description,
            'sale_product_code' => $config['sale_product_code'],
        ];

        try {
            $result = $payment->make_request($payment_data);
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'paypay_transactions',
                [
                    'order_id' => $payment_data['order_id'],
                    'status' => 'pending',
                    'amount' => $amount,
                    'created_at' => current_time('mysql'),
                ],
                ['%s', '%s', '%f', '%s']
            );
            wp_send_json_success(['message' => __('Payment initiated successfully', 'paypay-multicaixa'), 'redirect' => add_query_arg('paypay_status', 'success', home_url('/payment-confirmation'))]);
        } catch (\Exception $e) {
            error_log('PayPay Payment Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Payment failed: ' . $e->getMessage(), 'paypay-multicaixa')]);
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

        $config = [
            'public_key' => get_option('paypay_public_key'),
        ];

        $payment = new PayPayPayment($config);
        $result = $payment->handle_notification($raw_post);
        if ($result) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'paypay_transactions',
                [
                    'order_id' => sanitize_text_field($result['order_id'] ?? 'unknown'),
                    'status' => sanitize_text_field($result['status'] ?? 'pending'),
                    'amount' => floatval($result['amount'] ?? 0),
                    'created_at' => current_time('mysql'),
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