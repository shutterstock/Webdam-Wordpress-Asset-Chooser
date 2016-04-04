<?php

namespace Webdam;

/**
 * TinyMCE Asset Chooser
 *
 * Creates a button on post editors to browse and select
 * any of your WebDAM images.
 *
 * Sideloads selected images into your media library and fetches
 * as much metadata about the image as possible from the WebDAM API.
 */
class Asset_Chooser {

	/**
	 * @var Used to store an internal reference for the class
	 */
	private static $_instance;

	/**
	 * Fetch THE singleton instance of this class
	 *
	 * @param null
	 *
	 * @return Asset_Chooser object instance
	 */
	static function get_instance() {

		if ( empty( static::$_instance ) ){

			self::$_instance = new self();
		}

		// Return the single/cached instance of the class
		return self::$_instance;
	}

	/**
	 * Handles registering hooks that initialize this plugin.
	 *
	 * @param null
	 *
	 * @return null
	 */
	protected function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );

		add_filter( 'allowed_http_origins' , array( $this, 'allowed_http_origins' ) );

		add_action( 'wp_ajax_nopriv_webdam_get_api_response', array( $this, 'ajax_get_api_response' ) );

		// Handle sideloading images from WebDAM
		add_action( 'wp_ajax_pmc-webdam-sideload-image', array( $this, 'handle_ajax_image_sideload' ) );

		//load up plugin functionality only if we have settings
		// and if we are authenticated
		if ( \webdam_get_settings() && \webdam_is_authenticated() ) {

			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'mce_add_button' ) );

			// Load admin variable for the domain in the plugin
			add_action( 'admin_enqueue_scripts', array( $this, 'plugin_load_plugin_vars' ) );
		}
	}

	/**
	 * Allow the WebDAM domain to query our site
	 *
	 * @since 3.4.0
	 *
	 * @param array $allowed_origins {
	 *     Default allowed HTTP origins.
	 *     @type string Non-secure URL for admin origin.
	 *     @type string Secure URL for admin origin.
	 *     @type string Non-secure URL for home origin.
	 *     @type string Secure URL for home origin.
	 * }
	 *
	 * @return array The possibly modified array of allowed origins
	 */
	function allowed_http_origins( $allowed_origins ) {

		$settings = webdam_get_settings();

		$allowed_origins[] = webdam_get_site_protocol() . $settings['webdam_account_domain'];

		return $allowed_origins;
	}

	/**
	 * Render out a mock API response for WebDAM to consume
	 *
	 * WebDAM doesn't allow us to simply pass an access_token
	 * in the asset chooser iFrame URL. Instead, the &tokenpath=
	 * query var in the URL itself passes a URL which WebDAM can
	 * query via AJAX to obtain the credentials needed to authenticate.
	 *
	 * On WebDAM's side they're expecting to receive the full API
	 * response given when you authenticate with the API. This
	 * is quite dumb but that's how their system is setup.
	 *
	 * This mock JSON output matches the same output we recieve
	 * in the class-api.php:do_authentication() function, but uses
	 * the access and refresh tokens we already have.
	 *
	 * @param null
	 *
	 * @return null
	 */
	function ajax_get_api_response() {

		$mock_api_response = array(
			'access_token' => webdam_get_current_access_token(),
			'expires_in' => 3600,
			'token_type' => 'bearer',
			'scope' => null,
			'refresh_token' => webdam_get_current_refresh_token()
		);

		wp_send_json( $mock_api_response );

		die();
	}

	/**
	 * Enqueue any scripts or styles
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function action_wp_enqueue_scripts() {

		if ( is_admin() ) {

		} else {

			// Enqueue the webdam imported asset CSS
			wp_enqueue_style(
				'webdam-imported-asset',
				WEBDAM_PLUGIN_URL . 'assets/webdam-imported-asset.css',
				array(),
				false,
				'screen'
			);
		}
	}

	/**
	 * Enqueues the JS which loads the domain name
	 *
	 * @todo localize vars
	 * @todo Move status markup into _ template
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function plugin_load_plugin_vars() {

		global $post;

		$screen = get_current_screen();

		// Only output the following <script> on edit/new post screens
		if ( 'post' !== $screen->base ) {
			return;
		}

		$settings = webdam_get_settings();

		$domain_path = $settings['webdam_account_domain'];

		if ( false === strpos( $domain_path, '://' ) ) {
			$domain_path = webdam_get_site_protocol() . $domain_path;
		}

		// Get the site URL for the WebDAM cookie setting
		// document.domain may be different than the admin domain
		// in set-cookie.html we need to set the cookie with
		// the domain used by the admin.

		// If WEBDAM_PLUGIN_DIR is:
		// /srv/www/wp-content-sites/pmcvip-831/themes/vip/pmc-plugins/
		// The following will strip off /srv/www/
		$plugin_path = str_replace( $_SERVER['DOCUMENT_ROOT'], '', WEBDAM_PLUGIN_DIR );

		// The following return_url will look something like:
		// http://pmcvip-831.wwd.qa.pmc.com/wp-content-sites/pmcvip-831/themes/vip/pmc-plugins/webdam-asset-chooser/includes/set-cookie.html
		$return_url = webdam_get_site_protocol() . $_SERVER['HTTP_HOST'] . $plugin_path . '/includes/set-cookie.html';
		
		wp_enqueue_script( 'underscore' );

		wp_enqueue_style(
			'webdam-chooser-styles',
			WEBDAM_PLUGIN_URL . 'assets/assetchooser.css',
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
		<script type="text/template" id="webdam-insert-image-template">
			[caption id="attachment_<%- attachment_id %>" align="alignnone" class="webdam-imported-asset"]<img class="size-full wp-image-<%- attachment_id %> webdam-imported-asset" src="<%- source %>" alt="<%- alttext %>" width="<%- width %>" height="<%- height %>" /><%- title %> - <%- caption %>[/caption]
		</script>
		<script type="text/javascript">
			var webdam_sideload_nonce = <?php echo wp_json_encode( wp_create_nonce( 'webdam_sideload_image' ) ); ?>;
			var post_id = <?php echo wp_json_encode( $post->ID ); ?>;
			var asset_chooser_domain = <?php echo wp_json_encode( $domain_path ); ?>;
			var webdam_return_url = <?php echo wp_json_encode( esc_url_raw( $return_url ) ); ?>;
			var webdam_get_current_api_response_url = <?php echo wp_json_encode( add_query_arg( 'action', 'webdam_get_api_response', admin_url( 'admin-ajax.php' ) ) ); ?>;
		</script>
		<?php
	}

	/**
	 * Initialize TinyMCE table plugin and custom TinyMCE plugin
	 *
	 * @param array $plugin_array Array of TinyMCE plugins
	 * @return array Array of TinyMCE plugins
	 */
	public function mce_external_plugins( $plugin_array ) {
		$plugin_array['webdam_asset_chooser'] = WEBDAM_PLUGIN_URL . 'assets/assetchooser-loader.js';
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
	 *
	 * @param null
	 *
	 * @handles $_POST intercept and processing for the
	 *			pmc-webdam-sideload-image AJAX action
	 *
	 * @response JSON object containing status and returned data
	 * @return null
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
		$local_image_source  = media_sideload_image( $webdam_asset_url, $post_id, '', 'src' );

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
		$webdam_image_meta = \webdam_get_asset_metadata( $webdam_asset_id );

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
		if ( is_wp_error( $local_image_source ) ) {

			wp_send_json_error( array( 'Unable to sideload image.' ) );

		} else {

			if ( false !== $webdam_image_meta ) {

				wp_send_json_success( array(
					'source'        => $local_image_source,
					'alttext'       => $post_alttext,
					'attachment_id' => $attachment_id,
					'width'         => $wordpress_image_meta['width'],
					'height'        => $wordpress_image_meta['height'],
					'title'         => $post_title,
					'caption'       => $post_content,
				) );

			} else {

				wp_send_json_error( array( 'Unable to obtain meta data for image.' ) );

			}
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

Asset_Chooser::get_instance();

//EOF