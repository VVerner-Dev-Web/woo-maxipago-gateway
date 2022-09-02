<?php defined('ABSPATH') || exit;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName:)
 */
class MaxipagoCreditCardGateway extends MaxipagoGateway
{

  public function __construct()
  {
    $this->id                  = 'maxipago-credit_card';
    $this->method_title        = 'Maxipago';
    $this->method_description  = 'Realize cobranças através de cartão de crédito através do gateway Maxipago';

    parent::__construct();
  }
}
