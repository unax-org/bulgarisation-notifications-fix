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
 * Fix WooCommerce notifications for card payments to include document with transaction id.
 *
 * @return void
 */
function fix_notifications() {
	require_once plugin_dir_path( BNF\PLUGIN_FILE ) . 'config.php';

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

	remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( 'WC_Email_Customer_Processing_Order', 'trigger' ), 10 );
	remove_action( 'woocommerce_checkout_order_processed', array( '\Woo_BG\Admin\Order\Documents', 'generate_documents' ), 100 );

	add_action( 'woocommerce_checkout_order_processed', __NAMESPACE__ . '\\order_processed', 100, 3 );
	foreach ( $bnf_config['card-payment-gateways'] as $gateway_id ) {
		add_action( 'woocommerce_thankyou_' . $gateway_id, __NAMESPACE__ . '\\woocommerce_thankyou' );
	}
}


/**
 * Display field value on the order edit page
 * 
 * @param int      $order_id          Order ID.
 * @param array    $posted_data       Posted data.
 * @param WC_Order $order             Order object.
 * 
 * @return void
 */
function order_processed( $order_id, $posted_data, $order ) {
	require_once plugin_dir_path( BNF\PLUGIN_FILE ) . 'config.php';

	$logger = wc_get_logger();
	$order = wc_get_order( $order_id );

	$logger->info( 
		'Order processed', 
		array( 
			'order_id' => $order_id, 
			'order_status' => $order->get_status(), 
			'transaction_id'=> $order->get_transaction_id(),
			'source' => 'bulgarisation-notifications-fix',
		) 
	);	

	// If is card payment return. Currently only mypos_virtual but can be extended.
	if ( in_array( $order->get_payment_method(), $bnf_config['card-payment-gateways'] ) ) {
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
function woocommerce_thankyou( $order_id ) {
	$logger = wc_get_logger();
	$order = wc_get_order( $order_id );

	$logger->info( 
		'Order payment complete', 
		array( 
			'order_id' => $order_id, 
			'order_status' => $order->get_status(), 
			'transaction_id'=> $order->get_transaction_id(),
			'source' => 'bulgarisation-notifications-fix',
		) 
	);	

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
