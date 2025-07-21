<?php
namespace PayPayMulticaixa\Admin;

class PayPayAdmin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_settings_page() {
        add_options_page(
            __('PayPay Multicaixa Settings', 'paypay-multicaixa'),
            __('PayPay Multicaixa', 'paypay-multicaixa'),
            'manage_options',
            'paypay-multicaixa',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('paypay_multicaixa_settings', 'paypay_multicaixa_options', [
            'sanitize_callback' => [__CLASS__, 'sanitize_settings']
        ]);

        add_settings_section(
            'paypay_multicaixa_main',
            __('PayPay Multicaixa Configuration', 'paypay-multicaixa'),
            null,
            'paypay-multicaixa'
        );

        add_settings_field(
            'api_endpoint',
            __('API Endpoint', 'paypay-multicaixa'),
            [__CLASS__, 'render_api_endpoint_field'],
            'paypay-multicaixa',
            'paypay_multicaixa_main'
        );

        add_settings_field(
            'partner_id',
            __('Partner ID', 'paypay-multicaixa'),
            [__CLASS__, 'render_partner_id_field'],
            'paypay-multicaixa',
            'paypay_multicaixa_main'
        );

        add_settings_field(
            'private_key',
            __('Private Key', 'paypay-multicaixa'),
            [__CLASS__, 'render_private_key_field'],
            'paypay-multicaixa',
            'paypay_multicaixa_main'
        );

        add_settings_field(
            'public_key',
            __('PayPay Public Key', 'paypay-multicaixa'),
            [__CLASS__, 'render_public_key_field'],
            'paypay-multicaixa',
            'paypay_multicaixa_main'
        );

        add_settings_field(
            'language',
            __('Language', 'paypay-multicaixa'),
            [__CLASS__, 'render_language_field'],
            'paypay-multicaixa',
            'paypay_multicaixa_main'
        );
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('PayPay Multicaixa Settings', 'paypay-multicaixa'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('paypay_multicaixa_settings');
                do_settings_sections('paypay-multicaixa');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function render_api_endpoint_field() {
        $options = get_option('paypay_multicaixa_options', []);
        $value = isset($options['api_endpoint']) ? esc_attr($options['api_endpoint']) : '';
        echo '<input type="url" name="paypay_multicaixa_options[api_endpoint]" value="' . $value . '" class="regular-text" required>';
    }

    public static function render_partner_id_field() {
        $options = get_option('paypay_multicaixa_options', []);
        $value = isset($options['partner_id']) ? esc_attr($options['partner_id']) : '';
        echo '<input type="text" name="paypay_multicaixa_options[partner_id]" value="' . $value . '" class="regular-text" required>';
    }

    public static function render_private_key_field() {
        $options = get_option('paypay_multicaixa_options', []);
        $value = isset($options['private_key']) ? esc_textarea($options['private_key']) : '';
        echo '<textarea name="paypay_multicaixa_options[private_key]" class="large-text" rows="5" required>' . $value . '</textarea>';
    }

    public static function render_public_key_field() {
        $options = get_option('paypay_multicaixa_options', []);
        $value = isset($options['public_key']) ? esc_textarea($options['public_key']) : '';
        echo '<textarea name="paypay_multicaixa_options[public_key]" class="large-text" rows="5" required>' . $value . '</textarea>';
    }

    public static function render_language_field() {
        $options = get_option('paypay_multicaixa_options', []);
        $value = isset($options['language']) ? esc_attr($options['language']) : 'en';
        echo '<select name="paypay_multicaixa_options[language]">';
        echo '<option value="en"' . selected($value, 'en', false) . '>English</option>';
        echo '<option value="pt"' . selected($value, 'pt', false) . '>Portuguese</option>';
        echo '</select>';
    }

    public static function sanitize_settings($input) {
        $sanitized = [];
        $sanitized['api_endpoint'] = esc_url_raw($input['api_endpoint']);
        $sanitized['partner_id'] = sanitize_text_field($input['partner_id']);
        $sanitized['private_key'] = sanitize_textarea_field($input['private_key']);
        $sanitized['public_key'] = sanitize_textarea_field($input['public_key']);
        $sanitized['language'] = in_array($input['language'], ['en', 'pt']) ? $input['language'] : 'en';
        return $sanitized;
    }
}