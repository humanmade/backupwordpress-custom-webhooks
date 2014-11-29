<?php
/*
Plugin Name: BackUpWordPress Custom Webhooks
Plugin URI: https://bwp.hmn.md/downloads/backupwordpress-custom-webhooks/
Description: Send backup notifications to external webhooks
Author: Human Made Limited
Version: 1.0
Author URI: https://bwp.hmn.md/
License: GPLv2
Network: true
*/

/*
	Copyright 2013 Human Made Limited  (email : support@hmn.md)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

defined( 'WPINC' ) or die;

if ( ! defined( 'HMBKP_CW_REQUIRED_PHP_VERSION' ) ) {
	define( 'HMBKP_CW_REQUIRED_PHP_VERSION', '5.3.2' );
}

if ( ! defined( 'HMBKP_CW_REQUIRED_WP_VERSION' ) ) {
	define( 'HMBKP_CW_REQUIRED_WP_VERSION', '3.8.4' );
}

// Don't activate on anything less than PHP required version
if ( version_compare( phpversion(), HMBKP_CW_REQUIRED_PHP_VERSION, '<' ) ) {

	deactivate_plugins( trailingslashit( basename( dirname( __FILE__ ) ) ) . basename( __FILE__ ) );

	wp_die( sprintf( __( 'BackUpWordPress Custom Webhooks requires PHP version %s or greater.', 'backupwordpress' ), HMBKP_CW_REQUIRED_PHP_VERSION ), __( 'BackUpWordPress to Windows Azure', 'backupwordpress' ), array( 'back_link' => true ) );

}

function hmbkpp_webhooks_check() {

	if ( ! class_exists( 'HMBKP_Scheduled_Backup' ) ) {

		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'BackUpWordPress Custom Webhooks requires BackUpWordPress to be activated. It has been deactivated.', 'backupwordpress' ), 'BackUpWordPress to Windows Azure', array( 'back_link' => true ) );

	}
}

add_action( 'admin_init', 'hmbkpp_webhooks_check' );

function hmbkp_webhooks_activate() {

	if ( ! class_exists( 'HMBKP_Scheduled_Backup' ) ) {

		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'BackUpWordPress Custom Webhooks requires BackUpWordPress to be activated. It has been deactivated.', 'backupwordpress' ), 'BackUpWordPress to Windows Azure', array( 'back_link' => true ) );

	}

	// Don't activate on old versions of WordPress
	global $wp_version;

	if ( version_compare( $wp_version, HMBKP_CW_REQUIRED_WP_VERSION, '<' ) ) {
		deactivate_plugins( trailingslashit( basename( dirname( __FILE__ ) ) ) . basename( __FILE__ ) );
		wp_die( sprintf( __( 'BackUpWordPress to Windows Azure requires WordPress version %s or greater.', 'backupwordpress' ), HMBKP_CW_REQUIRED_WP_VERSION ), __( 'BackUpWordPress to Windows Azure', 'backupwordpress' ), array( 'back_link' => true ) );

	}

	// loads the translation files
	hmbkp_webhooks_plugin_textdomain();

}

register_activation_hook( __FILE__, 'hmbkp_webhooks_activate' );

/**
 * Set up plugin, load dependencies, modules and add hooks
 */
function hmbkp_webhooks_plugin_setup() {

	if ( ! defined( 'HMBKP_CW_PLUGIN_SLUG' ) ) {
		define( 'HMBKP_CW_PLUGIN_SLUG', plugin_basename( dirname( __FILE__ ) ) );
	}

	if ( ! defined( 'HMBKP_CW_PLUGIN_PATH' ) ) {
		define( 'HMBKP_CW_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
	}

	if ( ! defined( 'HMBKP_CW_PLUGIN_URL' ) ) {
		define( 'HMBKP_CW_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	}

	// Set filter for plugin's languages directory
	if ( ! defined( 'HMBKP_CW_PLUGIN_LANG_DIR' ) ) {
		define( 'HMBKP_CW_PLUGIN_LANG_DIR', apply_filters( 'hmbkp_webhooks_filter_lang_dir', HMBKP_CW_PLUGIN_PATH . '/languages/' ) );
	}


	// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
	define( 'HMBKP_CW_STORE_URL', 'https://bwp.hmn.md' );

	// the name of your product. This should match the download name in EDD exactly.
	define( 'HMBKP_CW_ADDON_NAME', 'BackUpWordPress Custom Webhooks' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

	if ( ! defined( 'HMBKP_CW_PLUGIN_VERSION' ) ) {
		define( 'HMBKP_CW_PLUGIN_VERSION', '1.0' );
	}

	if ( ! class_exists( 'HMBKP_SL_Plugin_Updater' ) ) {
		include( trailingslashit( dirname( __FILE__ ) ) . 'assets/edd-plugin-updater/HMBKP-SL-Plugin-Updater.php' );
	}

	// retrieve our license key from the DB
	$settings = hmbkpp_webhooks_fetch_settings();

	$license_key = $settings['license_key'];

	// setup the updater
	$edd_updater = new HMBKP_SL_Plugin_Updater( HMBKP_CW_STORE_URL, __FILE__, array(
			'version'   => HMBKP_CW_PLUGIN_VERSION, // current version number
			'license'   => $license_key, // license key (used get_option above to retrieve from DB)
			'item_name' => HMBKP_CW_ADDON_NAME, // name of this plugin
			'author'    => 'Human Made Limited' // author of this plugin
		)
	);

	if ( is_admin() ) {
		hmbkpp_webhooks_admin();
	}

}

add_action( 'plugins_loaded', 'hmbkp_webhooks_plugin_setup' );

/**
 * Loads the plugin text domain for translation
 * This setup allows a user to just drop his custom translation files into the WordPress language directory
 * Files will need to be in a subdirectory with the name of the textdomain 'backupwordpress'
 */
function hmbkp_webhooks_plugin_textdomain() {

	/** Set unique textdomain string */
	$textdomain = 'backupwordpress';

	/** The 'plugin_locale' filter is also used by default in load_plugin_textdomain() */
	$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

	/** Set filter for WordPress languages directory */
	$hmbkp_webhooks_wp_lang_dir = apply_filters(
		'hmbkp_webhooks_filter_wp_lang_dir',
		trailingslashit( WP_LANG_DIR ) . trailingslashit( $textdomain ) . $textdomain . '-' . $locale . '.mo'
	);

	/** Translations: First, look in WordPress' "languages" folder = custom & update-secure! */
	load_textdomain( $textdomain, $hmbkp_webhooks_wp_lang_dir );

	/** Translations: Secondly, look in plugin's "languages" folder = default */
	load_plugin_textdomain( $textdomain, false, HMBKP_CW_PLUGIN_LANG_DIR );

}
