<?php defined('ABSPATH') || exit;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName:)
 */
class MaxipagoBankSlipGateway extends MaxipagoGateway
{
  public $id                  = 'maxipago-bank_slip';
  public $method_title        = 'Maxipago';
  public $method_description  = 'Realize cobranças através de boleto através do gateway gateway Maxipago';

  protected $defaultTitle     = 'Boleto via Maxipago';
  private const URL_META_KEY  = 'maxipago-bank_slip-url';

  public function __construct()
  {
    $this->setCustomFields();

    parent::__construct();

    add_action('woocommerce_thankyou_' . $this->id, [$this, 'getBoletoPaymentInstructions']);
    add_action('woocommerce_view_order', [$this, 'getBoletoPaymentInstructions'], 1);
    add_filter('woocommerce_my_account_my_orders_actions', [$this, 'insertButtonInOrderActions'], 10, 2);
  }

  public function payment_fields()
  {
    $this->theSandboxMessage();

    wc_get_template(
      'bank-slip.php',
      ['instructions' => $this->get_option('checkout_instructions')],
      'woocommerce/maxipago/',
      $this->app->getPublicTemplatesPath()
    );
  }

  public function getBoletoPaymentInstructions(int $orderId): void
  {
    $order = wc_get_order($orderId);

    if ($order && $order->has_status('on-hold')) :
      wc_get_template(
        'bank-slip-payment-instructions.php',
        [
          'instructions' => $this->get_option('payment_instructions'),
          'bankSlipUrl'  => $order->get_meta(self::URL_META_KEY)
        ],
        'woocommerce/maxipago/',
        $this->app->getPublicTemplatesPath()
      );
    endif;
  }

  public function insertButtonInOrderActions(array $actions, WC_Order $order): array
  {
    if ($order->has_status('on-hold') && $order->get_payment_method() === $this->id) :
      $actions[$this->id] = [
        'url'  => $order->get_meta(self::URL_META_KEY),
        'name' => 'Ver boleto',
      ];
    endif;

    return $actions;
  }

  public function process_payment($orderId)
  {
    global $woocommerce;
    $order    = wc_get_order($orderId);

    $bankSlip = $this->createBankSlip($order);

    if ($bankSlip['responseMessage'] !== 'ISSUED') :
      wc_add_notice('Erro ao processar o pagamento: ' . $bankSlip['errorMessage'], 'error');
      return null;
    endif;

    $order->add_order_note('MAXIPAGO: Código da transação ' . $bankSlip['transactionID'], false);
    $order->add_order_note('MAXIPAGO: Boleto gerado. Disponível em: ' . $bankSlip['boletoUrl'], false);
    $order->add_meta_data(self::URL_META_KEY, $bankSlip['boletoUrl'], true);
    $order->set_status('on-hold');
    $order->save();

    $woocommerce->cart->empty_cart();

    return [
      'result'   => 'success',
      'redirect' => $this->get_return_url($order)
    ];
  }

  private function createBankSlip(WC_Order $order): array
  {
    $sale = $this->getSaleData($order);
    $sale['expirationDate'] = $this->getExpirationDate();
    $sale['number'] = str_pad($order->get_id(), 8, '0', STR_PAD_LEFT);
    $sale['instructions'] = $this->get_option('payment_instructions') . '; Referente ao pedido: ' . $order->get_id();

    $this->api->boletoSale($sale);

    return $this->api->response;
  }

  private function getExpirationDate(): string
  {
    $date = new DateTime(current_time('Y-m-d'));
    $date->modify('+' . $this->get_option('expiration_time') . ' days');
    return $date->format('Y-m-d');
  }

  protected function getSandboxProcessor(): int
  {
    return 12;
  }

  private function setCustomFields(): void
  {
    $this->customFields = [
      'api_processor' => [
        'title'       => 'Processador de pagamentos',
        'type'        => 'select',
        'options'     => [
          '11' => 'Boleto Itaú',
          '12' => 'Boleto Bradesco',
          '13' => 'Boleto Banco do Brasil',
          '14' => 'HSBC',
          '15' => 'Santander',
          '16' => 'Caixa Econômica Federal',
        ]
      ],
      'checkout_instructions' => [
        'title'           => 'Instruções de pagamento',
        'type'            => 'textarea',
        'default'         => 'Ao clicar em concluir compra, você receberá a URL do boleto.',
      ],
      'expiration_time' => [
        'title'           => 'Validade do boleto',
        'type'            => 'number',
        'default'         => 10,
        'description'     => 'Valor em dias',
        'desc_tip'        => true
      ],
      'payment_instructions'  => [
        'title'           => 'Instruções de pagamento',
        'type'            => 'textarea',
        'default'         => 'Sr. Caixa, não aceitar após o vencimento.',
        'description'     => 'Use ponto e vírgula (";") para pular uma linha.',
        'desc_tip'        => true
      ]
    ];
  }
}
