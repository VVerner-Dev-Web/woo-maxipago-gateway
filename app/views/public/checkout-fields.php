<?php defined('ABSPATH') || exit; ?>

<fieldset id="wc-maxipago-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
  <?php do_action('woocommerce_credit_card_form_start', 'maxipago'); ?>

  <p class="form-row form-row-wide">
    <label for="maxipago_ccName"> Nome impresso no cartão </label>
    <input style="width: 100%" id="maxipago_ccName" name="maxipago[name]" type="text" autocomplete="cc-name">
  </p>

  <p class="form-row form-row-wide">
    <label for="maxipago_ccNo"> Número do cartão </label>
    <input style="width: 100%" id="maxipago_ccNo" name="maxipago[number]" type="text" inputmode="numeric" autocomplete="cc-number">
  </p>

  <p class="form-row form-row-first">
    <label for="maxipago_expdate"> Validade do cartão </label>
    <input style="width: 100%" id="maxipago_expdate" name="maxipago[date]" type="text" placeholder="MM/AAAA" inputmode="numeric" autocomplete="cc-exp">
  </p>

  <p class="form-row form-row-last">
    <label for="maxipago_csc"> Código de Segurança </label>
    <input style="width: 100%" id="maxipago_csc" name="maxipago[csc]" type="password" autocomplete="off" placeholder="CSC" inputmode="numeric" maxlength="3" autocomplete="cc-csc">
  </p>

  <?php if (count($installments) > 1) : ?>
    <p class="form-row-wide">
      <label for="maxipago_installments"> Parcelamento </label>
      <select style="width: 100%" name="maxipago[installments]" id="maxipago_installments">
        <?php foreach ($installments as $i) : ?>
          <option value="<?= $i['qty'] ?>">
            <?= $i['qty'] ?> parcelas de R$ <?= wc_price($i['amount']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </p>
  <?php else : ?>
    <input type="hidden" name="maxipago[installments]" value="1" readonly>
  <?php endif; ?>

  <div class="clear"></div>

  <?php do_action('woocommerce_credit_card_form_end', 'maxipago'); ?>

  <div class="clear"></div>
</fieldset>
