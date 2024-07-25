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

	// add_action( 'woocommerce_after_register_post_type', __NAMESPACE__ . '\\init' );

	WooCommerce\bootstrap();
}


/**
 * Plugin init.
 *
 * @return void
 */
function init() {
	\Woo_BG\Admin\Order\Documents::generate_documents( 2203 );
}


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
