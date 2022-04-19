<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 */

use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use FBApiPlugin\SrvApi\Endpoint\Make_Remote_Exec;
use FBApiPlugin\SrvApi\Endpoint\Srv_Api_Endpoint;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use WPFacebook\Importer\Facebook_Import_Api;
use WPFacebook\Importer\Facebook_Importer_Rest_Endpoint;
use WPFacebook\Importer\Import_Api_Cronjob;
use WPFacebook\Importer\Import_Curl_Cronjob_Exec;
use WPFacebook\Importer\Import_WP_Custom_Events;
use WPFacebook\Importer\Import_WP_Custom_Post;
use WPFacebook\Importer\WP_Facebook_Importer_Database;
use WPFacebook\Importer\WP_Facebook_Importer_Helper;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Facebook_Importer
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Wp_Facebook_Importer_Loader $loader Maintains and registers all hooks for the plugin.
     */
    private Wp_Facebook_Importer_Loader $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    private string $plugin_name;

    /**
     * The Public API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $id_rsa plugin API ID_RSA.
     */
    private string $id_rsa;

    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $id_plugin_rsa plugin API ID_RSA.
     */
    private string $id_plugin_rsa;

    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $plugin_api_config plugin API ID_RSA.
     */
    private object $plugin_api_config;


    /**
     * The Public API DIR.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $api_dir plugin API DIR.
     */
    private string $api_dir;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $srv_api_dir plugin Slug Path.
     */
    private string $srv_api_dir;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of the plugin.
     */
    private string $version = '';

    /**
     * The current database version of the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $db_version The current database version of the plugin.
     */
    private string $db_version;

    /**
     * The current settings version of the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $settings_id The current database settings of the plugin.
     */
    private string $settings_id;

    /**
     * The current cronjob ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $cronjob_id The current cronjob ID.
     */
    private string $cronjob_id;

    /**
     * The current cronjob Exec ID.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $cronjob_exec_id The current cronjob ID.
     */
    private string $cronjob_exec_id;

    /**
     * The current cronjob Slug.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $cronjob_slug The current cronjob Slug.
     */
    private string $cronjob_slug;

    /**
     * The Settings for Plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      object $settings The Settings
     */
    private object $settings;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_slug plugin Slug Path.
     */
    private string $plugin_slug;

    /**
     * TWIG autoload for PHP-Template-Engine
     * the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Environment $twig TWIG autoload for PHP-Template-Engine
     */
    private Environment $twig;


    /**
     * FB-API SDK autoload Engine
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Facebook $fbApi FB-API SDK Engine
     */
    protected Facebook $fbApi;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @var object The main class.
     */
    private object $main;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @throws LoaderError
     * @throws FacebookSDKException
     * @since    1.0.0
     */
    public function __construct()
    {

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();


        $this->plugin_name = WP_FACEBOOK_IMPORTER_BASENAME;
        $this->plugin_slug = WP_FACEBOOK_IMPORTER_SLUG_PATH;
        $this->main = $this;

        $plugin = get_file_data(plugin_dir_path(dirname(__FILE__)) . $this->plugin_name . '.php', array('Version' => 'Version'), false);
        if (!$this->version) {
            $this->version = $plugin['Version'];
        }

        if (defined('WP_FACEBOOK_IMPORTER_DB_VERSION')) {
            $this->db_version = WP_FACEBOOK_IMPORTER_DB_VERSION;
        } else {
            $this->db_version = '1.0.0';
        }

        $this->settings_id = 'fFf6CxQhAMJN';
        $this->cronjob_id = 'mfh2bgv3GCG1eaq@npa';
        $this->cronjob_exec_id = 'vjb0dpn@keq2EZB_qxw';
        $this->cronjob_slug = 'cron';

        $this->check_dependencies();
        $this->load_dependencies();

        $twigAdminDir = plugin_dir_path(dirname(__FILE__)) . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR;
        $twig_loader = new FilesystemLoader($twigAdminDir);
        $twig_loader->addPath($twigAdminDir . 'layout', 'layout');
        $twig_loader->addPath($twigAdminDir . 'pages', 'page');
        $twig_loader->addPath($twigAdminDir . 'widgets', 'widget');
        $this->twig = new Environment($twig_loader);


        //JOB SRV API
        $this->srv_api_dir = plugin_dir_path(dirname(__FILE__)) . 'admin' . DIRECTORY_SEPARATOR .'srv-api' . DIRECTORY_SEPARATOR;

        if (is_file($this->srv_api_dir  . 'id_rsa' . DIRECTORY_SEPARATOR . $this->plugin_name.'_id_rsa')) {
            $this->id_plugin_rsa = base64_encode($this->srv_api_dir . DIRECTORY_SEPARATOR . 'id_rsa' . $this->plugin_name.'_id_rsa');
        } else {
            $this->id_plugin_rsa = '';
        }
        if (is_file($this->srv_api_dir  . 'config' . DIRECTORY_SEPARATOR . 'config.json')) {
            $this->plugin_api_config = json_decode( file_get_contents( $this->srv_api_dir  . 'config' . DIRECTORY_SEPARATOR . 'config.json'));
        } else {
            $this->plugin_api_config = (object) [];
        }

        $this->set_locale();
        $this->register_post_selector_database_handle();

        $this->register_plugin_helper_class();
        $this->register_plugin_settings();

        //WordPress CronJob
        $this->register_importer_cronjob_exec_class();

        $this->register_importer_cronjob_class();
        $this->register_fb_importer_rest_endpoint();
        //fb-sdk
        $this->register_fb_api_importer_class();
        $this->register_import_custom_post();
        $this->register_import_custom_events();

        //   $this->register_srv_rest_api_routes();
        $this->register_wp_remote_exec();

        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Wp_Facebook_Importer_Loader. Orchestrates the hooks of the plugin.
     * - Wp_Facebook_Importer_i18n. Defines internationalization functionality.
     * - Wp_Facebook_Importer_Admin. Defines all hooks for the admin area.
     * - Wp_Facebook_Importer_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-facebook-importer-loader.php';

        /**
         * The trait for the default settings
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/trait_wp_facebook_importer_defaults.php';

        /**
         * The  database for the Plugin
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/database/class_wp_facebook_importer_database.php';


        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-facebook-importer-i18n.php';


        /**
         * The code that runs during plugin activation.
         * This action is documented in includes/class-hupa-teams-activator.php
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-facebook-importer-activator.php';

        /**
         * Plugin Helper Class
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class_wp_facebook_importer_helper.php';

        /**
         * FB-API Importer Class
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fb-api-importer/class_facebook_import_api.php';

        /**
         * FB-API Importer WP-Cron
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fb-api-importer/class_import_api_cronjob.php';

        /**
         * Cronjob ausführen
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fb-api-importer/class_import_curl_cronjob_exec.php';


        /**
         * FB-SDK autoload
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fb-sdk/autoload.php';


        /**
         * TWIG autoload for PHP-Template-Engine
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Twig/autoload.php';

        /**
         * Update-Checker-Autoload
         * Git Update for Theme|Plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/update-checker/autoload.php';

        /**
         * Plugin WP-Rest API
         *
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/endpoint/wp_facebook_importer_rest_endpoint.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-facebook-importer-admin.php';


        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fb-api-importer/api-cronjob-exec.php';
        require plugin_dir_path(dirname(__FILE__)) . 'includes/fb-api-importer/class_wp_importer_open_socket.php';


        /**
         * Import Custom Post_Types (POSTS)
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fb-api-importer/class_import_wp_custom_post.php';


        /**
         * Import Custom Post_Types (Events)
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/fb-api-importer/class_import_wp_custom_events.php';


        //JOB SRV API Endpoint
        /**
         * SRV WP-Remote Exec
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/srv-api/config/class_make_remote_exec.php';


        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-facebook-importer-public.php';

        $this->loader = new Wp_Facebook_Importer_Loader();

    }

    /**
     * Check PHP and WordPress Version
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function check_dependencies(): void
    {
        global $wp_version;
        if (version_compare(PHP_VERSION, WP_FACEBOOK_IMPORTER_PHP_VERSION, '<') || $wp_version < WP_FACEBOOK_IMPORTER_WP_VERSION) {
            $this->maybe_self_deactivate();
        }
    }

    /**
     * Self-Deactivate
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function maybe_self_deactivate(): void
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins($this->plugin_slug);
        add_action('admin_notices', array($this, 'self_deactivate_notice'));
    }

    /**
     * Self-Deactivate Admin Notiz
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function self_deactivate_notice(): void
    {
        echo sprintf('<div class="notice notice-error is-dismissible" style="margin-top:5rem"><p>' . __('This plugin has been disabled because it requires a PHP version greater than %s and a WordPress version greater than %s. Your PHP version can be updated by your hosting provider.', 'hupa-teams') . '</p></div>', WP_FACEBOOK_IMPORTER_PHP_VERSION, WP_FACEBOOK_IMPORTER_WP_VERSION);
        exit();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Wp_Facebook_Importer_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Wp_Facebook_Importer_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_plugin_helper_class()
    {

        global $pluginHelper;
        $pluginHelper = new WP_Facebook_Importer_Helper($this->get_plugin_name(), $this->get_version(), $this->main);

        $this->loader->add_filter($this->plugin_name . '/get_random_string', $pluginHelper, 'getRandomString');
        $this->loader->add_filter($this->plugin_name . '/generate_random_id', $pluginHelper, 'getGenerateRandomId', 10, 4);
        $this->loader->add_filter($this->plugin_name . '/array_to_object', $pluginHelper, 'ArrayToObject');
        $this->loader->add_filter($this->plugin_name . '/object_to_array', $pluginHelper, 'ObjectToArray');
        $this->loader->add_filter($this->plugin_name . '/svg_icon', $pluginHelper, 'svg_icons', 10, 3);
        $this->loader->add_filter($this->plugin_name . '/order_by_args_string', $pluginHelper, 'order_by_args_string', 10, 3);
        $this->loader->add_filter($this->plugin_name . '/order_by_args', $pluginHelper, 'order_by_args', 10, 3);
        $this->loader->add_filter($this->plugin_name . '/svg_icons', $pluginHelper, 'svg_icons', 10, 3);
        $this->loader->add_filter($this->plugin_name . '/cleanWhitespace', $pluginHelper, 'cleanWhitespace');
        $this->loader->add_filter($this->plugin_name . '/get_next_cron_time', $pluginHelper, 'import_get_next_cron_time');
        $this->loader->add_action($this->plugin_name . '/set_api_log', $pluginHelper, 'wwdh_set_api_log',10,2);

    }

    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @throws FacebookSDKException
     * @since    1.0.0
     * @access   private
     */
    private function register_fb_api_importer_class()
    {
        global $fbApi;
        $fbApi = new Facebook_Import_Api($this->get_plugin_name(), $this->get_version(), $this->main);
        $this->loader->add_filter($this->plugin_name . '/check_fb_access_token', $fbApi, 'check_fb_access_token');
        $this->loader->add_filter($this->plugin_name . '/get_api_facebook_posts', $fbApi, 'get_api_facebook_posts');
        $this->loader->add_filter($this->plugin_name . '/get_api_facebook_events', $fbApi, 'get_api_facebook_events');
    }

    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_importer_cronjob_class()
    {
        if ($this->check_wp_cron()) {
            $fbApiCron = new Import_Api_Cronjob($this->get_plugin_name(), $this->get_version(), $this->main);

            $this->loader->add_filter($this->plugin_name . '/plugin_run_schedule_task', $fbApiCron, 'plugin_run_schedule_task');
            $this->loader->add_filter($this->plugin_name . '/plugin_wp_un_schedule_event', $fbApiCron, 'plugin_wp_un_schedule_event');
            $this->loader->add_filter($this->plugin_name . '/plugin_wp_delete_event', $fbApiCron, 'plugin_wp_delete_event');
        }
    }

    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_importer_cronjob_exec_class()
    {
        global $importerCronExec;
        $importerCronExec = FB_Importer_Api_Cronjob_Exec::instance($this->plugin_name, $this->main);
        $this->loader->add_action('fb_api_plugin_sync', $importerCronExec, 'fb_importer_plugin_synchronisation',0);
    }

    /**
     * Register all the hooks related to the Gutenberg Plugins functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_post_selector_database_handle()
    {

        global $FbImporterDatabase;
        $FbImporterDatabase = new WP_Facebook_Importer_Database($this->plugin_name, $this->get_db_version(), $this->main);

        $this->loader->add_action('init', $FbImporterDatabase, 'facebook_importer_check_jal_install');
        $this->loader->add_filter($this->plugin_name . '/get_plugin_settings', $FbImporterDatabase, 'get_api_settings');
        $this->loader->add_filter($this->plugin_name . '/update_plugin_settings', $FbImporterDatabase, 'plugin_update_settings');
        $this->loader->add_filter($this->plugin_name . '/get_custom_terms', $FbImporterDatabase, 'get_custom_terms');
        $this->loader->add_filter($this->plugin_name . '/get_term_by_term_id', $FbImporterDatabase, 'get_term_by_term_id');

        $this->loader->add_filter($this->plugin_name . '/get_facebook_imports', $FbImporterDatabase, 'get_facebook_imports', 10, 2);
        $this->loader->add_filter($this->plugin_name . '/set_facebook_imports', $FbImporterDatabase, 'set_facebook_imports');
        $this->loader->add_filter($this->plugin_name . '/update_facebook_import', $FbImporterDatabase, 'update_facebook_import');
        $this->loader->add_filter($this->plugin_name . '/update_imports_inputs', $FbImporterDatabase, 'update_imports_inputs');
        $this->loader->add_filter($this->plugin_name . '/delete_imports_input', $FbImporterDatabase, 'delete_imports_input');

        $this->loader->add_filter($this->plugin_name . '/delete_facebook_posts', $FbImporterDatabase, 'delete_facebook_posts', 10, 2);
        $this->loader->add_filter($this->plugin_name . '/update_last_sync', $FbImporterDatabase, 'update_last_sync', 10, 2);

        $this->loader->add_filter($this->plugin_name . '/update_last_sync_import', $FbImporterDatabase, 'update_last_sync_import');
        //EVENTS POSTS
        $this->loader->add_filter($this->plugin_name . '/delete_facebook_events', $FbImporterDatabase, 'delete_facebook_events');
        $this->loader->add_filter($this->plugin_name . '/delete_post_by_fb_id', $FbImporterDatabase, 'delete_post_by_fb_id', 10, 2);
        $this->loader->add_filter($this->plugin_name . '/get_wp_facebook_posts', $FbImporterDatabase, 'get_wp_facebook_posts');

        $this->loader->add_filter($this->plugin_name . '/get_import_daten', $FbImporterDatabase, 'get_import_daten');
        $this->loader->add_filter($this->plugin_name . '/check_double_post', $FbImporterDatabase, 'check_double_post');

        $this->loader->add_filter($this->plugin_name . '/check_db_events_by_args', $FbImporterDatabase, 'check_db_events_by_args', 10, 2);
        $this->loader->add_filter($this->plugin_name . '/delete_old_wp_facebook_posts', $FbImporterDatabase, 'delete_old_wp_facebook_posts', 10, 2);
        $this->loader->add_filter($this->plugin_name . '/get_wp_facebook_import_count', $FbImporterDatabase, 'get_wp_facebook_import_count', 10, 3);

        //LOG
        $this->loader->add_filter($this->plugin_name . '/set_plugin_syn_log', $FbImporterDatabase, 'set_plugin_syn_log');
        $this->loader->add_filter($this->plugin_name . '/delete_plugin_syn_log', $FbImporterDatabase, 'delete_plugin_syn_log');
        $this->loader->add_filter($this->plugin_name . '/get_plugin_syn_log', $FbImporterDatabase, 'get_plugin_syn_log');

    }

    /**
     * Register all the hooks related to the Gutenberg Plugins functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_fb_importer_rest_endpoint()
    {
        global $register_experience_public_endpoint;
        $register_experience_public_endpoint = new Facebook_Importer_Rest_Endpoint($this->plugin_name, $this->main, $this->cronjob_id);
        $this->loader->add_action('rest_api_init', $register_experience_public_endpoint, 'register_routes');

    }

    /**
     * Check PHP and WordPress Version
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_plugin_settings(): void
    {
        $FbImporterDatabase = new WP_Facebook_Importer_Database($this->plugin_name, $this->db_version, $this->main);
        $args = sprintf('WHERE id="%s"', $this->settings_id);
        $settings = $FbImporterDatabase->get_api_settings($args);
        $this->settings = (object)[];
        if ($settings->status) {
            $this->settings = $settings->record;
        }
    }

    /**
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_import_custom_post(): void
    {
        $WpCustomPosts = new Import_WP_Custom_Post($this->plugin_name, $this->db_version, $this->main);
        $this->loader->add_filter($this->plugin_name . '/sync_facebook_posts', $WpCustomPosts, 'sync_facebook_posts');
    }

    /**
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_import_custom_events(): void
    {
        $WpCustomEvents = new Import_WP_Custom_Events($this->plugin_name, $this->db_version, $this->main);
        $this->loader->add_filter($this->plugin_name . '/sync_facebook_events', $WpCustomEvents, 'sync_facebook_events');
    }

    /**
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_make_cronjob(): void
    {
        global $WpMakeCronjob;
        $WpMakeCronjob = Import_Curl_Cronjob_Exec::instance($this->plugin_name, $this->main);
    }


    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        if (!get_option('fb_importer_user_role')) {
            update_option('fb_importer_user_role', 'manage_options');
        }

        if(!get_option('fb_cronjob_settings')){
            $settings = [
                'min_sleep_post' => 180,
                'min_sleep_event' => 60,
                'min_event_count' => 5
            ];

            update_option('fb_cronjob_settings', $settings);
        }


        $plugin_admin = new Wp_Facebook_Importer_Admin($this->get_plugin_name(), $this->get_version(), $this->main);


        //Register Custom Post-Type and Taxonomie
        $postTypes = new Wp_Facebook_Importer_Activator();
        $this->loader->add_action('init', $postTypes, 'add_admin_capabilities');
        $this->loader->add_action('init', $postTypes, 'register_facebook_importer_post_type');
        $this->loader->add_action('init', $postTypes, 'register_facebook_importer_taxonomies');

        //Admin Menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'wp_facebook_importer_menu');
        //Admin Ajax
        $this->loader->add_action('wp_ajax_FBImporterHandle', $plugin_admin, 'prefix_ajax_FBImporterHandle');
        //Plugin Settings Link
        $this->loader->add_filter('plugin_action_links_' . $this->plugin_name . '/' . $this->plugin_name . '.php', $plugin_admin, 'wp_facebook_importer_plugin_add_action_link');
        //Externer Cronjob Trigger
        $this->loader->add_action('init', $plugin_admin, 'cronjob_extern_trigger_check');
        $this->loader->add_action('template_redirect', $plugin_admin, 'importer_cronjob_callback_trigger');

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');

        //JOB UPDATE CHECKER
        $this->loader->add_action('init', $plugin_admin, 'set_fb_importer_update_checker');
        $this->loader->add_action('in_plugin_update_message-'.WP_FACEBOOK_IMPORTER_SLUG_PATH.'/'.WP_FACEBOOK_IMPORTER_SLUG_PATH.'.php', $plugin_admin, 'fb_importer_show_upgrade_notification',10,2);

    }

    /**
     * Register all the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Wp_Facebook_Importer_Public($this->get_plugin_name(), $this->get_version(), $this->main);

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }


    /**
     * Register API SRV Rest-Api Endpoints
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_srv_rest_api_routes()
    {
        $srv_rest_api = new Srv_Api_Endpoint($this->get_plugin_name(), $this->get_version(), $this->main);
        $this->loader->add_action('rest_api_init', $srv_rest_api, 'register_routes');
    }

    /**
     * Register API SRV Rest-Api Endpoints
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_remote_exec()
    {
        global $wpRemoteExec;
        $wpRemoteExec = Make_Remote_Exec::instance($this->plugin_name, $this->get_version(), $this->main);
    }

    /**
     * Run the loader to execute all the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name(): string
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Wp_Facebook_Importer_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader(): Wp_Facebook_Importer_Loader
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version(): string
    {
        return $this->version;
    }

    /**
     * Retrieve the database version number of the plugin.
     *
     * @return    string    The database version number of the plugin.
     * @since     1.0.0
     */
    public function get_db_version(): string
    {
        return $this->db_version;
    }

    /**
     * @return Environment
     */
    public function get_twig(): Environment
    {
        return $this->twig;
    }

    /**
     * @return Facebook|false
     * @throws FacebookSDKException
     */
    public function get_fb_sdk()
    {
        if (!$this->settings) {
            return false;
        }
        if (!$this->settings->app_id || !$this->settings->app_secret) {
            return false;
        }
        $this->fbApi = new Facebook([
            'app_id' => $this->settings->app_id,
            'app_secret' => $this->settings->app_secret,
            'default_graph_version' => 'v11.0'
        ]);
        return $this->fbApi;
    }

    /**
     * @return string
     */
    public function get_settings_id(): string
    {
        return $this->settings_id;
    }

    public function get_settings(): object
    {
        return $this->settings;
    }

    public function get_cronjob_id(): string
    {
        return strtoupper(sha1($this->cronjob_id));
    }

    public function get_cronjob_exec_id(): string
    {
        return strtoupper(sha1($this->cronjob_exec_id));
    }

    public function get_cronjob_slug(): string
    {
        return $this->cronjob_slug;
    }

    /**
     * @return bool
     */
    private function check_wp_cron(): bool
    {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * The API DIR
     *
     *
     * @return    string    API DIR of the plugin.
     * @since     1.0.0
     */
    public function get_api_dir(): string
    {
        return $this->api_dir;
    }

    /**
     * The Public Certificate
     *
     * @return    string    Public Certificate in BASE64.
     * @since     1.0.0
     */
    public function get_id_rsa(): string
    {
        return $this->id_rsa;
    }

    public function get_plugin_api_config():object {
        return $this->plugin_api_config;
    }

}
