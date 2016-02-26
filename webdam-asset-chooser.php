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
require_once __DIR__ . '/includes/class-admin-settings.php';
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

	/**
	 * Ensure we always have a cache of the WebDAM API
	 *
	 * If no cache is present, create an instance of the API
	 *
	 * @param bool $refresh_cache Pass true to force a cache refresh
	 *
	 * @return null
	 */
	public function cache_webdam_api( $refresh_cache = false ) {

		require __DIR__ . '/vendor/shutterstock/presto.php';
		require __DIR__ . '/vendor/webdam-php-wrapper/response.php';
		require __DIR__ . '/vendor/webdam-php-wrapper/api-client.php';

		$client_id = "b632bb166bf7bdc2370e363d0eb87e70cee3bc2b";
		$client_secret = "3fa40ec908a73b89192ab8f1c5d866ebdb025bf2";
		$username = "wwd-user";
		$password = "SUmGOhy27QaINXRL7ecDdw==";

		// Create instance of REST client
		$presto = new Presto\Presto( array( CURLOPT_VERBOSE => true ) );
		$response = new bbaisley\Response();

		$api = wp_cache_get( 'webdam_api_instance' );

		if ( false === $api || $refresh_cache ) {

			$api = new bbaisley\Api( $client_id, $client_secret, $presto, $response );

			$access_token_response = $api->getAccessTokenUsingPassword( $username, $password );

			if ( 200 === $access_token_response->meta['http_code'] ) {

				wp_cache_set( 'webdam_api_instance', $api );
			}
		}
	}

	/**
	 * Grab an up-to-date instance of the api
	 *
	 * Attempt to fetch a cache of the api, and refresh the access
	 * token if it's expired
	 *
	 * @return bbaisley\Api An instance of the WebDAM API
	 */
	public function get_webdam_api() {

		$api = wp_cache_get( 'webdam_api_instance' );

		if ( false === $api || $api->isAccessTokenExpired() ) {

			$api->refreshAccess();

		}

		return $api;
	}

	/**
	 * Fetch asset metadata from WebDAM
	 *
	 * @param array $asset_ids
	 *
	 * @return array|bool Array of metadata on success, false on failure
	 */
	public function get_webdam_asset_metadata( $asset_ids = array() ) {

		$asset_ids = (array) $asset_ids;

		$webdam_api = $this->get_webdam_api();

		$asset_meta = $webdam_api->getAssetMetadata( $asset_ids );

		if ( 200 === $asset_meta->meta['http_code'] ) {

			return $asset_meta->data;

		} else {

			return false;

		}
	}

}

WebDAM_Asset_Chooser::get_instance();

//EOF