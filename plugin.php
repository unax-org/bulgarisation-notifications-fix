<?php
/**
 * Plugin Name: Bulgarisation Notifications Fix
 * Description: A plugin fixes notifications for card payments with callbacks.
 * Version: 1.0.0
 * Requires Plugins: woocommerce, bulgarisation-for-woocommerce
 * Requires at least: 4.7
 * Requires PHP: 7.2
 * Author: Unax
 * Author URI: https://unax.org/
 * Text Domain: bnf
 *
 * WC requires at least: 3.0
 * WC tested up to:  6.4.1
 *
 * License: Apache-2.0
 * License URI: https://github.com/mobio/mobio-woocommerce/blob/master/LICENSE
 *
 * @package     BNF
 * @author      Unax
 */


namespace Unax\BNF;

const PLUGIN_NAME = 'Bulgarisation Notifications Fix';
const PLUGIN_VERSION = '1.0.0';
const PLUGIN_FILE = __FILE__;

require __DIR__ . '/inc/namespace.php';


/**
 * Load the textdomain.
 */
load_plugin_textdomain( 'bnf', false, sprintf( '%s/languages', plugin_dir_path( __FILE__ ) ) );


/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', 'Unax\BNF\Inc\\bootstrap' );


/**
 * Register deactivation hook.
 */
register_deactivation_hook( __FILE__, 'Unax\BNF\Inc\\deactivate' );



