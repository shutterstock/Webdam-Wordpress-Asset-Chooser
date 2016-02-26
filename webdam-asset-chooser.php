<?php
/**
 Plugin Name: WebDAM Asset Chooser
 Plugin URI: http://webdam.com/
 Description: Allows you to view and add assets from WebDAM to Wordpress.
 Version: 1.1
 Author: WebDAM, PMC, Amit Gupta
 Author URI: http://webdam.com/
*/

class WebDAM_Asset_Chooser {

	const PLUGIN_ID = 'webdam-asset-chooser';

	/**
	 * @var WebDAM_Asset_Chooser Singleton instance of class
	 */
	private static $_instance;
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/class-admin-settings.php';
require_once __DIR__ . '/includes/class-api.php';
require_once __DIR__ . '/includes/class-asset-chooser.php';

	/**
	 * Handles initializing this class and returning the singleton instance after it's been cached.
	 *
	 * @return null|MCE_Table_Buttons
	 */
	public static function get_instance() {
		$class = get_called_class();

		if ( ! is_a( static::$_instance , 'WebDAM_Asset_Chooser' ) ) {
			static::$_instance = new $class();
		}

		return static::$_instance;
	}

	/**
	 * An empty constructor
	 */
	protected function __construct() {
		$this->_setup_plugin();
	}

	/**
	 * Check whether is it ok to load up plugin functionality or not.
	 *
	 * @return boolean Returns TRUE if its ok to load up plugin functionality else FALSE
	 */
	protected function _is_ok_to_load() {
		if ( get_option( self::PLUGIN_ID . '-domain_path', false ) !== false ) {
			return true;
		}

		return false;
	}

}

WebDAM_Asset_Chooser::get_instance();

//EOF