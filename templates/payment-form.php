<?php if (!defined('ABSPATH')) exit; ?>
<form method="post" action="">
    <p>
        <label for="paypay_amount"><?php _e('Amount (AOA)', 'paypay-multicaixa'); ?></label>
        <input type="number" id="paypay_amount" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" step="0.01" required>
    </p>
    <p>
        <label for="paypay_description"><?php _e('Description', 'paypay-multicaixa'); ?></label>
        <input type="text" id="paypay_description" name="description" required>
    </p>
    <?php wp_nonce_field('paypay_multicaixa_payment', 'paypay_nonce'); ?>
    <input type="submit" name="paypay_multicaixa_submit" value="<?php _e('Pay Now', 'paypay-multicaixa'); ?>">
</form>