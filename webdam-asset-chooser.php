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
	 * Handles registering hooks that initialize this plugin.
	 */
	protected function _setup_plugin() {
		add_action( 'admin_init', array( $this, 'plugin_admin_init' ) );

		// Admin settings for plugin
		add_action( 'admin_menu', array( $this, 'plugin_admin_add_page' ) );

		//load up plugin functionality only if domain path is saved in options
		if ( $this->_is_ok_to_load() ) {

			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'mce_add_button' ) );

			// Load admin variable for the domain in the plugin
			add_action( 'admin_enqueue_scripts', array( $this, 'plugin_load_plugin_vars' ) );

		} else {
			//domain path not saved in plugin options, show an admin notice
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
		}

		printf( '<div class="error"><p><strong>Please update <a href="%s">WebDAM options</a> with your account URL.</strong></p></div>', esc_url( admin_url( 'options-general.php?page=' . self::PLUGIN_ID . '-plugin' ) ) );
	}

	/**
	 *	Enqueues the JS which loads the domain name
	 */
	public function plugin_load_plugin_vars() {
		$domain_path = get_option( self::PLUGIN_ID . '-domain_path' );
		?>
		<script type="text/javascript">
		var asset_chooser_domain = '<?php echo esc_js( $domain_path ); ?>';
		</script>
		<?php
	}

	/**
	 *	Sets up the settings page for the WebDAM admin
	 */
	public function plugin_admin_add_page() {
		add_options_page( 'WebDAM Asset Chooser', 'WebDAM', 'manage_options', self::PLUGIN_ID . '-plugin', array( $this, 'plugin_options_page' ) );
	}

	/**
	 *	Markup for the settings page
	 */
	public function plugin_options_page() {
		?>
		<div class="wrap">
		<h2>WebDAM Asset Chooser Settings</h2>
		This page allows you to set up your WebDAM Asset Chooser.
		<form action="options.php" method="post">
			<?php settings_fields( self::PLUGIN_ID . '-domain_path' ); ?>
			<?php do_settings_sections( self::PLUGIN_ID . '-plugin' ); ?>

			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form></div>

		<?php
	}

	/**
	 *	Initializes the WebDAM admin section
	 */
	public function plugin_admin_init() {
		register_setting( self::PLUGIN_ID . '-domain_path', self::PLUGIN_ID . '-domain_path', array( $this, 'plugin_options_validate' ) );
		add_settings_section( self::PLUGIN_ID . '-plugin_main', '', '', self::PLUGIN_ID . '-plugin' );
		add_settings_field( self::PLUGIN_ID . '-plugin_domain_path', 'Your WebDAM Account', array( $this, 'plugin_setting_string' ), self::PLUGIN_ID . '-plugin', self::PLUGIN_ID . '-plugin_main' );
	}


	/**
	 *	Displays the string which represents the WebDAM domain
	 */
	public function plugin_setting_string() {
		$domain_path = get_option( self::PLUGIN_ID . '-domain_path' );
?>
		<input id="<?php echo esc_attr( self::PLUGIN_ID . '-domain_path' ); ?>" name="<?php echo esc_attr( self::PLUGIN_ID . '-domain_path' ); ?>" size="40" type="text" value="<?php echo esc_attr( $domain_path ); ?>" />
		<p class="description">Your account URL, e.g. 'http://domain.webdamdb.com'</p>
<?php
	}

	/**
	 *	Simple validation for the domain, can be enhanced
	 */
	public function plugin_options_validate( $domain_path ) {

		if ( empty( $domain_path ) || ! is_string( $domain_path ) || filter_var( trim( $domain_path ), FILTER_VALIDATE_URL ) === false ) {
			//not a valid domain URL, bail out
			return '';
		}

		$url_parts = parse_url( trim( $domain_path ) );

		if ( ! is_array( $url_parts ) || empty( $url_parts ) || empty( $url_parts['scheme'] ) || empty( $url_parts['host'] ) ) {
			return '';
		}

		return sprintf( '%s://%s', $url_parts['scheme'], $url_parts['host'] );

	}

	/**
	 * Initialize TinyMCE table plugin and custom TinyMCE plugin
	 *
	 * @param array $plugin_array Array of TinyMCE plugins
	 * @return array Array of TinyMCE plugins
	 */
	public function mce_external_plugins( $plugin_array ) {
		$plugin_array['webdam_asset_chooser'] = plugins_url( 'assets/assetchooser-loader.js', __FILE__ );
		return $plugin_array;
	}

	/**
	 * Add TinyMCE table control buttons
	 *
	 * @param array $buttons Buttons for the second row
	 * @return array Buttons for the second row
	 */
	public function mce_add_button( $buttons ) {
		array_push( $buttons, "separator", 'btnWebDAMAssetChooser' );
        return $buttons;
	}
}

WebDAM_Asset_Chooser::get_instance();

//EOF