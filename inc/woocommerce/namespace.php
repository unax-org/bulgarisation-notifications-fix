<?php
/**
 * Engenuity WP namespace.
 * 
 * @package     Engenuity App
 * @author      Engenuity
 */

namespace Unax\BNF\Inc\WooCommerce;

use Unax\BNF;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Hook everything.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'before_woocommerce_init', __NAMESPACE__ . '\\declare_wc_compatibility' );

	remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( 'WC_Email_Customer_Processing_Order', 'trigger' ), 10, 2 );
	
	// remove_action( 'woocommerce_checkout_order_processed', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ), 100 );
	// remove_action( 'woocommerce_checkout_order_processed', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ), 100 );
	// add_action( 'woocommerce_checkout_order_processed', 'bnf_generate_documents', 100, 3 );
	// add_action( 'woocommerce_payment_complete', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ) );
}


/**
 * Declare High-Performance Order Storage (HPOS) compatibility.
 *
 * @return void
 */
function declare_wc_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', BNF\PLUGIN_FILE, true );
	}
}


/**
 * Display field value on the order edit page
 * 
 * @param int $order_id Order ID
 * @param array $posted_data Posted data
 * @param WC_Order $order Order object
 * 
 * @return void
 */
function generate_documents( $order_id, $posted_data, $order ) {
	require_once BNF_PATH . 'config.php';

	// If is card payment return. Currently only mypos_virtual but can be extended.
	if ( in_array( $order->get_payment_method(), $bnf_config['card-payment-gateways'] ) ) {
		return;
	}        

	if ( ! class_exists( '\Woo_BG\Admin\Order\Documents' ) ) {
		return;
	}

	\Woo_BG\Admin\Order\Documents::generate_documents( $order_id );
}
