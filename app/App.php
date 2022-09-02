<?php defined('ABSPATH') || exit;

class MaxipagoApp
{
  public function init()
  {
    if (!class_exists('WC_Payment_Gateway')) :
      add_action('admin_notices', [$this, 'loadWoocommerceMissingNotice']);
      return;
    endif;

    $this->includeFiles();

    add_filter('woocommerce_payment_gateways', [$this, 'enqueueGateway']);

    add_action('wp_enqueue_scripts', [$this, 'registerPublicAssets']);
    add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAsset']);
  }

  public function enqueueGateway(array $methods): array
  {
    $methods[] = 'MaxipagoGateway';
    return $methods;
  }

  public function loadAdminView(string $view): void
  {
    require MAXIPAGO_APP . '/views/admin/' . $view . '.php';
  }

  public function getPublicTemplatesPath(): string
  {
    return MAXIPAGO_APP . '/views/public/';
  }

  public function enqueueAdminAsset(): void
  {
    $screen = get_current_screen();
    if ($screen->base !== 'woocommerce_page_wc-settings') :
      return;
    endif;

    wp_enqueue_script('jquery-mask', $this->getAssetsUrl() . 'vendor/jquery.mask.min.js', ['jquery'], '1.14.16', true);
    wp_enqueue_script('maxipago-admin', $this->getAssetsUrl() . 'js/admin.js', ['jquery', 'jquery-mask'], MAXIPAGO_VERSION, true);
  }

  public function registerPublicAssets(): void
  {
    wp_register_script('jquery-mask', $this->getAssetsUrl() . 'vendor/jquery.mask.min.js', ['jquery'], '1.14.16', true);
    wp_register_script('maxipago-checkout', $this->getAssetsUrl() . 'js/checkout.js', ['jquery', 'jquery-mask'], MAXIPAGO_VERSION, true);
  }

  private function includeFiles(): void
  {
    require_once MAXIPAGO_APP . "/controller/maxipago-sdk/maxipago/RequestBase.php";
    require_once MAXIPAGO_APP . "/controller/maxipago-sdk/maxipago/XmlBuilder.php";
    require_once MAXIPAGO_APP . "/controller/maxipago-sdk/maxipago/KLogger.php";
    require_once MAXIPAGO_APP . "/controller/maxipago-sdk/maxipago/Request.php";
    require_once MAXIPAGO_APP . "/controller/maxipago-sdk/maxipago/ServiceBase.php";
    require_once MAXIPAGO_APP . "/controller/maxipago-sdk/maxipago/ResponseBase.php";
    require_once MAXIPAGO_APP . '/controller/maxipago-sdk/maxiPago.php';
    require_once MAXIPAGO_APP . '/controller/MaxipagoGateway.php';
  }

  public function loadWoocommerceMissingNotice(): void
  {
    $this->loadAdminView('notice-missing-woocommerce');
  }

  private function getAssetsUrl(): string
  {
    return trailingslashit(plugins_url('app/assets', MAXIPAGO_FILE));
  }
}

$plugin = new MaxipagoApp;
add_action('init', [$plugin, 'init'], 9);
