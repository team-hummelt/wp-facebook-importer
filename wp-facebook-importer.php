<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wwdh.de
 * @since             1.0.0
 * @package           Wp_Facebook_Importer
 *
 * @wordpress-plugin
 * Plugin Name:       WP Facebook Importer
 * Plugin URI:        https://wwdh.de/plugins
 * Description:       Import and sync Facebook API posts.
 * Version:           1.0.1
 * Author:            Jens Wiecker
 * Author URI:        https://wwdh.de
 * License:           MIT License
 * Text Domain:       wp-facebook-importer
 * Domain Path:       /languages
 * Requires PHP:      7.4
 * Requires at least: 5.6
 * Tested up to:      5.9.3
 * Stable tag:        1.0.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently DATABASE VERSION
 * @since             1.0.0
 */
const WP_FACEBOOK_IMPORTER_DB_VERSION = '1.0.4';

/**
 * MIN PHP VERSION for Activate
 * @since             1.0.0
 */
const WP_FACEBOOK_IMPORTER_PHP_VERSION = '7.4';

/**
 * MIN WordPress VERSION for Activate
 * @since             1.0.0
 */
const WP_FACEBOOK_IMPORTER_WP_VERSION = '5.6';

/**
 * PLUGIN ROOT PATH
 * @since             1.0.0
 */
define('WP_FACEBOOK_IMPORTER_PLUGIN_DIR', dirname(__FILE__));

/**
 * PLUGIN URL
 * @since             1.0.0
 */
define('WP_FACEBOOK_IMPORTER_PLUGIN_URL', plugins_url('wp-facebook-importer'));

/**
 * PLUGIN SLUG
 * @since             1.0.0
 */
define('WP_FACEBOOK_IMPORTER_SLUG_PATH', plugin_basename(__FILE__));
define('WP_FACEBOOK_IMPORTER_BASENAME', plugin_basename(__DIR__));


/**
 * PLUGIN ADMIN DIR
 * @since             1.0.0
 */
define('WP_FACEBOOK_IMPORTER_PLUGIN_ADMIN_DIR', dirname(__FILE__). DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-facebook-importer-activator.php
 */
function activate_wp_facebook_importer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-facebook-importer-activator.php';
	Wp_Facebook_Importer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-facebook-importer-deactivator.php
 */
function deactivate_wp_facebook_importer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-facebook-importer-deactivator.php';
	Wp_Facebook_Importer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_facebook_importer' );
register_deactivation_hook( __FILE__, 'deactivate_wp_facebook_importer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-facebook-importer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

global $wp_facebook_importer_plugin;
$wp_facebook_importer_plugin = new Wp_Facebook_Importer();
$wp_facebook_importer_plugin->run();



