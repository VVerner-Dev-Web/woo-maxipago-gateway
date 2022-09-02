<?php defined('ABSPATH') || exit;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName:)
 */
class MaxipagoBankSlipGateway extends MaxipagoGateway
{

  public function __construct()
  {
    $this->id                  = 'maxipago-bank_slip';
    $this->method_title        = 'Maxipago';
    $this->method_description  = 'Realize cobranças através de boleto através do gateway gateway Maxipago';

    parent::__construct();
  }
}
