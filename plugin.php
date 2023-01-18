<?php

namespace Ainsys\Connector\Content;

/**
 * Plugin Name:       AINSYS Connector Headless CMS
 * Plugin URI: https://app.ainsys.com/
 * Description: Plugin for replacing content on a WordPress site.
 * Version:           1.1.1
 * Author:            AINSYS
 * Author URI:        https://app.ainsys.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     ainsys_connector_content
 * Domain Path:     /languages
 */

defined( 'ABSPATH' ) || die();
define( 'AINSYS_CONNECTOR_CONTENT_TEXTDOMAIN', 'ainsys_connector_content' );
if ( version_compare( PHP_VERSION, '7.4.0' ) < 0 ) {

	if ( ! function_exists( 'deactivate_plugins' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	deactivate_plugins( plugin_basename( __FILE__ ) );

	add_action(
		'admin_notices',
		function () {

			$class    = 'notice notice-error is-dismissible';
			$message1 = __( 'Upgrade your PHP version. Minimum version - 7.4+. Your PHP version ' );
			$message2 = __( '! If you don\'t know how to upgrade PHP version, just ask in your hosting provider! If you can\'t upgrade - delete this plugin!' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message1 . PHP_VERSION . $message2 ) );
		}
	);

}

/**
 * Show notice if master plugin is not active.
 *
 * @return void
 */
function check_if_master_plugin_is_active() {

	if ( ! function_exists( '\Ainsys\Connector\autoloader' ) ) {
		// show admin notice on error.
		add_action(
			'admin_notices',
			function () {

				$class   = 'notice notice-error is-dismissible';
				$message = __( 'Please install and activate `Ainsys WP Connector Master Plugin` first' );

				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}
		);
	}

}


add_action( 'plugins_loaded', __NAMESPACE__ . '\check_if_master_plugin_is_active', 20 );

/**
 * Links sub plugin's class to be loaded with master plugin.
 *
 * @param  array $sub_plugins
 *
 * @return array
 */
function enqueue_to_be_loaded( $sub_plugins = [] ) {

	$sub_plugins[ __FILE__ ] = __NAMESPACE__ . '\Plugin';

	return $sub_plugins;
}


add_filter( 'ainsys_child_plugins_to_be_loaded', __NAMESPACE__ . '\enqueue_to_be_loaded' );
