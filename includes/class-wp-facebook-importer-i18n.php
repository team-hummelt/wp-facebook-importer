<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Facebook_Importer_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-facebook-importer',
			false,
			dirname(plugin_basename(__FILE__), 2) . '/languages/'
		);

	}



}
