<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Facebook_Importer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'fb_api_settings';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);

$table_name = $wpdb->prefix . 'fb_api_imports';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);

delete_option("wp-facebook-importer/jal_db_version");
delete_option('fb_importer_user_role');
delete_option('fb_cronjob_settings');
delete_option('wp-facebook-importer_update_config');

//DELETE FP-CUSTOM POST-TYPE
if( !function_exists( 'plugin_prefix_unregister_post_type' ) ) {
    function plugin_prefix_unregister_post_type(){
        unregister_post_type( 'wp_facebook_posts' );
    }
}

add_action('init','plugin_prefix_unregister_post_type');

wp_clear_scheduled_hook('fb_api_plugin_sync');