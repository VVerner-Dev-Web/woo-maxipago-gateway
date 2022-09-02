<?php defined('ABSPATH') || exit;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName:)
 */
abstract class MaxipagoGateway extends WC_Payment_Gateway
{
  private $app  = null;
  private $api  = null;

  public function __construct()
  {
    $this->has_fields          = true;
    $this->supports            = ['products'];

    $this->setFormFields();
    $this->init_settings();

    $this->enabled = ('yes' === $this->get_option('enabled'));
    $this->title   = $this->get_option('title');
    $this->installmentMinPrice     = (int) $this->get_option('installment_min_price');
    $this->installmentMaxQuantity  = (int) $this->get_option('installment_max_quantity');

    $this->app = new MaxipagoApp();

    $this->setApi();

    add_action('wp_enqueue_scripts', [$this, 'enqueueCheckoutAssets']);
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
  }

  public function payment_fields()
  {
    if ($this->isSandbox()) :
      echo wpautop('Integração em modo de testes. Nenhuma transação terá valor comercial.');
    endif;

    wc_get_template(
      'checkout-fields.php',
      ['installments' => $this->getInstallments()],
      'woocommerce/maxipago/',
      $this->app->getPublicTemplatesPath()
    );
  }

  public function is_available()
  {
    return $this->get_option('api_user') && $this->get_option('api_pass') && $this->get_option('api_cnpj') && $this->get_option('api_env') && $this->get_option('api_processor');
  }

  public function using_supported_currency()
  {
    return 'BRL' === get_woocommerce_currency();
  }

  public function process_payment($orderId)
  {
    global $woocommerce;
    $order = wc_get_order($orderId);

    $creditCard = isset($_REQUEST['maxipago']) && is_array($_REQUEST['maxipago']) ? $_REQUEST['maxipago'] : [];

    if (!$creditCard) return;

    $autorize = $this->autorizeTransaction($creditCard, $order,);

    if ($autorize['errorMessage']) :
      wc_add_notice('Erro ao processar o pagamento: ' . $autorize['errorMessage'], 'error');
      return null;
    endif;

    $capture = $this->captureTransaction($autorize['orderID'], $order);

    if ($capture['processorMessage'] !== 'APPROVED') :
      wc_add_notice('Erro ao processar o pagamento: ' . $capture['processorMessage'], 'error');
      return null;
    endif;

    $order->add_order_note('MAXIPAGO: Pagamento recebido, código da transação ' . $capture['transactionID'], false);
    $order->payment_complete();

    $woocommerce->cart->empty_cart();

    return [
      'result'   => 'success',
      'redirect' => $this->get_return_url($order)
    ];
  }

  private function captureTransaction(string $authOrderId, WC_Order $order): array
  {
    $data = [
      'orderID'       => $authOrderId,
      'referenceNum'  => $order->get_id(),
      'chargeTotal'   => $order->get_total()
    ];

    $this->api->creditCardCapture($data);

    return $this->api->response;
  }

  private function autorizeTransaction(array $creditCard, WC_Order $order): array
  {
    $datePieces = explode('/', $creditCard['date']);

    $data = [
      'fraudCheck'    => 'N',
      'ipAddress'     => $_SERVER['REMOTE_ADDR'],
      'referenceNum'  => $order->get_id(),
      'number'        => preg_replace('/\D/', '', $creditCard['number']),
      'cvvNumber'     => preg_replace('/\D/', '', $creditCard['csc']),
      'expMonth'      => $datePieces[0],
      'expYear'       => $datePieces[1],
      'currencyCode'  => 'BRL',
      'chargeTotal'   => $order->get_total(),
      'processorID'   => $this->isSandbox() ? 1 : $this->get_option('api_processor')
    ];

    if ($creditCard['installments'] > 1) :
      $data['numberOfInstallments'] = $creditCard['installments'];
    endif;

    $this->api->creditCardAuth($data);

    return $this->api->response;
  }

  public function enqueueCheckoutAssets()
  {
    if ('no' === $this->enabled || !is_checkout() && !isset($_REQUEST['pay_for_order'])) return;

    wp_enqueue_script('jquery-mask');
    wp_enqueue_script('maxipago-checkout');
  }

  public function setFormFields(): void
  {
    $this->form_fields = [
      'enabled'   => [
        'title'       => 'Ativar',
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
      ],
      'title'     => [
        'title'       => 'Nome do método de pagamento',
        'type'        => 'text',
        'default'     => 'Cartão de Crédito Maxipago',
        'description' => 'Como o nome deste método ficará exibido durante o checkout',
        'desc_tip'    => true,
      ],

      'api_user'  => [
        'title'       => 'Merchant ID',
        'type'        => 'number',
        'description' => 'Esta informação é referente a sua conta na Maxipago',
        'desc_tip'    => true,
      ],
      'api_pass'  => [
        'title'       => 'Merchant Key',
        'type'        => 'text',
        'description' => 'Esta informação é referente a sua conta na Maxipago',
        'desc_tip'    => true,
      ],
      'api_cnpj'  => [
        'title'       => 'CNPJ',
        'type'        => 'text',
        'description' => 'Esta informação é referente a sua conta na Maxipago',
        'desc_tip'    => true,
      ],
      'api_env'   => [
        'title'       => 'Modo de integração',
        'type'        => 'select',
        'options'     => [
          'TEST'        => 'Ambiente de testes',
          'LIVE'        => 'Ambiente de produção - cobranças reais'
        ]
      ],
      'api_processor' => [
        'title'       => 'Processador de pagamentos',
        'type'        => 'select',
        'options'     => [
          '2' => 'Rede',
          '3' => 'Getnet',
          '4' => 'Cielo',
          '5' => 'TEF',
          '6' => 'Elavon',
          '8' => 'ChasePaymentech',
        ]
      ],

      'installment_min_price' => [
        'title'           => 'Valor mínimo da parcela',
        'type'            => 'number',
        'default'         => 10,
      ],
      'installment_max_quantity' => [
        'title'           => 'Quantidade máxima de parcelas',
        'type'            => 'select',
        'default'         => 12,
        'options'         => range(1, 12)
      ]

    ];
  }

  private function getInstallments(): array
  {
    global $woocommerce;
    $orderAmount  = $woocommerce->cart->total;
    $installments = [
      ['qty' => 1, 'amount' => $orderAmount]
    ];

    for ($i = 2; $i <= $this->installmentMaxQuantity; $i++) :

      $iAmount = $orderAmount / $i;

      if ($iAmount < $this->installmentMinPrice) :
        break;
      endif;

      $installments[] = [
        'qty'    => $i,
        'amount' => $iAmount
      ];
    endfor;

    return $installments;
  }

  private function isSandbox(): bool
  {
    return $this->get_option('api_env') === 'TEST';
  }

  private function setApi()
  {
    if (!$this->is_available()) :
      return;
    endif;

    $this->api = new maxiPago;
    $this->api->setCredentials($this->get_option('api_user'), $this->get_option('api_pass'));
    $this->api->setEnvironment($this->get_option('api_env'));
  }
}
