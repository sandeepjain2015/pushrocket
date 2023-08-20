<?php
/*
Plugin Name: Pushrocket
Description: A plugin for Pushrocket settings.
Version: 1.0
Author: Sandeep jain
Author URI: https://sandeepjain.me/
*/
// Exit if accessed directly.
namespace Pushrocket;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Represents a featured product in the system.
 *
 * The Featured_Product class provides functionality related to managing and displaying featured products.
 * It includes methods for retrieving featured product data, setting featured status, and performing related operations.
 *
 * @since 1.0.0
 */
class Pushrocket {
	/**
	 * Class constructor for initializing the object.
	 *
	 * It sets up initial properties and performs any necessary setup or initialization tasks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		define( 'PUSHROCKET_PATH', plugin_dir_url( __FILE__ ) );
		define( 'PUSHROCKET_DIR', plugin_dir_path( __FILE__ ) );
		$this->includes();
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

	}
	public function includes() {
		require_once PUSHROCKET_DIR . 'class-notification-settings.php';
		require_once PUSHROCKET_DIR . 'utils/class-send-notification.php';
	}
	 /**
	  * Admin Notices Here
	  *
	  * @since 1.0.0
	  */
	public function admin_notices() {
		$error_msg = get_transient( 'pushrocket_error' );
		if ( $error_msg ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html( $error_msg ) . '</strong></p></div>';
			delete_transient( 'pushrocket_error' );
		}
	}
}
new Pushrocket();
