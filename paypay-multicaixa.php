<?php
/*
 * Plugin Name: PayPay Multicaixa Payment Gateway
 * Plugin URI: https://yourwebsite.com/paypay-multicaixa
 * Description: A WordPress plugin for processing payments via PayPay Multicaixa, inspired by the PayPay SDK.
 * Version: 1.0.1
 * Author: Luis R.
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * Text Domain: paypay-multicaixa
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PAYPAY_MULTICAIXA_DIR', plugin_dir_path(__FILE__));
define('PAYPAY_MULTICAIXA_URL', plugin_dir_url(__FILE__));

// Load dependencies with error checking
$includes = [
    PAYPAY_MULTICAIXA_DIR . 'includes/Frontend/PayPayFrontend.php',
    PAYPAY_MULTICAIXA_DIR . 'includes/Core/PayPayPayment.php',
];
foreach ($includes as $file) {
    if (!file_exists($file)) {
        wp_die(sprintf(__('Missing plugin file: %s', 'paypay-multicaixa'), esc_html(basename($file))));
    }
    require_once $file;
}

class PayPayMulticaixaPlugin {
    public function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        // Add settings page (old code)
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        // Initialize frontend
        add_action('init', ['PayPayMulticaixa\Frontend\PayPayFrontend', 'init']);
    }

    public function load_textdomain() {
        load_plugin_textdomain('paypay-multicaixa', false, basename(dirname(__FILE__)) . '/languages');
    }

    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paypay_transactions';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id VARCHAR(100) NOT NULL,
            status VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function add_settings_page() {
        add_options_page(
            __('PayPay Settings', 'paypay-multicaixa'),
            __('PayPay Multicaixa', 'paypay-multicaixa'),
            'manage_options',
            'paypay-config',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('PayPay Settings', 'paypay-multicaixa'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('paypay_settings');
                do_settings_sections('paypay-config');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('paypay_settings', 'paypay_partner_id', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('paypay_settings', 'paypay_private_key', [
            'sanitize_callback' => function ($value) {
                if (!preg_match('/-----BEGIN (RSA )?PRIVATE KEY-----.*-----END (RSA )?PRIVATE KEY-----/s', $value)) {
                    add_settings_error('paypay_settings', 'invalid_private_key', __('Invalid private key format', 'paypay-multicaixa'));
                    return get_option('paypay_private_key');
                }
                return $value;
            }
        ]);
        register_setting('paypay_settings', 'paypay_public_key', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('paypay_settings', 'paypay_sale_product_code', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('paypay_settings', 'paypay_api_url', ['sanitize_callback' => 'esc_url_raw']);
        register_setting('paypay_settings', 'paypay_language', [
            'sanitize_callback' => function ($value) {
                return in_array($value, ['en', 'pt']) ? $value : 'en';
            }
        ]);

        add_settings_section(
            'paypay_main',
            __('Main Settings', 'paypay-multicaixa'),
            function () {
                echo '<p>' . __('Enter your PayPay API credentials to enable Multicaixa Express payments. Contact PayPay Africa for details.', 'paypay-multicaixa') . '</p>';
            },
            'paypay-config'
        );

        add_settings_field('paypay_partner_id', __('Partner ID', 'paypay-multicaixa'), function () {
            echo '<input type="text" name="paypay_partner_id" value="' . esc_attr(get_option('paypay_partner_id')) . '" class="regular-text" required>';
        }, 'paypay-config', 'paypay_main');

        add_settings_field('paypay_private_key', __('Private Key (PEM)', 'paypay-multicaixa'), function () {
            echo '<textarea name="paypay_private_key" rows="10" class="large-text code" required>' . esc_textarea(get_option('paypay_private_key')) . '</textarea>';
        }, 'paypay-config', 'paypay_main');

        add_settings_field('paypay_public_key', __('PayPay Public Key', 'paypay-multicaixa'), function () {
            echo '<textarea name="paypay_public_key" rows="10" class="large-text code" required>' . esc_textarea(get_option('paypay_public_key')) . '</textarea>';
        }, 'paypay-config', 'paypay_main');

        add_settings_field('paypay_sale_product_code', __('Product Code', 'paypay-multicaixa'), function () {
            echo '<input type="text" name="paypay_sale_product_code" value="' . esc_attr(get_option('paypay_sale_product_code')) . '" class="regular-text" required>';
        }, 'paypay-config', 'paypay_main');

        add_settings_field('paypay_api_url', __('API URL', 'paypay-multicaixa'), function () {
            echo '<input type="url" name="paypay_api_url" value="' . esc_attr(get_option('paypay_api_url', 'https://testgateway.zsaipay.com:18202/gateway/recv.do')) . '" class="regular-text" required>';
        }, 'paypay-config', 'paypay_main');

        add_settings_field('paypay_language', __('Language', 'paypay-multicaixa'), function () {
            $value = get_option('paypay_language', 'en');
            echo '<select name="paypay_language">';
            echo '<option value="en"' . selected($value, 'en', false) . '>English</option>';
            echo '<option value="pt"' . selected($value, 'pt', false) . '>Portuguese</option>';
            echo '</select>';
        }, 'paypay-config', 'paypay_main');
    }
}

register_activation_hook(__FILE__, ['PayPayMulticaixaPlugin', 'activate']);

new PayPayMulticaixaPlugin();