<?php
/**
 * Plugin Name: PayPay Multicaixa Express
 * Description: Plugin de pagamento via Multicaixa Express pela PayPay.
 * Version: 1.0.1
 * Author: Luis R.
 * Text Domain: paypay-multicaixa
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load text domain for translations
add_action('plugins_loaded', function () {
    load_plugin_textdomain('paypay-multicaixa', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Include files with error checking
$includes = [
    plugin_dir_path(__FILE__) . 'includes/paypay-settings-page.php',
    plugin_dir_path(__FILE__) . 'includes/paypay-api-handler.php'
];
foreach ($includes as $file) {
    if (!file_exists($file)) {
        wp_die(sprintf(__('Missing plugin file: %s', 'paypay-multicaixa'), esc_html(basename($file))));
    }
    require_once $file;
}

// Enqueue JS for frontend AJAX
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('paypay-frontend', plugin_dir_url(__FILE__) . 'assets/paypay.js', ['jquery'], '1.0.1', true);
    wp_localize_script('paypay-frontend', 'PayPayData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('paypay_payment_nonce')
    ]);
});

// Shortcode for payment form
add_shortcode('paypay_payment_form', function () {
    ob_start();
    ?>
    <form id="paypay-form">
        <label for="amount"><?php _e('Amount (Kz):', 'paypay-multicaixa'); ?></label>
        <input type="number" id="amount" name="amount" required min="1" step="0.01"><br>

        <label for="payment_method"><?php _e('Escolha o método de pagamento:', 'paypay-multicaixa'); ?></label>
        <select id="payment_method" name="payment_method" required>
            <option value="MULTICAIXA_EXPRESS"><?php _e('Multicaixa Express', 'paypay-multicaixa'); ?></option>
            <option value="PAYPAY_APP"><?php _e('PayPay App', 'paypay-multicaixa'); ?></option>
        </select><br>

        <div id="phone-group">
            <label for="phone"><?php _e('Phone Number:', 'paypay-multicaixa'); ?></label>
            <input type="text" id="phone" name="phone" pattern="\d{9}" placeholder="923123456"><br>
        </div>

        <button type="submit" id="paypay-submit"><?php _e('Pay Now', 'paypay-multicaixa'); ?></button>
    </form>
    <div id="paypay-message"></div>
    <div id="paypay-modal-content"></div>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const methodSelect = document.getElementById("payment_method");
        const phoneGroup = document.getElementById("phone-group");

        methodSelect.addEventListener("change", function () {
          if (this.value === "MULTICAIXA_EXPRESS") {
            phoneGroup.style.display = "block";
          } else {
            phoneGroup.style.display = "none";
          }
        });
      });
    </script>
    <?php
    return ob_get_clean();
});
