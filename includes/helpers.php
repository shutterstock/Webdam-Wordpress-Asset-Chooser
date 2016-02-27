<?php
/**
 * GENERIC HELPERS
 */

/**
 * Check whether is it ok to load up plugin functionality or not.
 *
 * @param null
 *
 * @return boolean Returns TRUE if its ok to load up plugin functionality else FALSE
 */
function webdam_get_settings() {

	$settings = get_option( 'webdam_settings' );

	//@todo better way to verify that we have good settings

	if ( ! empty( $settings ) && is_array( $settings ) ) {
		if ( ! empty( $settings['webdam_account_domain'] ) && ! empty( $settings['api_client_id'] ) && ! empty( $settings['api_client_id'] ) ) {

			return $settings;

		}
	}

	return false;
}

/**
 * API HELPERS
 */

/**
 * Fetch asset metadata from WebDAM
 *
 * @param array $asset_ids An array of assets to fetch meta for.
 *                         e.g. array( XXXXXXX, XXXXXX, ... )
 *
 * @return array|bool Array of metadata on success, false on failure
 */
function webdam_get_asset_metadata( $asset_ids = array() ) {

	$asset_ids = (array) $asset_ids;

	$asset_meta = Webdam\API::get_instance()->get_asset_metadata( $asset_ids );

	if ( $asset_meta ) {
		return $asset_meta;
	}

	return false;
}

/**
 * Get the authorization url
 *
 * @param null
 *
 * @return string The authorization URL
 */
function webdam_get_authorization_url() {

	$api = Webdam\API::get_instance();

	return $api->get_authorization_url();
}

/**
 * Is the WebDAM API authenticated?
 *
 * @param null
 *
 * @return bool True if the API is authenticated, false if it is not.
 */
function webdam_is_authenticated() {

	$api = Webdam\API::get_instance();

	if ( $api->is_authenticated() ) {
		return true;
	}

	return false;
}

// EOF