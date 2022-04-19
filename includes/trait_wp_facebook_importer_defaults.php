<?php
namespace WPFacebook\Importer;
/**
 * Default Plugin Settings
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
defined('ABSPATH') or die();

trait WP_Facebook_Importer_Defaults {

    // DB-Tables
    protected  string $table_api_settings = 'fb_api_settings';
    protected  string $table_api_imports = 'fb_api_imports';
    protected  string $table_api_sync_log = 'fb_api_sync_log';
    //SETTINGS DEFAULT OBJECT
    protected array $plugin_default_values;
    //API Options
    protected string $api_url = 'https://start.hu-ku.com/theme-update/api/v2/';
    protected string $public_api_token_uri = 'public/token';
    protected string $public_api_support_uri = 'public';
    protected string $public_api_public_resource_uri = 'public/resource';
    protected string $public_api_public_preview_uri = 'public/preview';
    protected string $kunden_login_url = 'https://start.hu-ku.com/theme-update/kunden-web';
    // Get activate Token
    protected string $extension_api_activate_uri = 'jwt/extension/license/activate/';
    //Resource Token
    protected string $extension_api_id_rsa_token = 'jwt/extension/license/token/';
    // License Resource URI
    protected string $extension_api_resource_uri = 'jwt/extension/license/resource';
    protected string $extension_api_extension_download = 'jwt/extension/download';

    protected function get_plugin_defaults(string $args=''):array {

        $this->plugin_default_values = [
            'api_settings' => [
                'api_url' => $this->api_url,
                'public_api_token_url' => $this->api_url . $this->public_api_token_uri,
                'public_api_support_url' => $this->api_url . $this->public_api_support_uri,
                'public_api_resource_url' => $this->api_url . $this->public_api_public_resource_uri,
                'public_api_preview_url' => $this->api_url . $this->public_api_public_preview_uri,
                //Kunden Login
                'kunden_login_url' => $this->kunden_login_url,
                'extension_api_activate_url' => $this->api_url . $this->extension_api_activate_uri,
                // ID_RSA Resource Token
                'extension_api_id_rsa_token' => $this->api_url . $this->extension_api_id_rsa_token,
                //Resource
                'extension_api_resource_url' => $this->api_url . $this->extension_api_resource_uri,
                //Download
                'extension_api_extension_download' => $this->api_url . $this->extension_api_extension_download,
            ],
            'max_post_sync' => [
                "0" => [
                    "id" => 1,
                    'value' => 10
                ],
                "1" => [
                    "id" => 2,
                    'value' => 20
                ],
                "2" => [
                    "id" => 3,
                    'value' => 30
                ],
                "3" => [
                    "id" => 4,
                    'value' => 40
                ],
                "4" => [
                    "id" => 4,
                    'value' => 40
                ],
                "5" => [
                    "id" => 5,
                    'value' => 50
                ],
            ],

            'select_api_sync_interval' => [
                "0" => [
                    "id" => 'hourly',
                    "bezeichnung" => __('hourly', 'wp-facebook-importer'),
                ],
                "1" => [
                    'id' => 'twicedaily',
                    "bezeichnung" =>__('Twice a day', 'wp-facebook-importer'),
                ],
                "3" => [
                    'id' => 'daily',
                    "bezeichnung" =>__('Once a day', 'wp-facebook-importer'),
                ],
                "4" => [
                    'id' => 'weekly',
                    "bezeichnung" =>__('Once a week', 'wp-facebook-importer'),
                ],
            ],

            'select_user_role' => [
                "0" => [
                    'value' => 'read',
                    'name' => __('Subscriber','wp-facebook-importer')
                ],
                "1" => [
                    'value' => 'edit_posts',
                    'name' => __('Contributor','wp-facebook-importer')
                ],
                "2" => [
                    'value' => 'publish_posts',
                    'name' => __('Author','wp-facebook-importer')
                ],
                "3" => [
                    'value' => 'publish_pages',
                    'name' => __('Editor','wp-facebook-importer')
                ],
                "4" => [
                    'value' => 'manage_options',
                    'name' => __('Administrator','wp-facebook-importer')
                ],
            ],

            'language_formulare' => [
                'import_name' => __('Name or location for this import:', 'wp-facebook-importer'),
                'import_name_help' => __('This name is displayed on the website..', 'wp-facebook-importer'),
                'import_description' => __('Description for this import:', 'wp-facebook-importer'),
                'import_description_help' => __('The description is optional.', 'wp-facebook-importer'),
                'max_number_events' => __('Max. Number of posts and events Import:', 'wp-facebook-importer'),
                'max_number_events_help' => __('The standard value is 100.', 'wp-facebook-importer'),
                'header_api_options' => __('Facebook Api and WordPress Options:', 'wp-facebook-importer'),
                'page_id_id_help' => __('If you do not enter a Page ID or User ID, your Facebook User ID will be used.', 'wp-facebook-importer'),
                'fb_user_id' => __('Facebook User-ID:', 'wp-facebook-importer'),
                'fb_user_help' => __('Only posts and events of the user are imported.', 'wp-facebook-importer'),
                'user_id_aktiv' => __('User ID active', 'wp-facebook-importer'),
                'header_select_cat' => __('Select category for Facebook posts and events:', 'wp-facebook-importer'),
                'header_select_cat_sm' => __('If you do not select a category, the default categories for posts or events will be used.', 'wp-facebook-importer'),
                'select' => __('select', 'wp-facebook-importer'),
                'category_select' => __('Category for posts:', 'wp-facebook-importer'),
                'event_select' => __('Category for events:', 'wp-facebook-importer'),
                'header_new_cat' => __('Create a new category for Facebook posts or events:', 'wp-facebook-importer'),
                'btn_new_cat' => __('Create new category', 'wp-facebook-importer'),
                'btn_create_import' => __('Create a new Facebook import', 'wp-facebook-importer'),
                'btn_update_import' => __('Save changes', 'wp-facebook-importer'),
                'btn_back' => __('back to the overview', 'wp-facebook-importer'),
                'btn_sync' => __('Synchronise now', 'wp-facebook-importer'),
                'btn_sync_posts' => __('Synchronize posts', 'wp-facebook-importer'),
                'btn_sync_events' => __('Synchronize events', 'wp-facebook-importer'),

                'btn_del_import' => __('Delete import', 'wp-facebook-importer'),
                'btn_reset_import' => __('Reset Import', 'wp-facebook-importer'),
                'btn_del_posts' => __('Delete all posts', 'wp-facebook-importer'),
                'FB_API_credentials' => __('FB API credentials','wp-facebook-importer'),
                'App_ID' => __('App ID', 'wp-facebook-importer'),
                'APP_Secret' => __('APP Secret', 'wp-facebook-importer'),
                'Access_Token' => __('Access-Token', 'wp-facebook-importer'),
                'gespeichert' => __('saved', 'wp-facebook-importer'),
                'Token_anzeigen' => __('Show tokens', 'wp-facebook-importer'),
                'Token_ausblenden' => __('Hide token', 'wp-facebook-importer'),
                'Check_Access_Token' => __('Check Access Token', 'wp-facebook-importer'),
                'Synchronisation_Einstellungen' => __('Synchronization settings','wp-facebook-importer'),
                'Cronjob_aktiv' => __('Cronjob active','wp-facebook-importer'),
                'Synchronisierungsintervall' => __('Synchronization interval','wp-facebook-importer'),
                'Update_Intervall_for_die_Synchronisierung' => __('Update interval for synchronisation','wp-facebook-importer'),
                'Beitrage_pro_Update_Importieren' => __('Import posts per update','wp-facebook-importer'),
                'URL_for_external_cronjob' => __('URL for external cronjob', 'wp-facebook-importer'),
                'Close' => __('Close', 'wp-facebook-importer'),
                'Response' => __('Response','wp-facebook-importer'),
                'Facebook_Import_Posts'=> __('Facebook Import Posts','wp-facebook-importer'),
                'FB_API_Zugangsdaten' => __('FB API access data','wp-facebook-importer'),
                'header_options' => __('Facebook Importer and WordPress Options', 'wp-facebook-importer'),
                'Einstellungen' => __('Settings', 'wp-facebook-importer'),
                'label_role_header'=> __('Minimum requirement for using this function','wp-facebook-importer'),
                'User_role' => __('User role','wp-facebook-importer'),
                //Seiten Page
                'site_page_headline' => __('Import and manage Facebook content','wp-facebook-importer'),
                'site_page_subline' => __('Facebook content and events','wp-facebook-importer'),
                'all_imported_content' => __('all imported content', 'wp-facebook-importer'),
                'Import_new_content' => __('Import new content','wp-facebook-importer'),
                'Name_oder_Location_for_diesen_Import' => __('Name or location for this import','wp-facebook-importer'),
                'Dieser_Name_wird_auf_der_Website_angezeigt' => __('This name is displayed on the website','wp-facebook-importer' ),
                'Description_for_this_import' => __('Description for this import','wp-facebook-importer'),
                'The_description_is_optional' => __('The description is optional', 'wp-facebook-importer'),
                'Max_Number_of_posts_and_events_Import' => __('Max. Number of posts and events Import', 'wp-facebook-importer'),
                'The_standard_value_is' => __('The standard value is', 'wp-facebook-importer'),
                'Facebook_Api_and_WordPress_Options' => __('Facebook Api and WordPress Options','wp-facebook-importer'),
                'page_id_help' => __('If you do not enter a Page ID or User ID, your Facebook User ID will be used.', 'wp-facebook-importer'),
                'Facebook_Page_ID' => __('Facebook Page ID', 'wp-facebook-importer'),
                'Facebook_User_ID' => __('Facebook User-ID', 'wp-facebook-importer'),
                'user_id_help' => __('Only posts and events of the user are imported.', 'wp-facebook-importer'),
                'User_ID_active' => __('User ID active', 'wp-facebook-importer'),
                'select_kategorie_label' => __('Select category for Facebook posts and events','wp-facebook-importer'),
                'select_kategorie_help' => __('If you do not select a category, the default categories for posts or events will be used.', 'wp-facebook-importer'),
                'Category_for_posts' => __('Category for posts','wp-facebook-importer'),
                'Category_for_events' => __('Category for events', 'wp-facebook-importer'),

                'add_category_headline' => __('Create a new category for Facebook posts or events','wp-facebook-importer'),
                'Create_new_category' => __('Create new category', 'wp-facebook-importer'),
                'Create_a_new_Facebook_import' => __('Create a new Facebook import', 'wp-facebook-importer'),
                'aktiv' => __('active', 'wp-facebook-importer'),
                'next_synchronisierung_am' => __('next synchronization on', 'wp-facebook-importer'),
                'am' => __('on', 'wp-facebook-importer'),
                'um' => __('at', 'wp-facebook-importer'),
                'Uhr' => __('clock', 'wp-facebook-importer'),
                'vom' => __('from', 'wp-facebook-importer'),
                'unbekannt' => __('unknown', 'wp-facebook-importer'),
                'Facebook_API_Status' => __('Facebook API Status', 'wp-facebook-importer'),
                'max_imports_label' => __('If the entry remains empty, the number is unlimited.', 'wp-facebook-importer'),
                'Beginn_der_Synchronisierung'=> __('Start synchronization','wp-facebook-importer'),
                'letzter_Beitrag'=> __('last post','wp-facebook-importer'),
                'letzte_Aktualisierung'=> __('last update','wp-facebook-importer'),
                'check_import_ohne_image'=> __('Ignore posts without picture','wp-facebook-importer'),
                'sync_wait_head'=> __('Posts and events','wp-facebook-importer'),
                'sync_wait_second'=> __('are synchronized','wp-facebook-importer'),
                'Posts'=> __('Posts','wp-facebook-importer'),
                'Events'=> __('Events','wp-facebook-importer'),
                'Imported' => __('Imported','wp-facebook-importer'),
                'formular_headline_update'=> __('Settings for import','wp-facebook-importer'),
                'formular_headline_new'=> __('Create new import','wp-facebook-importer'),
                'btn_cronjob_log' => __('Display cronjob log', 'wp-facebook-importer'),
                'next_update' => __('next update', 'wp-facebook-importer'),
                //ToolTip
                'tooltip_reset_sync' => __('The date of the last synchronization is reset.','wp-facebook-importer')

            ],
            'language_table' => [
                'Designation' => __('Designation', 'wp-facebook-importer'),

                'Max_Import' => __('Max. Import', 'wp-facebook-importer'),
                'Import' => __('Import', 'wp-facebook-importer'),
                'User_ID' => __('User ID', 'wp-facebook-importer'),
                'Page_ID' => __('Page ID', 'wp-facebook-importer'),
                'User_active' => __('User active', 'wp-facebook-importer'),
                'Post_Category' => __('Post Category', 'wp-facebook-importer'),
                'Event_Category' => __('Event Category', 'wp-facebook-importer'),
                'Start_Post' => __('Start post', 'wp-facebook-importer'),
                'End_Post' => __('End post', 'wp-facebook-importer'),
                'Start_Event' => __('Start event', 'wp-facebook-importer'),
                'End_Event' => __('End event', 'wp-facebook-importer'),
                'Event_Status' => __('Event status', 'wp-facebook-importer'),
                'Post_Status' => __('Post status', 'wp-facebook-importer'),
                'delete' => __('delete', 'wp-facebook-importer'),
                'Edit' => __('Edit', 'wp-facebook-importer')

            ],
            'language_modal' => [
                'Neue_Kategorie_erstellen' => __('Create a new category','wp-facebook-importer'),
                'Name' => __('Name', 'wp-facebook-importer'),
                'name_help'=> __('This name is then displayed on the website', 'wp-facebook-importer'),
                'Titelform'=> __('Title form', 'wp-facebook-importer'),
                'create' => __('create', 'wp-facebook-importer'),
                'Abbrechen' => __('cancel', 'wp-facebook-importer'),
                'titel_form_help' => __('The "title form" is the readable URL variant of the name. It usually consists only of lower case letters, numbers and hyphens.','wp-facebook-importer'),
                'Beschreibung' => __('Description', 'wp-facebook-importer'),
                'beschreibung_help' => __('The description is not always displayed. In some themes it may be displayed.','wp-facebook-importer'),
                'del_posts' => __('Really delete all posts?', 'wp-facebook-importer'),
                'del_import' => __('Delete import really?', 'wp-facebook-importer'),
                'btn_delete_import' => __('Delete import','wp-facebook-importer'),
                'btn_delete_posts' => __('Delete posts','wp-facebook-importer'),
                'del_header_post' => __('Delete posts?', 'wp-facebook-importer'),
                'delete_import_note' => __('All posts and events will be irrevocably deleted! The deletion can not be undone.','wp-facebook-importer'),
                'delete_posts_note' => __('All posts will be irrevocably deleted! The deletion can not be undone.','wp-facebook-importer'),
                'alle_events_delete'=> __('Delete all events','wp-facebook-importer'),
                'alert_delete_all_events' => __('Really delete all events?', 'wp-facebook-importer'),
                'alert_delete_all_events_msg' => __('All events will be irrevocably deleted! The deletion can not be undone.','wp-facebook-importer'),
            ],
            'ajax_msg' => [
                'delete_import_title' => __('Delete import really?', 'wp-facebook-importer'),
            ]

        ];

        if ($args) {
            return $this->plugin_default_values[$args];
        } else {
            return $this->plugin_default_values;
        }
    }
}
