<?php defined('ABSPATH') || exit;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName:)
 */
abstract class MaxipagoGateway extends WC_Payment_Gateway
{
  protected $defaultTitle = 'Maxipago';
  protected $customFields = [];
  protected $app    = null;
  protected $api    = null;

  public function __construct()
  {
    $this->has_fields          = true;
    $this->supports            = ['products'];

    $this->setFormFields();
    $this->init_settings();

    $this->enabled = ('yes' === $this->get_option('enabled'));
    $this->title   = $this->get_option('title');

    $this->app = new MaxipagoApp();

    $this->setApi();

    add_action('wp_enqueue_scripts', [$this, 'enqueueCheckoutAssets']);
    add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
  }

  public function is_available()
  {
    return $this->get_option('api_user') && $this->get_option('api_pass') && $this->get_option('api_cnpj') && $this->get_option('api_env') && $this->get_option('api_processor');
  }

  public function using_supported_currency()
  {
    return 'BRL' === get_woocommerce_currency();
  }

  public function enqueueCheckoutAssets()
  {
    if ('no' === $this->enabled || !is_checkout() && !isset($_REQUEST['pay_for_order'])) return;

    wp_enqueue_script('jquery-mask');
    wp_enqueue_script('maxipago-checkout');
  }

  public function setFormFields(): void
  {
    $this->form_fields = $this->getFormFields();
  }

  protected function theSandboxMessage(): void
  {
    if ($this->isSandbox()) :
      echo wpautop('<strong>Integração em modo de testes. Nenhuma transação terá valor comercial.</strong>');
      echo '<hr>';
    endif;
  }

  protected function isSandbox(): bool
  {
    return $this->get_option('api_env') === 'TEST';
  }

  protected function getSaleData(WC_Order $order): array
  {
    return [
      'referenceNum'      => $order->get_id(),
      'processorID'       => $this->isSandbox() ? $this->getSandboxProcessor() : $this->get_option('api_processor'),
      'ipAddress'         => $_SERVER['REMOTE_ADDR'],
      'chargeTotal'       => $order->get_total(),
      'billingName'       => $order->get_formatted_billing_full_name(),
      'billingAddress'    => $order->get_billing_address_1(),
      'billingAddress2'   => $order->get_billing_address_2(),
      'billingCity'       => $order->get_billing_city(),
      'billingState'      => $order->get_billing_state(),
      'billingPostalCode' => $order->get_billing_postcode(),
      'billingCountry'    => $order->get_billing_country(),
      'billingEmail'      => $order->get_billing_email(),

    ];
  }

  protected function getSandboxProcessor(): int
  {
    return 1;
  }

  private function getFormFields(): array
  {
    return array_merge([
      'enabled'   => [
        'title'       => 'Ativar',
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
      ],
      'title'     => [
        'title'       => 'Nome do método de pagamento',
        'type'        => 'text',
        'default'     => $this->defaultTitle,
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
      ]
    ], $this->customFields);
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
