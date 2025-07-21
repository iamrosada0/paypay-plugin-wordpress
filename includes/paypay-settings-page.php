<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add settings page to WordPress menu
add_action('admin_menu', function () {
    add_options_page(
        __('PayPay Settings', 'paypay-multicaixa'),
        __('PayPay Multicaixa', 'paypay-multicaixa'),
        'manage_options',
        'paypay-config',
        'paypay_render_settings_page'
    );
});

// Render the settings page HTML
function paypay_render_settings_page() {
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

// Register settings and fields
add_action('admin_init', function () {
    // Register settings with sanitization
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

    // Add settings section
    add_settings_section(
        'paypay_main',
        __('Main Settings', 'paypay-multicaixa'),
        function () {
            echo '<p>' . __('Enter your PayPay API credentials to enable Multicaixa Express payments. Contact PayPay Africa for details.', 'paypay-multicaixa') . '</p>';
        },
        'paypay-config'
    );

    // Add settings fields
    add_settings_field('paypay_partner_id', __('Partner ID', 'paypay-multicaixa'), function () {
        echo '<input type="text" name="paypay_partner_id" value="' . esc_attr(get_option('paypay_partner_id')) . '" class="regular-text">';
    }, 'paypay-config', 'paypay_main');

    add_settings_field('paypay_private_key', __('Private Key (PEM)', 'paypay-multicaixa'), function () {
        echo '<textarea name="paypay_private_key" rows="10" class="large-text code">' . esc_textarea(get_option('paypay_private_key')) . '</textarea>';
    }, 'paypay-config', 'paypay_main');

    add_settings_field('paypay_public_key', __('PayPay Public Key', 'paypay-multicaixa'), function () {
        echo '<textarea name="paypay_public_key" rows="10" class="large-text code">' . esc_textarea(get_option('paypay_public_key')) . '</textarea>';
    }, 'paypay-config', 'paypay_main');

    add_settings_field('paypay_sale_product_code', __('Product Code', 'paypay-multicaixa'), function () {
        echo '<input type="text" name="paypay_sale_product_code" value="' . esc_attr(get_option('paypay_sale_product_code')) . '" class="regular-text">';
    }, 'paypay-config', 'paypay_main');

    add_settings_field('paypay_api_url', __('API URL', 'paypay-multicaixa'), function () {
        echo '<input type="url" name="paypay_api_url" value="' . esc_attr(get_option('paypay_api_url', 'https://testgateway.zsaipay.com:18202/gateway/recv.do')) . '" class="regular-text">';
    }, 'paypay-config', 'paypay_main');
});