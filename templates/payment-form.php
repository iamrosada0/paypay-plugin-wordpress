<?php if (!defined('ABSPATH')) exit; ?>
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