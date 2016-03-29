<?php
/**
 Plugin Name: WebDAM Asset Chooser
 Plugin URI: http://webdam.com/
 Description: Import WebDAM assets into WordPress.
 Version: 1.2.0
 Author: WebDAM, PMC, Amit Gupta, James Mehorter
 Author URI: http://webdam.com/
*/

namespace Webdam;

define( 'WEBDAM_PLUGIN_VERSION', '1.2.0' );
define( 'WEBDAM_PLUGIN_DIR', __DIR__ );
define( 'WEBDAM_PLUGIN_SLUG', 'webdam-asset-chooser' );

require_once __DIR__ . '/includes/helpers.php';

define( 'WEBDAM_PLUGIN_URL', webdam_get_plugin_url() );

require_once __DIR__ . '/includes/class-admin-settings.php';
require_once __DIR__ . '/includes/class-api.php';
require_once __DIR__ . '/includes/class-asset-chooser.php';

// EOF