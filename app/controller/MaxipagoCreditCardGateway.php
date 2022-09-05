<?php defined('ABSPATH') || exit;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName:)
 */
class MaxipagoCreditCardGateway extends MaxipagoGateway
{
  public $id                  = 'maxipago-credit_card';
  public $method_title        = 'Maxipago';
  public $method_description  = 'Realize cobranças através de cartão de crédito através do gateway Maxipago';

  protected $defaultTitle    = 'Cartão de Crédito via Maxipago';

  public function __construct()
  {
    $this->installmentMinPrice     = (int) $this->get_option('installment_min_price');
    $this->installmentMaxQuantity  = (int) $this->get_option('installment_max_quantity');

    $this->setCustomFields();

    parent::__construct();
  }

  public function payment_fields()
  {
    $this->theSandboxMessage();

    wc_get_template(
      'credit-card.php',
      ['installments' => $this->getInstallments()],
      'woocommerce/maxipago/',
      $this->app->getPublicTemplatesPath()
    );
  }

  public function process_payment($orderId)
  {
    global $woocommerce;
    $order = wc_get_order($orderId);

    $creditCard = isset($_REQUEST['maxipago']) && is_array($_REQUEST['maxipago']) ? $_REQUEST['maxipago'] : [];

    if (!$creditCard) return;

    $autorize = $this->autorizeTransaction($creditCard, $order);

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

    $sale = $this->getSaleData($order);

    $sale['fraudCheck']   = 'N';
    $sale['number']       = preg_replace('/\D/', '', $creditCard['number']);
    $sale['cvvNumber']    = preg_replace('/\D/', '', $creditCard['csc']);
    $sale['expMonth']     = $datePieces[0];
    $sale['expYear']      = $datePieces[1];
    $sale['currencyCode'] = 'BRL';

    if ($creditCard['installments'] > 1) :
      $sale['numberOfInstallments'] = $creditCard['installments'];
    endif;

    $this->api->creditCardAuth($sale);

    return $this->api->response;
  }

  protected function getSandboxProcessor(): int
  {
    return 1;
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

  private function setCustomFields(): void
  {
    $this->customFields = [
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
}
