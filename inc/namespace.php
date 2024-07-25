<?php
/**
 * Bulgarisation Notifications Fix namespace.
 * 
 * @package     BNF
 * @author      Unax
 */

namespace Unax\BNF\Inc;

use Unax\BNF\Inc\WooCommerce;

/**
 * Hook everything.
 *
 * @return void
 */
function bootstrap() {
	require __DIR__ . '/woocommerce/namespace.php';

	WooCommerce\bootstrap();
}


/**
 * Plugin init.
 *
 * @return void
 */
function init() {}


/**
 * Admin options scripts.
 *
 * @param  string $hook WooCommerce Hook.
 * @return void
 */
function admin_enqueue_scripts( $hook ) {}


/**
 * Public scripts.
 *
 * @return void
 */
function enqueue_scripts() {}


/**
 * Deactivate plugin.
 *
 * @return void
 */
function deactivate() {}
