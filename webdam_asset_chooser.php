<?php
/**
 Plugin Name: WebDAM Asset Chooser
 Plugin URI: http://webdam.com/
 Description: Allows you to view and add assets from WebDAM to Wordpress.
 Version: 1.0
 Author: WebDAM
 Author URI: http://webdam.com/
*/

class WebDAM_Asset_Chooser {

	/**
	 * Handles initializing this class and returning the singleton instance after it's been cached.
	 *
	 * @return null|MCE_Table_Buttons
	 */
	public static function get_instance() {
		// Store the instance locally to avoid private static replication
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
			self::_setup_plugin();
		}

		return $instance;
	}

	/**
	 * An empty constructor
	 */
	public function __construct() { /* Purposely do nothing here */ }

	/**
	 * Handles registering hooks that initialize this plugin.
	 */
	public static function _setup_plugin() {
		add_filter('mce_external_plugins', array(__CLASS__, 'mce_external_plugins'));
		add_filter('mce_buttons', array(__CLASS__, 'mce_add_button'));

		// Admin settings for plugin
		add_action('admin_menu', array(__CLASS__, 'plugin_admin_add_page'));
		add_action('admin_init', array(__CLASS__, 'plugin_admin_init'));

		// Load admin variable for the domain in the plugin
		add_action('admin_enqueue_scripts', array(__CLASS__, 'plugin_load_plugin_vars'));
	}

	/**
	 *	Enqueues the JS which loads the domain name
	 */
	public static function plugin_load_plugin_vars() {
		$options = get_option('plugin_options');
		?>
		<script type="text/javascript">
		var asset_chooser_domain = <?= json_encode( $options['domain_path'] ); ?>;
		</script>
		<?php
	}

	/**
	 *	Sets up the settings page for the WebDAM admin
	 */
	public static function plugin_admin_add_page() {
		add_options_page('WebDAM Asset Chooser', 'WebDAM', 'manage_options', 'plugin', array(__CLASS__, 'plugin_options_page'));
	}

	/**
	 *	Markup for the settings page
	 */
	public static function plugin_options_page() {
		?>
		<div class="wrap">
		<h2>WebDAM Asset Chooser Settings</h2>
		This page allows you to set up your WebDAM Asset Chooser.
		<form action="options.php" method="post">
			<?php settings_fields('plugin_options'); ?>
			<?php do_settings_sections('plugin'); ?>

			<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
		</form></div>

		<?php
	}

	/**
	 *	Initializes the WebDAM admin section
	 */
	public static function plugin_admin_init() {
		register_setting('plugin_options', 'plugin_options', array(__CLASS__, 'plugin_options_validate' ));
		add_settings_section('plugin_main', '', '', 'plugin');
		add_settings_field('plugin_domain_path', 'Your WebDAM Account', array(__CLASS__, 'plugin_setting_string'), 'plugin', 'plugin_main');
	}


	/**
	 *	Displays the string which represents the WebDAM domain
	 */
	function plugin_setting_string() {
		$options = get_option('plugin_options');
?>
		<input id="plugin_domain_path" name="plugin_domain_path" size="40" type="text" value="<?php echo esc_attr( $options['domain_path'] ); ?>" /><br />
		<p class="description">Your account URL, e.g. 'http://domain.webdamdb.com'</p>
<?php
	}

	/**
	 *	Simple validation for the domain, can be enhanced
	 */
	public static function plugin_options_validate($input) {
		$options = get_option('plugin_options');
		$trimmed = trim($input['domain_path']);

		// omit the last '/' if present
		$trimmed = untrailingslashit( $trimmed );

		$options['domain_path'] = $trimmed;
		return $options;
	}

	/**
	 * Initialize TinyMCE table plugin and custom TinyMCE plugin
	 *
	 * @param array $plugin_array Array of TinyMCE plugins
	 * @return array Array of TinyMCE plugins
	 */
	public static function mce_external_plugins( $plugin_array ) {
		$plugin_array['webdam_asset_chooser'] = plugins_url( 'assets/assetchooser-loader.js', __FILE__ );
		return $plugin_array;
	}

	/**
	 * Add TinyMCE table control buttons
	 *
	 * @param array $buttons Buttons for the second row
	 * @return array Buttons for the second row
	 */
	public static function mce_add_button( $buttons ) {
		array_push( $buttons, "separator", 'btnWebDAMAssetChooser' );
        return $buttons;
	}
}

WebDAM_Asset_Chooser::get_instance();

//EOF