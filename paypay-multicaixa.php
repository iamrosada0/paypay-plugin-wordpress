<?php
/*
 * Plugin Name: PayPay Multicaixa Payment Gateway
 * Plugin URI: https://yourwebsite.com/paypay-multicaixa
 * Description: Integrates PayPay Multicaixa payment system with WordPress for custom payments.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * Text Domain: paypay-multicaixa
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PAYPAY_MULTICAIXA_DIR', plugin_dir_path(__FILE__));
define('PAYPAY_MULTICAIXA_URL', plugin_dir_url(__FILE__));

// Load dependencies
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Config/PayPayConfig.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Core/PayPayService.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/PayPayHttp/HttpClient.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/PayPayHttp/HttpException.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/PayPayHttp/HttpRequest.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/PayPayHttp/Log.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Biz/BizContentBase.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Biz/CreatePaymentBiz.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Models/PayPayRequestBase.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Exceptions/APIException.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Admin/PayPayAdmin.php';
require_once PAYPAY_MULTICAIXA_DIR . 'includes/Frontend/PayPayFrontend.php';

// Initialize plugin
class PayPayMulticaixaPlugin {
    public function __construct() {
        // Load text domain for translations
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        // Initialize admin settings
        add_action('admin_init', ['PayPayCheckoutSdk\Admin\PayPayAdmin', 'init']);
        // Initialize frontend functionality
        add_action('init', ['PayPayCheckoutSdk\Frontend\PayPayFrontend', 'init']);
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
}

register_activation_hook(__FILE__, ['PayPayMulticaixaPlugin', 'activate']);

new PayPayMulticaixaPlugin();