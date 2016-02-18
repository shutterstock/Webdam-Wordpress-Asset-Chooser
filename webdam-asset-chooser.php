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
		add_action( 'admin_init', array( $this, 'cache_webdam_api' ), 10, 0 );

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

		// Handle sideloading images from WebDAM
		add_action( 'wp_ajax_pmc-webdam-sideload-image', array( $this, 'handle_ajax_image_sideload' ) );
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

		global $post;

		$screen = get_current_screen();

		// Only output the following <script> on edit/new post screens
		if ( 'post' !== $screen->base ) {
			return;
		}

		$domain_path = get_option( self::PLUGIN_ID . '-domain_path' );

		wp_enqueue_style(
			'webdam-chooser-styles',
			plugins_url( 'assets/assetchooser.css', __FILE__ ),
			array(),
			false,
			'screen'
		);
		?>

		<div class="webdam-asset-chooser-status">
			<div class="working">
				<?php esc_html_e( 'Importing your WebDAM selection..', 'PMC' ); ?>
				<img src="/wp-includes/js/thickbox/loadingAnimation.gif" alt="Waiting" />
			</div>
			<div class="done"></div>
		</div>
		<script type="text/javascript">
			var webdam_sideload_nonce = <?php echo wp_json_encode( wp_create_nonce( 'webdam_sideload_image' ) ); ?>;
			var post_id = <?php echo wp_json_encode( $post->ID ); ?>;
			var asset_chooser_domain = <?php echo wp_json_encode( $domain_path ); ?>;
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

	/**
	 * Sideload the remote WebDAMN image into WP's media library
	 *
	 * This is executed over AJAX from client-side when an image is chosen
	 * in the WebDAM interface.
	 */
	public function handle_ajax_image_sideload() {

		// Verify doing ajax
		if ( ! defined( 'DOING_AJAX' ) && ! DOING_AJAX ) {
			return;
		}

		// Verify our nonce to ensure safe origin
		check_ajax_referer( 'webdam_sideload_image', 'nonce' );

		// Verify we've got the data we need to proceed
		if ( empty( $_POST['post_id'] ) ) {
			wp_send_json_error( array( 'No post ID provided.' ) );
		}

		if ( empty( $_POST['webdam_asset_url'] ) ) {
			wp_send_json_error( array( 'No image source provided.' ) );
		}

		// Sanitize our input
		$post_id          = (int) $_POST['post_id'];
		$webdam_asset_id  = (int) $_POST['webdam_asset_id'];
		$webdam_asset_url = esc_url_raw( $_POST['webdam_asset_url'] );
		$webdam_asset_filename = sanitize_file_name( $_POST['webdam_asset_filename'] );

		// Adjust the remote image url so we receive the largest image possible
		$webdam_asset_url = str_replace( 'md_', '1280_', $webdam_asset_url );

		// Hook into add_attachment so we can obtain the sideloaded image ID
		// media_sideload_image does not return the ID, which sucks.
		add_action( 'add_attachment', array( $this, 'add_attachment' ), 10, 1 );

		// Sideload the image into WP
		$local_image_url  = media_sideload_image( $webdam_asset_url, $post_id, '', 'src' );

		// Grab the sideloaded image ID we just set via the
		// add_attachment actionm hook
		$attachment_id = get_post_meta( $post_id, 'webdam_attachment_id_tmp', true );

		// We don't need this any longer—let's ditch it.
		delete_post_meta( $post_id, 'webdam_attachment_id_tmp' );

		// Grab the current image metadata
		$wordpress_image_meta = wp_get_attachment_metadata( $attachment_id );

		// Fetch metadata for the image
		// Some images contain embeded metadata, but that is unreliable
		// and often not present. We could create code to check existing data,
		// and fetch what's needed, but the likelihood of images with data
		// is slim, and depends on the photographer.
		$webdam_image_meta = $this->get_webdam_asset_metadata( $webdam_asset_id );

		// Set the initial alttext
		$post_alttext = '';

		if ( ! empty( $wordpress_image_meta['image_data']['title'] ) ) {
			$post_alttext = $wordpress_image_meta['image_data']['title'];
		}

		if ( false !== $webdam_image_meta ) {

			$post_title = $post_content = $post_excerpt = $post_credit = '';

			if ( ! empty( $webdam_image_meta->headline ) ) {
				$post_title = $webdam_image_meta->headline;
				$post_alttext = $webdam_image_meta->headline;
			}

			if ( ! empty( $webdam_image_meta->caption ) ) {
				$post_content = $webdam_image_meta->caption;
				$post_excerpt = $webdam_image_meta->caption;
			}

			if ( ! empty( $webdam_image_meta->byline ) ) {
				$post_credit = $webdam_image_meta->byline;
			}

			// Set the attachment post attributes
			wp_update_post( array(
				'ID'           => $attachment_id,
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_excerpt' => $post_excerpt,
			) );

			// Set the attachment post meta values
			$attachment_post_metas = array(
				'_wp_attachment_image_alt' => $post_alttext,
				'_image_credit'            => $post_credit,
				'_webdam_asset_id'         => $webdam_asset_id,
				'_webdam_asset_filename'   => $webdam_asset_filename,
			);

			foreach ( $attachment_post_metas as $meta_key => $meta_value ) {
				update_post_meta( $attachment_id, $meta_key, $meta_value );
			}

			// Merge the existing metadata (that WP found embedded within the image)
			// with the metadata from the WebDAM API
			if ( ! empty( $wordpress_image_meta['image_data'] ) && is_array( $wordpress_image_meta['image_data'] ) ){

				$wordpress_image_meta['image_data'] = array_merge( $wordpress_image_meta['image_data'], (array) $webdam_image_meta );

			} else {

				$wordpress_image_meta['image_data'] = (array) $webdam_image_meta;

			}

			// Update the metadata stored for the image by WordPress
			wp_update_attachment_metadata(
				$attachment_id,
				$wordpress_image_meta
			);
		}

		// Return the local image url on success
		// ..error message on failure
		if ( is_wp_error( $local_image_url ) ) {

			wp_send_json_error( array( 'Unable to sideload image.' ) );

		} else {

			wp_send_json_success( array( 'url' => $local_image_url, 'alttext' => $post_alttext ) );

		}
	}

	/**
	 * @param bool $refresh_cache
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
	 * @return bool|mixed
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

	/**
	 * Helper to obtain sideloaded image ID
	 *
	 * We add this hook before calling media_sideload_image,
	 * and remove it immediately afterwards. This allows us to
	 * capture the newly sideloaded attachment ID. In this context
	 * we can obtain the post_parent and use that to set post meta
	 * on the parent post, which contains the attachment ID.
	 *
	 * It's a little hacky, but by doing so we can call get_post_meta
	 * after calling media_sideload_image to obtain the new attachment ID
	 *
	 * @internal Called via add_attachment action hook
	 *
	 * @param $attachment_id The ID of the newly inserted attachment image
	 *
	 * @return null
	 */
	public function add_attachment( $attachment_id ) {

		// Remove this hook callback so it doesn't fire again
		// We only want this to fire once, right when we're sideloading
		// the image into WP.
		remove_action( 'add_attachment', array( $this, 'add_attachment' ), 10, 1 );

		// Fetch the attachment's post so we may obtain it's parent ID
		// When we call media_sideload_image we specify the original post's ID
		// so that the attachment will be attached to the post.
		$attachment = get_post( $attachment_id );

		// Set temporary post meta on the parent post so we may obtain the
		// attachment id via get_post_meta immediately after calling
		// media_sideload_image()
		add_post_meta( $attachment->post_parent, 'webdam_attachment_id_tmp', $attachment_id );
	}
}

WebDAM_Asset_Chooser::get_instance();

//EOF