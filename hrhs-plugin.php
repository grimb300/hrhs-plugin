<?php

/**
 * Plugin Name:       Rocktown History Plugin
 * Plugin URI:        https://rocktownhistory.com/
 * Description:       Create, display, and manage Harrisonburg-Rockingham Historical Society archives.
 * Version:           0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Bob Grim
 * Author URI:        https://candolatitude.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hrhs-plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin wide defines
 */

define( 'HRHS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'HRHS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

function hrhs_debug( $msg ) {
  if ( is_array( $msg ) || is_object( $msg ) || is_bool( $msg ) ) {
    $msg = var_export( $msg, true );
  }
  error_log( 'hrhs-plugin: ' . $msg );
}


/**
 * Include the core class responsible for loading all necessary components of the plugin.
 */
if ( !class_exists( "HRHS_Plugin" ) ) {
	require_once HRHS_PLUGIN_PATH . 'includes/class-hrhs-plugin.php';

 	/**
	 * Instantiates the HRHS Plugin class and then calls its run method officially starting
	 * the plugin.
	 */
	function run_hrhs_plugin() {
		$hrhs = new HRHS_Plugin();
		register_activation_hook(__FILE__, array($hrhs, 'hrhs_activation'));
		register_deactivation_hook(__FILE__, array($hrhs, 'hrhs_deactivation'));
		$hrhs->run();
	}

	// Call the above function to begin execution of the plugin.
	run_hrhs_plugin();

}