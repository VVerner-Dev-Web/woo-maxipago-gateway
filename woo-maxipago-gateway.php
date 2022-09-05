<?php defined('ABSPATH') || exit;

/**
 * Plugin Name:           Maxipago Gateway
 * Description:           Integra o gateway de pagamento Maxipago ao WooCommerce
 * Author:                VVerner
 * Author URI:            https://vverner.com
 * Version:               1.0.0
 * License:               GPLv3 or later
 * WC requires at least:  6.5
 * WC tested up to:       6.8.2
 * Requires at least:     5.6
 * Tested up to:          6.0.2
 * Requires PHP:          7.2
 */

define('MAXIPAGO_VERSION', '1.0.0');
define('MAXIPAGO_FILE', __FILE__);
define('MAXIPAGO_APP', __DIR__ . '/app');

require_once MAXIPAGO_APP . '/App.php';
