<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/public
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Facebook_Importer_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $basename    The ID of this plugin.
	 */
	private string $basename;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private string $version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Facebook_Importer $main The main class.
     */
    private Wp_Facebook_Importer $main;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name The name of the plugin.
	 * @param string $version    The version of this plugin.
	 *@since    1.0.0
	 */
	public function __construct(string $plugin_name, string $version, Wp_Facebook_Importer $main ) {

		$this->basename = $plugin_name;
		$this->version = $version;
        $this->main = $main;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Facebook_Importer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Facebook_Importer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->basename, plugin_dir_url( __FILE__ ) . 'css/wp-facebook-importer-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Facebook_Importer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Facebook_Importer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->basename, plugin_dir_url( __FILE__ ) . 'js/wp-facebook-importer-public.js', array( 'jquery' ), $this->version, false );

        wp_register_script($this->basename.'-endpoint-localize', '', [], $this->version, true);
        wp_enqueue_script($this->basename.'-endpoint-localize');
        wp_localize_script($this->basename.'-endpoint-localize',
            'FBIMRestObj',
            array(
                'get_url' => esc_url_raw(rest_url('fb-importer/v2/')),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );
	}

}
