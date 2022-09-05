<?php defined('ABSPATH') || exit; ?>

<fieldset id="wc-maxipago-bank_slip-form" class="wc-payment-form wc-credit-card-form" style="background:transparent;">
  <?php do_action('woocommerce_credit_card_form_start', 'maxipago-bank_slip'); ?>

  <?= wpautop($instructions); ?>

  <div class="clear"></div>

  <?php do_action('woocommerce_credit_card_form_end', 'maxipago-bank_slip'); ?>

  <div class="clear"></div>
</fieldset>
