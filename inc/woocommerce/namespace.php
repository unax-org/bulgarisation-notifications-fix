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

	add_action( 'plugins_loaded', __NAMESPACE__ . '\\fix_notifications' );
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
 * Fix WooCommerce notifications for card payments to include document with transaction id.
 *
 * @return void
 */
function fix_notifications() {
	if ( ! class_exists( '\Woo_BG\Admin\Order\Documents' ) 
		|| ! class_exists( '\Woo_BG\Admin\Order\Emails' )
		|| ! class_exists( '\WC_Email_Customer_Processing_Order' )) {
		return;
	}

	remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( 'WC_Email_Customer_Processing_Order', 'trigger' ), 10 );
	remove_action( 'woocommerce_checkout_order_processed', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ), 100 );

	add_action( 'woocommerce_checkout_order_processed', __NAMESPACE__ . '\\order_processed', 10, 2 );
	add_action( 'woocommerce_order_payment_status_changed', __NAMESPACE__ . '\\payment_complete' );
}


/**
 * Display field value on the order edit page
 * 
 * @param int      $order_id          Order ID.
 * @param WC_Order $order             Order object.
 * 
 * @return void
 */
function order_processed( $order_id, $order ) {
	require_once plugin_dir_path( BNF\PLUGIN_FILE ) . 'config.php';

	// If is card payment return. Currently only mypos_virtual but can be extended.
	if ( in_array( $order->get_payment_method(), $bnf_config['card-payment-gateways'] ) && 'wc-pending' === $order->get_status() ) {
		return;
	}        

	checkout_order_processed_notification( $order_id );
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
function payment_complete( $order_id ) {
	checkout_order_processed_notification( $order_id );
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
function checkout_order_processed_notification( $order_id ) {
	\Woo_BG\Admin\Order\Documents::generate_documents( $order_id );
	
	add_filter( 'woocommerce_email_attachments', array( '\Woo_BG\Admin\Order\Emails', 'attach_invoice_to_mail' ), 10, 4 );

	$WC_Email_Customer_Processing_Order = new \WC_Email_Customer_Processing_Order();
	$WC_Email_Customer_Processing_Order->trigger( $order_id );
}
