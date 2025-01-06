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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Hook everything.
 *
 * @return void
 */
function bootstrap() {
	add_action( 'before_woocommerce_init', __NAMESPACE__ . '\\declare_wc_compatibility' );
	add_action( 'init', __NAMESPACE__ . '\\fix_notifications' );
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
 * Get cart payment gateways.
 *
 * @return array
 */
function get_card_payment_gateways() {
	return apply_filters( 'bnf_card_payment_gateways', BNF\CARD_PAYMENT_GATEWAYS );
}


/**
 * Fix WooCommerce notifications for card payments to include document with transaction id.
 *
 * @return void
 */
function fix_notifications() {
	if ( ! class_exists( '\Woo_BG\Admin\Order\Documents' ) || ! class_exists( '\Woo_BG\Admin\Order\Emails' ) ) {
		$logger = wc_get_logger();

		$logger->error( 
			'Bulgarisation for WooCommerce classes not found', 
			array( 
				'source' => 'bulgarisation-notifications-fix',
			) 
		);

		return;
	}


	if ( ! class_exists( '\WC_Email_Customer_Processing_Order' ) ) {
		$wc_emails_path = WP_PLUGIN_DIR . '/woocommerce/includes/emails/';
		
		if ( ! file_exists( $wc_emails_path . 'class-wc-email.php' ) ) {
			$logger = wc_get_logger();

			$logger->error( 
				'WC Email classes not found', 
				array( 
					'source' => 'bulgarisation-notifications-fix',
				) 
			);

			return;
		}

		include $wc_emails_path . 'class-wc-email.php';
		include $wc_emails_path . 'class-wc-email-customer-processing-order.php';
	}

	// Disable email for card payments.
	add_filter( 'woocommerce_email_enabled_customer_processing_order', __NAMESPACE__ . '\\email_enabled_customer_processing_order', 10, 4 );
	
	// Move document generation to the end of the order processing for card payments.
	remove_action( 'woocommerce_checkout_order_processed', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ), 100 );
	add_action( 'woocommerce_checkout_order_processed', __NAMESPACE__ . '\\checkout_order_processed', 100, 3 );

	// Generate documents and send customer notification after payment complete.
	add_action( 'woocommerce_payment_complete', __NAMESPACE__ . '\\payment_complete' );
}


/**
 * Disable email for card payments.
 * 
 * @param bool 								  $enabled 	   Is email enabled
 * @param \WC_Order 						  $order 	   Order
 * @param \WC_Email_Customer_Processing_Order $email_object Email object
 * 
 * @return bool
 */
function email_enabled_customer_processing_order( $enabled, $order, $email_object ) {
	$logger = wc_get_logger();
	if ( ! $order instanceof \WC_Order ) {
		$logger->error( 
			'Missing order in filter enabled email for customer processing order', 
			array( 
				'source' => 'bulgarisation-notifications-fix',
			) 
		);

		return $enabled;
	}

	$logger->debug( 
		'Filter enabled email for customer processing order', 
		array( 
			'order_id' => $order->get_id(), 
			'payment_method' => $order->get_payment_method(), 
			'order_status' => $order->get_status(), 
			'transaction_id'=> $order->get_transaction_id(),
			'source' => 'bulgarisation-notifications-fix',
		) 
	);

	// If is card payment return. Currently only mypos_virtual but can be extended.
	if ( in_array( $order->get_payment_method(), get_card_payment_gateways() ) ) {
		$enabled = false;
	}

	return $enabled;
}


/**
 * Skip generating documents for card payments.
 * 
 * @param int      $order_id          Order ID.
 * @param array    $posted_data       Posted data.
 * @param WC_Order $order             Order object.
 * 
 * @return void
 */
function checkout_order_processed( $order_id, $posted_data, $order ) {
	$order = wc_get_order( $order_id );

	$logger = wc_get_logger();
	$logger->debug( 
		'Order processed', 
		array( 
			'order_id' => $order_id, 
			'payment_method' => $order->get_payment_method(), 
			'order_status' => $order->get_status(), 
			'transaction_id'=> $order->get_transaction_id(),
			'source' => 'bulgarisation-notifications-fix',
		) 
	);	

	// If is card payment skip generating documents.
	if ( in_array( $order->get_payment_method(), get_card_payment_gateways() ) ) {
		return;
	}

	\Woo_BG\Admin\Order\Documents::generate_documents( $order_id );
}


/**
 * Generatie documents and send email to the customer afer payment complete.
 * 
 * @param int $order_id Order ID
 * 
 * @return void
 */
function payment_complete( $order_id ) {
	$order = wc_get_order( $order_id );

	$logger = wc_get_logger();
	$logger->debug( 
		'Woocommerce thankyou page', 
		array( 
			'order_id' => $order_id, 
			'payment_method' => $order->get_payment_method(),
			'order_status' => $order->get_status(), 
			'transaction_id'=> $order->get_transaction_id(),
			'source' => 'bulgarisation-notifications-fix',
		) 
	);

	\Woo_BG\Admin\Order\Documents::generate_documents( $order_id );

	add_filter( 'woocommerce_email_attachments', array( '\Woo_BG\Admin\Order\Emails', 'attach_invoice_to_mail' ), 10, 4 );
	add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_true', 20 );

	$WC_Email_Customer_Processing_Order = new \WC_Email_Customer_Processing_Order();
	$WC_Email_Customer_Processing_Order->trigger( $order_id );
}
