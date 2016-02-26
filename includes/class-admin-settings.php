<?php

namespace Webdam;

/**
 * Class Admin
 * @package Webdam
 */
class Admin {

	/**
	 * @var Used to store an internal reference for the class
	 */
	private static $_instance;

	/**
	 * Holds the webdam settings
	 */
	private $options;

	/**
	 * Fetch THE instance of the admin object
	 *
	 * @return Admin object instance
	 */
	static function get_instance( ) {

		if ( empty( static::$_instance ) ){

			self::$_instance = new self();
		}

		// Return the single/cached instance of the class
		return self::$_instance;
	}

	/**
	 * Admin constructor.
	 */
	public function __construct() {

		// Create the Settings > Webdam page
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'create_settings_page_elements' ) );

		// Display a notice when credentials are needed
		if ( ! \webdam_get_settings() ) {
			add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
		}
	}
	
	/**
	 * Show a notice to admin users to update plugin options
	 *
	 * @return void
	 */
	public function show_admin_notice() {
		/*
		 * We want to show notice only to those users who can update options,
		 * for everyone else the notice won't mean much if anything.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		} ?>

		<div class="error">
			<p>
				<strong>
					Please update the <a href="<?php echo esc_url( admin_url( 'options-general.php?page=webdam-settings' ) ) ?>">WebDAM Settings</a> with your information.
				</strong>
			</p>
		</div><?php
	}

	/**
	 * Create the settings page
	 */
	public function add_plugin_page() {

		add_options_page(
			'WebDAM Settings',
			'WebDAM',
			'manage_options',
			'webdam-settings',
			array( $this, 'create_settings_page' )
		);
	}

	/**
	 * Create the settings page contents/form fields
	 */
	public function create_settings_page() {

		// Set class property
		$this->settings = get_option( 'webdam_settings' ); ?>

		<div class="wrap">
			<h2>WebDAM Settings</h2>
			<form method="post" action="options.php"><?php

				// This prints out all hidden setting fields
				settings_fields( 'webdam_settings' );

				do_settings_sections( 'webdam-settings' );

				submit_button(); ?>

			</form>
		</div><?php

	}

	/**
	 * Register page settings, sections, and fields
	 */
	public function create_settings_page_elements() {

		/**
		 * Register the webdam_settings setting
		 */
		register_setting(
			'webdam_settings',
			'webdam_settings',
			array( $this, 'webdam_settings_input_sanitization' )
		);

		/**
		 * WebDAM Account Section
		 */
		add_settings_section(
			'webdam_settings_section_webdam_account',
			'WebDAM Account',
			array( $this, 'webdam_settings_section_webdam_account_info' ),
			'webdam-settings'
		);

		add_settings_field(
			'webdam_account_domain',
			'Domain',
			array( $this, 'webdam_account_domain_input_field' ),
			'webdam-settings',
			'webdam_settings_section_webdam_account'
		);

		add_settings_field(
			'webdam_account_username',
			'Username',
			array( $this, 'webdam_account_username_input_field' ),
			'webdam-settings',
			'webdam_settings_section_webdam_account'
		);

		add_settings_field(
			'webdam_account_password',
			'Password',
			array( $this, 'webdam_account_password_input_field' ),
			'webdam-settings',
			'webdam_settings_section_webdam_account'
		);

		/**
		 * WebDAM API Section
		 *
		 * @todo "Enable WebDAM API Integration" checkbox
		 * "Enable to use features such as sideloading metadata"
		 *
		 * enter client id and secret
		 *
		 * @todo allow grant type selection in admin default should be auth_code but allow fallback to password.
		 * @todo only display auth code method once client id and secret at set
		 * @todo likewise only show username/pass UNLESS we can somehow prepopulate them when the chooser iframe opens
		 * @todo click auth code link (https://apiv2.webdamdb.com/oauth2/authorize?response_type=code&client_id=b632bb166bf7bdc2370e363d0eb87e70cee3bc2b&redirect_uri=wwd.com&state=STATE) redirect uri should be the settings page, grab auth_code out of the URL and save/fetch token with it
		 *
		 */
		add_settings_section(
			'webdam_settings_section_webdam_api',
			'API Settings',
			array( $this, 'webdam_settings_section_webdam_api_info' ),
			'webdam-settings'
		);

		add_settings_field(
			'api_client_id',
			'Client ID',
			array( $this, 'api_client_id_input_field' ),
			'webdam-settings',
			'webdam_settings_section_webdam_api'
		);

		add_settings_field(
			'api_client_secret',
			'Client Secret',
			array( $this, 'api_client_secret_input_field' ),
			'webdam-settings',
			'webdam_settings_section_webdam_api'
		);
	}

	/**
	 * Print the WebDAM Account Section text
	 */
	public function webdam_settings_section_webdam_account_info() {
		esc_html_e( 'Enter your WebDAM Account information below:', 'webdam' );
	}

	/**
	 * Print the API Section text
	 */
	public function webdam_settings_section_webdam_api_info() {
		esc_html_e( 'Enter your API credentials below below:', 'webdam' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function webdam_account_domain_input_field() { ?>
		<input
			type="text"
			id="webdam_account_domain"
			name="webdam_settings[webdam_account_domain]"
			value="<?php echo ! empty( $this->settings['webdam_account_domain'] ) ? esc_attr( $this->settings['webdam_account_domain'] ) : ''; ?>"
			placeholder="yourdomain.webdamdb.com"/><?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function webdam_account_username_input_field() { ?>
		<input
			type="text"
			id="webdam_account_username"
			name="webdam_settings[webdam_account_username]"
			value="<?php echo ! empty( $this->settings['webdam_account_username'] ) ? esc_attr( $this->settings['webdam_account_username'] ) : ''; ?>" /><?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function webdam_account_password_input_field() { ?>
		<input
			type="text"
			id="webdam_account_password"
			name="webdam_settings[webdam_account_password]"
			value="<?php echo ! empty( $this->settings['webdam_account_password'] ) ? esc_attr( $this->settings['webdam_account_password'] ) : ''; ?>" /><?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function api_client_id_input_field() { ?>
		<input
			type="text"
			id="api_client_id"
			name="webdam_settings[api_client_id]"
			value="<?php echo ! empty( $this->settings['api_client_id'] ) ? esc_attr( $this->settings['api_client_id'] ) : ''; ?>" /><?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function api_client_secret_input_field() { ?>
		<input
			type="text"
			id="api_client_secret"
			name="webdam_settings[api_client_secret]"
			value="<?php echo ! empty( $this->settings['api_client_secret'] ) ? esc_attr( $this->settings['api_client_secret']) : ''; ?>" /><?php
	}

	/**
	 * Sanitize each setting field as it's saved
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function webdam_settings_input_sanitization( $input ) {
		$new_settings = array();

		// @todo encrypt the piss outta this stuff for storage
		// ...but in a way we can still retrieve values for sending auth
		// to webdam in the api.

		if( isset( $input['webdam_account_domain'] ) ) {
			$new_settings['webdam_account_domain'] = sanitize_text_field( $input['webdam_account_domain'] );
		}

		if( isset( $input['webdam_account_username'] ) ) {
			$new_settings['webdam_account_username'] = sanitize_text_field( $input['webdam_account_username'] );
		}

		if( isset( $input['webdam_account_password'] ) ) {
			$new_settings['webdam_account_password'] = sanitize_text_field( $input['webdam_account_password'] );
		}
		
		if( isset( $input['api_client_id'] ) ) {
			$new_settings['api_client_id'] = sanitize_text_field( $input['api_client_id'] );
		}

		if( isset( $input['api_client_secret'] ) ) {
			$new_settings['api_client_secret'] = sanitize_text_field( $input['api_client_secret'] );
		}

		// Broadcast that changes are being saved
		if ( ! empty( $new_settings ) ) {
			do_action( 'webdam-saved-new-settings' );
		}

		return $new_settings;
	}
}

Admin::get_instance();

// EOF