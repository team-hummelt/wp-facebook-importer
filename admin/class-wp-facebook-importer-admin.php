<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/admin
 */

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use WPFacebook\Importer\Import_Curl_Cronjob_Exec;
use WPFacebook\Importer\WP_Facebook_Importer_Ajax;
use WPFacebook\Importer\WP_Facebook_Importer_Defaults;
use WPFacebook\Importer\Wp_Importer_Open_Socket;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/admin
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Facebook_Importer_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private string $basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private string $version;

    /**
     * The trait for the default settings
     * of the plugin.
     */
    use WP_Facebook_Importer_Defaults;

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
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct(string $plugin_name, string $version, Wp_Facebook_Importer $main)
    {

        $this->basename = $plugin_name;
        $this->version = $version;
        $this->main = $main;

    }

    public function wp_facebook_importer_menu()
    {
        add_menu_page(
            __('FB-Importer', 'wp-facebook-importer'),
            __('FB-Importer', 'wp-facebook-importer'),
            get_option('fb_importer_user_role'),
            'wp-facebook-importer-settings',
            '',
           'dashicons-facebook',3
           // apply_filters($this->basename . '/svg_icons', 'facebook', true, true), 3
        );

        $hook_suffix = add_submenu_page(
            'wp-facebook-importer-settings',
            __('Settings', 'wp-facebook-importer'),
            __('Settings', 'wp-facebook-importer'),
            get_option('fb_importer_user_role'),
            'wp-facebook-importer-settings',
            array($this, 'admin_wp_facebook_importer_settings_page'));

        add_action('load-' . $hook_suffix, array($this, 'wp_facebook_importer_load_ajax_admin_options_script'));

        $hook_suffix = add_submenu_page(
            'wp-facebook-importer-settings',
            __('FB pages', 'wp-facebook-importer'),
            __('FB pages', 'wp-facebook-importer'),
            get_option('fb_importer_user_role'),
            'fb-importer-sites',
            array($this, 'admin_wp_facebook_importer_sites'));

        add_action('load-' . $hook_suffix, array($this, 'wp_facebook_importer_load_ajax_admin_options_script'));

        //Options Page
        $hook_suffix = add_options_page(
            __('FB-Importer', 'wp-facebook-importer'),
            __('FB-Importer', 'wp-facebook-importer'),
            get_option('fb_importer_user_role'),
            'wp-facebook-importer-options',
            array($this, 'wp_facebook_importer_options_page')
        );

        add_action('load-' . $hook_suffix, array($this, 'wp_facebook_importer_load_ajax_admin_options_script'));
    }

    /**
     * ============================================
     * =========== PLUGIN SETTINGS LINK ===========
     * ============================================
     */
    public static function wp_facebook_importer_plugin_add_action_link($data): array
    {
        // check permission
        if (!current_user_can(get_option('fb_importer_user_role'))) {
            return $data;
        }
        return array_merge(
            $data,
            array(
                sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        array(
                            'page' => 'wp-facebook-importer-options'
                        ),
                        admin_url('/options-general.php')
                    ),
                    __("Settings", "wp-facebook-importer")
                )
            )
        );
    }

    public function admin_wp_facebook_importer_settings_page(): void
    {

        $nextTime = apply_filters($this->basename . '/get_next_cron_time', 'fb_api_plugin_sync');
        $nextCronDate = '';
        $nextCronTime = '';
        if ($nextTime) {
            $time = current_time('timestamp') + $nextTime;
            $nextCronJob = date('d.m.Y H:i:s', $time);
            $nextCronJob = explode(' ', $nextCronJob);
            $nextCronDate = $nextCronJob[0];
            $nextCronTime = $nextCronJob[1];
        }

        $siteLang = $this->get_plugin_defaults('language_formulare');
        $modalLang = $this->get_plugin_defaults('language_modal');
        $select_sync_interval = $this->get_plugin_defaults('select_api_sync_interval');
        $select_max_post_sync = $this->get_plugin_defaults('max_post_sync');
        $lang = wp_parse_args($siteLang, $modalLang);
        $var = [
            'external_url' => site_url().'/?'.$this->main->get_cronjob_slug().'='.$this->main->get_cronjob_id(),
            'next_cron_date' => $nextCronDate,
            'next_cron_time' => $nextCronTime
        ];

        $settings = apply_filters($this->basename . '/get_plugin_settings', false);
        $twigData = [
            'db' => $this->main->get_db_version(),
            'version' => $this->version,
            'l' => $lang,
            's' => $settings->record,
            'select_sync_interval' => $select_sync_interval,
            'select_max_post_sync' => $select_max_post_sync,
            'var' => $var
        ];
        try {
            echo $this->main->get_twig()->render('@page/settings.twig', $twigData);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function admin_wp_facebook_importer_sites(): void
    {
        $limit = 5;
        $selectFrom = [];
        for ($i = 1; $i <= $limit; $i++) {
            $count = ($i * 12) + date('n', current_time('timestamp')) - 1;
            $from = strtotime(current_time('mysql') . '-' . $count . ' month');
            $selectFromItem = [
                'count' => $count,
                'year' => date('Y', $from)
            ];
            $selectFrom[] = $selectFromItem;
        }
        $now = [
            '0' => [
                'count' => date('n', current_time('timestamp')),
                'year' => date('Y', current_time('timestamp'))
            ]
        ];
        $selectFrom = array_merge_recursive($now, $selectFrom);
        $siteLang = $this->get_plugin_defaults('language_formulare');
        $modalLang = $this->get_plugin_defaults('language_modal');
        $tableLang = $this->get_plugin_defaults('language_table');
        $select_sync_interval = $this->get_plugin_defaults('select_api_sync_interval');
        $select_max_post_sync = $this->get_plugin_defaults('max_post_sync');
        $lang = wp_parse_args($siteLang, $modalLang);
        $var = [
            'external_url' => rest_url('fb-importer/v2/cron/' . $this->main->get_cronjob_id()),
        ];

        $settings = apply_filters($this->basename . '/get_plugin_settings', false);
        $termsSelect = apply_filters($this->basename . '/get_custom_terms', 'wp_facebook_category');
        $termsSelect->status ? $kategorie = $termsSelect->terms : $kategorie = false;


        $twigData = [
            'db' => $this->main->get_db_version(),
            'version' => $this->version,
            'cat_select' => $kategorie,
            'l' => $lang,
            'tl' => $tableLang,
            's' => $settings->record,
            'select_sync_interval' => $select_sync_interval,
            'select_max_post_sync' => $select_max_post_sync,
            'select_max_year' => $selectFrom,
            'var' => $var,
        ];

        try {
            echo $this->main->get_twig()->render('@page/fb-seiten.twig', $twigData);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    //Options Page
    public function wp_facebook_importer_options_page(): void
    {
        $siteLang = $this->get_plugin_defaults('language_formulare');
        $selectRole = $this->get_plugin_defaults('select_user_role');
        $twigData = [
            'db' => $this->main->get_db_version(),
            'version' => $this->version,
            'l' => $siteLang,
            'select_role' => $selectRole,
            'set_role' => get_option('fb_importer_user_role'),
            'data' => get_option('fb_cronjob_settings')
        ];
        try {
            echo $this->main->get_twig()->render('@page/plugin-optionen.twig', $twigData);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function wp_facebook_importer_load_ajax_admin_options_script(): void
    {

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        $title_nonce = wp_create_nonce('facebook_import_admin_handle');

        wp_register_script('wp-facebook-importer-admin-ajax-script', '', [], '', true);
        wp_enqueue_script('wp-facebook-importer-admin-ajax-script');
        wp_localize_script('wp-facebook-importer-admin-ajax-script', 'fb_import_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $title_nonce,
            'data_table' => plugin_dir_url(__FILE__) . 'json/DataTablesGerman.json',
            'rest_url' => get_rest_url('fb-importer/v2/'),
            'fb_import_url' => WP_FACEBOOK_IMPORTER_PLUGIN_URL,
            'alert_msg' => $this->get_plugin_defaults('language_modal')
        ));
    }

    /**
     * Register Experience Reports AJAX ADMIN RESPONSE HANDLE
     *
     * @since    1.0.0
     */
    public function prefix_ajax_FBImporterHandle(): void
    {
        check_ajax_referer('facebook_import_admin_handle');
        require_once 'ajax/class_wp_facebook_importer_ajax.php';
        $adminAjaxHandle = new WP_Facebook_Importer_Ajax($this->basename, $this->version, $this->main);
        wp_send_json($adminAjaxHandle->wp_facebook_importer_admin_ajax_handle());
    }

    public function cronjob_extern_trigger_check(): void
    {
        global $wp;
        $wp->add_query_var($this->main->get_cronjob_slug());
        $wp->add_query_var('cronjob');
    }

    public function importer_cronjob_callback_trigger(): void
    {
        if (get_query_var($this->main->get_cronjob_slug()) === $this->main->get_cronjob_id()) {
            Import_Curl_Cronjob_Exec::instance($this->basename, $this->main);
            exit();
        }
        if (get_query_var('cronjob') === $this->main->get_cronjob_exec_id()) {
            Wp_Importer_Open_Socket::instance($this->basename, $this->main);
            exit();
        }
    }


    /**
     * Register the Update-Checker for the Plugin.
     *
     * @since    1.0.0
     */
    public function set_fb_importer_update_checker()
    {

        if (get_option("{$this->basename}_update_config") && get_option($this->basename . '_update_config')->update->update_aktiv) {
            $postSelectorUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
                get_option("{$this->basename}_update_config")->update->update_url_git,
                WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename . DIRECTORY_SEPARATOR . $this->basename . '.php',
                $this->basename
            );

            switch (get_option("{$this->basename}_update_config")->update->update_type) {
                case '1':
                    $postSelectorUpdateChecker->getVcsApi()->enableReleaseAssets();
                    break;
                case '2':
                    $postSelectorUpdateChecker->setBranch(get_option("{$this->basename}_update_config")->update->branch_name);
                    break;
            }
        }
    }

    /**
     * add plugin upgrade notification
     */

    public function fb_importer_show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata ) {

        if ( isset( $new_plugin_metadata->upgrade_notice ) && strlen( trim( $new_plugin_metadata->upgrade_notice ) ) > 0 ) {
            // Display "upgrade_notice".
            echo sprintf( '<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr('Important Upgrade Notice', 'google-rezensionen-api'), esc_html( rtrim( $new_plugin_metadata->upgrade_notice ) ) );

        }
    }

    /**
     * @param $string
     * @return string
     */
    private function cleanWhitespace($string): string
    {
        return trim(preg_replace('/\s+/', '', $string));
    }


    public function enqueue_scripts()
    {

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


        wp_enqueue_style($this->basename . '-bootstrap-icons', plugin_dir_url(__DIR__) . 'includes/tools/bootstrap/bootstrap-icons.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-sweetalert2', plugin_dir_url(__DIR__) . 'includes/tools/sweetalert2/sweetalert2.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-animate', plugin_dir_url(__DIR__) . 'includes/tools/animate.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-bootstrap', plugin_dir_url(__DIR__) . 'includes/tools/bootstrap/bootstrap.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-dashboard', plugin_dir_url(__FILE__) . 'css/admin-dashboard.css', array(), $this->version, 'all');

        wp_enqueue_script($this->basename . '-bootstrap-bundle', plugin_dir_url(__DIR__) . 'includes/tools/bootstrap/bootstrap.bundle.min.js', array(), $this->version, true);
        wp_enqueue_script($this->basename . '-sweetalert2', plugin_dir_url(__DIR__) . 'includes/tools/sweetalert2/sweetalert2.all.min.js', array(), $this->version, true);


        if (get_current_screen()->id == 'fb-importer_page_fb-importer-sites') {
            wp_enqueue_style($this->basename . '-data-tables-bs5', plugin_dir_url(__DIR__) . 'includes/tools/data-tables/dataTables.bootstrap5.min.css', array(), $this->version, 'all');
            wp_enqueue_script($this->basename . '-jQuery-data-tables', plugin_dir_url(__DIR__) . 'includes/tools/data-tables/jquery.dataTables.min.js', array(), $this->version, true);
            wp_enqueue_script($this->basename . '-data-tables', plugin_dir_url(__DIR__) . 'includes/tools/data-tables/dataTables.bootstrap5.min.js', array(), $this->version, true);
            wp_enqueue_script($this->basename . '-data-table-imports', plugin_dir_url(__DIR__) . 'admin/js/data-table-imports.js', array('jquery'), $this->version, true);
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script($this->basename, plugin_dir_url(__FILE__) . 'js/wp-facebook-importer-admin.js', array('jquery'), $this->version, true);

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

        wp_enqueue_style( $this->basename.'-static', plugin_dir_url( __FILE__ ) . 'css/wp-facebook-importer-admin.css', array(), $this->version, 'all' );

    }

}
