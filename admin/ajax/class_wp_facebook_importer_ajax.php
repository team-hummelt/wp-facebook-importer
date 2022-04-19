<?php

namespace WPFacebook\Importer;

use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Wp_Facebook_Importer;
use stdClass;

defined('ABSPATH') or die();

/**
 * Define the Admin AJAX functionality.
 *
 * Loads and defines the Admin Ajax files for this plugin
 *
 *
 * @link       https://wwdh.de/
 * @since      1.0.0
 */

/**
 * Define the AJAX functionality.
 *
 * Loads and defines the Admin Ajax files for this plugin
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/admin
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class WP_Facebook_Importer_Ajax
{
    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $basename The ID of this plugin.
     */
    protected string $basename;

    /**
     * The AJAX METHOD
     *
     * @since    1.0.0
     * @access   private
     * @var      string $method The AJAX METHOD.
     */
    protected string $method;

    /**
     * The AJAX DATA
     *
     * @since    1.0.0
     * @access   private
     * @var      array|object $data The AJAX DATA.
     */
    private $data;

    /**
     * The trait for the default settings
     * of the plugin.
     */
    use WP_Facebook_Importer_Defaults;

    /**
     * The Version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current Version of this plugin.
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


    public function __construct(string $basename, string $version, Wp_Facebook_Importer $main)
    {
        $this->basename = $basename;
        $this->version = $version;
        $this->main = $main;

        $this->method = '';
        if (isset($_POST['daten'])) {
            $this->data = $_POST['daten'];
            $this->method = filter_var($this->data['method'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        }

        if (!$this->method) {
            $this->method = $_POST['method'];
        }
    }

    public function wp_facebook_importer_admin_ajax_handle()
    {
        $responseJson = new stdClass();
        $responseJson->type = $this->method;
        $record = new stdClass();
        $responseJson->status = false;
        $responseJson->msg = date('H:i:s', current_time('timestamp'));
        switch ($this->method) {
            case 'update_user_role':
                $user_role = filter_input(INPUT_POST, 'user_role', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                if (!$user_role) {
                    $responseJson->msg = '<span class="font-strong text-danger me-1">' . __('Error', 'wp-facebook-importer') . ': </span> ' . __('Ajax transmission error', 'wp-facebook-importer') . '!';
                    return $responseJson;
                }
                update_option('fb_importer_user_role', $user_role);
                $responseJson->status = true;
                break;

            case'import_form_handle':

                $record->import_name = filter_input(INPUT_POST, 'import_name', FILTER_SANITIZE_STRING);
                $record->post_description = filter_input(INPUT_POST, 'post_description', FILTER_SANITIZE_STRING);
                $import_count = filter_input(INPUT_POST, 'import_count', FILTER_SANITIZE_NUMBER_INT);
                $page_id = filter_input(INPUT_POST, 'page_id', FILTER_SANITIZE_STRING);
                $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_STRING);
                $post_cat = filter_input(INPUT_POST, 'post_cat', FILTER_SANITIZE_NUMBER_INT);
                $record->post_time_from = filter_input(INPUT_POST, 'post_time_from', FILTER_SANITIZE_NUMBER_INT);
                $event_cat = filter_input(INPUT_POST, 'event_cat', FILTER_SANITIZE_NUMBER_INT);
                filter_input(INPUT_POST, 'check_user_id', FILTER_SANITIZE_STRING) ? $check_user_id = 1 : $check_user_id = 0;
                $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
                filter_input(INPUT_POST, 'import_no_image', FILTER_SANITIZE_STRING) ? $record->import_no_image = 1 : $record->import_no_image = 0;

                if (!$type) {
                    $responseJson->status = false;
                    $responseJson->msg = __('Error', 'wp-facebook-importer');
                    return $responseJson;
                }

                if ($type == 'insert' && !$record->import_name) {
                    $responseJson->msg = __('The input field Name/Location is a mandatory field!', 'wp-facebook-importer');
                    return $responseJson;
                }


                $import_count != 0 ? $record->import_count = $import_count : $record->import_count = 5;
                if ($record->import_count > 100) {
                    $record->import_count = 100;
                }
                if ($check_user_id && !$user_id || !$check_user_id && !$page_id) {
                    $record->user_id = 'me';
                } else {
                    $record->user_id = $user_id;
                }
                $record->page_id = $page_id;
                $record->check_user_id = $check_user_id;
                $term_post_cat = '';
                $term_event_cat = '';
                if (!$post_cat || !$event_cat) {
                    $terms = apply_filters($this->basename . '/get_custom_terms', 'wp_facebook_category');
                    foreach ($terms->terms as $tmp) {
                        if ($tmp->name === 'Facebook Allgemein') {
                            $term_post_cat = $tmp->term_id;
                        }
                        if ($tmp->name == 'Facebook Veranstaltungen') {
                            $term_event_cat = $tmp->term_id;
                        }
                    }
                }

                $post_cat ? $record->post_term_id = $post_cat : $record->post_term_id = $term_post_cat;
                $event_cat ? $record->event_term_id = $event_cat : $record->event_term_id = $term_event_cat;

                $args = sprintf('WHERE bezeichnung ="%s"', $record->import_name);
                $dbImports = apply_filters($this->basename . '/get_facebook_imports', $args, false);
                if ($type == 'insert' && $dbImports->status) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = sprintf(__('Name or location "%s" already exists!', 'wp-facebook-importer'), $record->import_name);
                    return $responseJson;
                }

                if (!$record->user_id) {
                    $record->user_id = 'me';
                }

                switch ($type) {
                    case 'insert':
                        $record->aktiv = 1;
                        $insert = apply_filters($this->basename . '/set_facebook_imports', $record);
                        $insert->status ? $responseJson->title = __('saved', 'wp-facebook-importer') : $responseJson->title = __('Error while saving.', 'wp-facebook-importer');
                        $responseJson->status = $insert->status;
                        $responseJson->msg = $insert->msg;
                        $responseJson->type = $type;
                        $responseJson->reset = true;
                        break;
                    case 'update':
                        $record->id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                        if (!$record->id) {
                            $responseJson->title = __('Error', 'wp-facebook-importer');
                            $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                            return $responseJson;
                        }
                        $args = sprintf('WHERE id =%d', $record->id);
                        $import = apply_filters($this->basename . '/get_facebook_imports', $args, false);
                        if (!$import->status) {
                            $responseJson->title = __('Error', 'wp-facebook-importer');
                            $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                            return $responseJson;
                        }
                        $import = $import->record;
                        if ($import->bezeichnung !== $record->import_name) {
                            $args = sprintf('WHERE bezeichnung ="%s"', $record->import_name);
                            $dbImports = apply_filters($this->basename . '/get_facebook_imports', $args);
                            if ($dbImports->status) {
                                $responseJson->title = __('Error', 'wp-facebook-importer');
                                $responseJson->msg = sprintf(__('Name or location "%s" already exists!', 'wp-facebook-importer'), $record->import_name);
                                return $responseJson;
                            }
                        }

                        apply_filters($this->basename . '/update_facebook_import', $record);
                        $responseJson->status = true;
                        $responseJson->msg = __('Changes saved successfully.', 'wp-facebook-importer');
                        $responseJson->title = __('saved', 'wp-facebook-importer');
                        break;
                }

                break;
            case'change_import_settings':
                $record->column = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
                $record->id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                if (!$record->column || !$record->id) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                    return $responseJson;
                }

                $args = sprintf('WHERE id =%d', $record->id);
                $import = apply_filters($this->basename . '/get_facebook_imports', $args, false);
                if (!$import->status) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                    return $responseJson;
                }
                $import = $import->record;
                switch ($record->column) {
                    case'aktiv':
                        $import->aktiv ? $record->content = 0 : $record->content = 1;
                        break;
                    case 'user_aktiv':
                        $import->user_aktiv ? $record->content = 0 : $record->content = 1;
                        break;
                }

                $record->type = "'%d'";
                apply_filters($this->basename . '/update_imports_inputs', $record);
                $responseJson->status = true;
                $responseJson->msg = __('Changes saved successfully.', 'wp-facebook-importer');
                $responseJson->title = __('saved', 'wp-facebook-importer');
                break;

            case'get_import_data':
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                if (!$id) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                    return $responseJson;
                }
                $args = sprintf('WHERE id =%d', $id);
                $import = apply_filters($this->basename . '/get_facebook_imports', $args, false);
                if (!$import->status) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                    return $responseJson;
                }

                $import = $import->record;
                $import->type = 'update';


                $postTerm = apply_filters($this->basename . '/get_term_by_term_id', $import->post_term);
                $eventTerm = apply_filters($this->basename . '/get_term_by_term_id', $import->event_term);
                $postTerm ? $import->post_term_select = $postTerm->term->term_id : $import->post_term_select = '';
                $eventTerm ? $import->event_term_select = $eventTerm->term->term_id : $import->event_term_select = '';

                $siteLang = $this->get_plugin_defaults('language_formulare');
                $modalLang = $this->get_plugin_defaults('language_modal');
                $tableLang = $this->get_plugin_defaults('language_table');
                $select_sync_interval = $this->get_plugin_defaults('select_api_sync_interval');
                $select_max_post_sync = $this->get_plugin_defaults('max_post_sync');
                $lang = wp_parse_args($siteLang, $modalLang);
                $log = apply_filters($this->basename.'/get_plugin_syn_log','');

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


                if ($import->last_sync) {
                    $lastSync = date('d.m.Y H:i:s', $import->last_sync);
                    $lastSync = explode(' ', $lastSync);
                    $import->lastSynDate = $lastSync[0];
                    $import->lastSynTime = $lastSync[1];
                } else {
                    $import->lastSynDate = false;
                }

                if ($import->until_sync) {
                    $lastUntilSync = date('d.m.Y H:i:s', $import->until_sync);
                    $lastUntilSync = explode(' ', $lastUntilSync);
                    $import->lastUntilDate = $lastUntilSync[0];
                    $import->lastUntilTime = $lastUntilSync[1];
                } else {
                    $import->lastUntilDate = false;
                }


                $import->postCount = apply_filters($this->basename.'/get_wp_facebook_import_count',$id ,'post')->count;
                $import->eventCount = apply_filters($this->basename.'/get_wp_facebook_import_count', $id, 'event')->count;
                $selectFrom = array_merge_recursive($now, $selectFrom);
                $settings = apply_filters($this->basename . '/get_plugin_settings', false);
                $termsSelect = apply_filters($this->basename . '/get_custom_terms', 'wp_facebook_category');
                $termsSelect->status ? $kategorie = $termsSelect->terms : $kategorie = false;
                $template = '';
                $twigData = [
                    'log_status' => $log->status,
                    'data' => $import,
                    'version' => $this->version,
                    'cat_select' => $kategorie,
                    'l' => $lang,
                    'tl' => $tableLang,
                    's' => $settings->record,
                    'select_sync_interval' => $select_sync_interval,
                    'select_max_post_sync' => $select_max_post_sync,
                    'select_max_year' => $selectFrom,
                ];

                try {
                    $template = $this->main->get_twig()->render('@widget/import-formular.twig', $twigData);
                } catch (LoaderError|SyntaxError|RuntimeError $e) {
                    echo $e->getMessage();
                } catch (Throwable $e) {
                    echo $e->getMessage();
                }
                $responseJson->status = true;
                $responseJson->template = preg_replace(array('/<!--(.*)-->/Uis', "/[[:blank:]]+/"), array('', ' '), str_replace(array("\n", "\r", "\t"), '', $template));
                break;

            case 'set_facebook_category':
                $cat_name = filter_input(INPUT_POST, 'cat_name', FILTER_SANITIZE_STRING);
                $cat_slug = filter_input(INPUT_POST, 'cat_slug', FILTER_SANITIZE_STRING);
                $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

                if (!$cat_name) {
                    $responseJson->name = 'cat_name';
                    $responseJson->msg = __('Category name is a required field!', 'wp-facebook-importer');
                    return $responseJson;
                }
                if (!$cat_slug) {
                    $responseJson->name = 'cat_slug';
                    $responseJson->msg = __('The title form is required!', 'wp-facebook-importer');
                    return $responseJson;
                }

                $cat_slug = strtolower(preg_replace('/\s+/', '', $cat_slug));
                if (strlen($cat_slug) < 5) {
                    $responseJson->name = 'cat_slug';
                    $responseJson->msg = __('Title form must have at least 5 characters!', 'wp-facebook-importer');
                    return $responseJson;
                }

                if (!preg_match("~^[0-9a-z\-_]+$~i", $cat_slug)) {
                    $responseJson->name = 'cat_slug';
                    $responseJson->msg = __('Title form wrong format! Only letters, numbers, hyphens or underscores.', 'wp-facebook-importer');
                    return $responseJson;
                }

                $fb_terms = apply_filters($this->basename . '/get_custom_terms', 'wp_facebook_category');
                if ($fb_terms->status) {
                    foreach ($fb_terms->terms as $tmp) {
                        if ($tmp->name === $cat_name || $tmp->slug === $cat_slug) {
                            $responseJson->msg = __('Name or title form already exists!', 'wp-facebook-importer');
                            return $responseJson;
                        }
                    }
                }
                wp_insert_term(
                    $cat_name,
                    'wp_facebook_category',
                    array(
                        'description' => $description,
                        'slug' => $cat_slug
                    )
                );

                $responseJson->select = apply_filters($this->basename . '/get_custom_terms', 'wp_facebook_category')->terms;
                $responseJson->status = true;
                $responseJson->catName = $cat_name;
                $responseJson->selLang = __('select', 'wp-facebook-importer');
                $responseJson->msg = __('Changes saved successfully.', 'wp-facebook-importer');
                $responseJson->title = __('saved', 'wp-facebook-importer');
                break;

            case'set_plugin_settings':
                $settings = [];
                $errMsg = [];
                $successMsg = [];
                $settings['app_id'] = filter_input(INPUT_POST, 'app_id', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                $settings['app_secret'] = filter_input(INPUT_POST, 'app_secret', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                $settings['token'] = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
                $settings['sync_interval'] = filter_input(INPUT_POST, 'sync_interval', FILTER_SANITIZE_STRING);
                $settings['sync_max'] = filter_input(INPUT_POST, 'sync_max', FILTER_SANITIZE_NUMBER_INT);
                filter_input(INPUT_POST, 'cron_aktiv', FILTER_SANITIZE_STRING) ? $settings['cron_aktiv'] = 1 : $settings['cron_aktiv'] = 0;

                $DBSettings = apply_filters($this->basename . '/get_plugin_settings', sprintf('WHERE id="%s"', $this->main->get_settings_id()));
                $DBSettings = $DBSettings->record;
                foreach ($settings as $key => $val) {
                    switch ($key) {
                        case 'app_id':
                            if ($settings['app_id']) {
                                $record->content = apply_filters($this->basename . '/cleanWhitespace', $settings['app_id']);
                                $record->type = '%s';
                                $record->column = 'app_id';
                                apply_filters($this->basename . '/update_plugin_settings', $record);
                                $successMsg[] = 'app_id';
                            } else {
                                $errMsg[] = 'app_id';
                            }
                            break;
                        case'app_secret':
                            if ($settings['app_secret']) {
                                $record->content = apply_filters($this->basename . '/cleanWhitespace', $settings['app_secret']);
                                $record->type = '%s';
                                $record->column = 'app_secret';
                                apply_filters($this->basename . '/update_plugin_settings', $record);
                                $successMsg[] = 'app_secret';
                            } else {
                                $errMsg[] = 'app_secret';
                            }
                            break;
                        case'token':
                            $where = sprintf('WHERE id="%s"', $this->main->get_settings_id());
                            $dbSettings = apply_filters($this->basename . '/get_plugin_settings', $where);
                            if (!$settings['token'] && $dbSettings->status && $dbSettings->record->access_token) {
                                $successMsg[] = 'token';
                            } elseif ($settings['token']) {
                                $record->content = apply_filters($this->basename . '/cleanWhitespace', $settings['token']);
                                $record->type = '%s';
                                $record->column = 'access_token';
                                apply_filters($this->basename . '/update_plugin_settings', $record);
                                $successMsg[] = 'token';
                            } else {
                                $errMsg[] = 'token';
                            }
                            break;
                        case'sync_interval':
                            if ($DBSettings->sync_interval !== $settings['sync_interval']) {
                                $record->content = $settings['sync_interval'];
                                $record->type = '%s';
                                $record->column = 'sync_interval';
                                apply_filters($this->basename . '/update_plugin_settings', $record);
                                //JOB WARNING CronJob erstellen
                                wp_clear_scheduled_hook('fb_api_plugin_sync');
                                apply_filters('wp_api_run_schedule_task', false);
                            }
                            break;
                        case'sync_max':

                            $record->content = $settings['sync_max'];
                            $record->type = '%d';
                            $record->column = 'max_sync';
                            apply_filters($this->basename . '/update_plugin_settings', $record);
                            break;
                        case 'cron_aktiv':
                            if ($settings['cron_aktiv'] && $DBSettings->cron_aktiv !== $settings['cron_aktiv']) {
                                apply_filters('wp_api_run_schedule_task', false);
                            }
                            if (!$settings['cron_aktiv'] && $DBSettings->cron_aktiv !== $settings['cron_aktiv']) {
                                wp_clear_scheduled_hook('fb_api_plugin_sync');
                            }
                            //JOB WARNING CronJob erstellen
                            $record->content = $settings['cron_aktiv'];
                            $record->type = '%d';
                            $record->column = 'cron_aktiv';
                            apply_filters($this->basename . '/update_plugin_settings', $record);

                            break;
                    }
                }

                $responseJson->status = true;
                $responseJson->err_arr = $errMsg ? (object)$errMsg : false;
                $responseJson->success_arr = $successMsg ? (object)$successMsg : false;
                break;

            case'get_access_token':
                $DBSettings = apply_filters($this->basename . '/get_plugin_settings', sprintf('WHERE id="%s"', $this->main->get_settings_id()));
                $DBSettings = $DBSettings->record;
                $responseJson->status = true;
                $responseJson->msg = $DBSettings->access_token;
                break;

            case'check_status_access_token':

                $accessToken = apply_filters($this->basename . '/check_fb_access_token', '');

                $siteLang = $this->get_plugin_defaults('language_formulare');
                $modalLang = $this->get_plugin_defaults('language_modal');
                $lang = wp_parse_args($siteLang, $modalLang);
                $twigData = [
                    'status' => $accessToken->status,
                    'msg' => $accessToken->msg,
                    'title' => $accessToken->title,
                    'l' => $lang
                ];
                try {
                    $template = $this->main->get_twig()->render('@widget/fb-api-status-modal.twig', $twigData);
                } catch (LoaderError|SyntaxError|RuntimeError $e) {
                    echo $e->getMessage();
                } catch (Throwable $e) {
                    echo $e->getMessage();
                }
                $responseJson->template = preg_replace(array('/<!--(.*)-->/Uis', "/[[:blank:]]+/"), array('', ' '), str_replace(array("\n", "\r", "\t"), '', $template));
                $responseJson->status = true;
                break;

            case'import_delete_handle':
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
                if (!$type || !$id) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                    return $responseJson;
                }
                switch ($type) {
                    case 'import':
                        $responseJson->type = 'import';
                        apply_filters($this->basename . '/delete_imports_input', $id);
                        apply_filters($this->basename . '/delete_facebook_posts', $id, 'post');
                        apply_filters($this->basename . '/delete_facebook_posts', $id, 'event');
                        $responseJson->status = true;
                        $responseJson->title = __('Import deleted', 'wp-facebook-importer');;
                        $responseJson->msg = __('Import, posts and events deleted!', 'wp-facebook-importer');
                        break;
                    case'posts':
                        apply_filters($this->basename . '/delete_facebook_posts', $id, 'post');
                        $responseJson->type = 'posts';
                        $responseJson->id = $id;
                        $responseJson->no_collapse = true;
                        $responseJson->status = true;
                        $responseJson->title = __('Posts deleted', 'wp-facebook-importer');
                        $responseJson->msg = __('All posts and events deleted!', 'wp-facebook-importer');
                        break;
                    case'events':
                        apply_filters($this->basename . '/delete_facebook_events', $id);
                        $responseJson->type = 'event';
                        $responseJson->id = $id;
                        $responseJson->no_collapse = true;
                        $responseJson->status = true;
                        $responseJson->title = __('Events deleted', 'wp-facebook-importer');
                        $responseJson->msg = __('All posts and events deleted!', 'wp-facebook-importer');
                        break;
                    case'reset_sync_date':
                        $record->last_sync = '';
                        $record->from_sync = '';
                        $record->until_sync = '';
                        $record->id = $id;
                        apply_filters($this->basename.'/update_last_sync_import', $record);
                        $responseJson->status = true;
                        $responseJson->title = __('Reset successful', 'wp-facebook-importer');
                        $responseJson->msg = __('All posts and events reset.', 'wp-facebook-importer');
                        break;
                }
                break;

            case'sync_fb_posts':
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                if (!$id) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                    return $responseJson;
                }
                $syncImport = apply_filters($this->basename . '/sync_facebook_posts', $id);
                if ($syncImport->status) {
                    $responseJson->id = $id;
                    $responseJson->count = $syncImport->count;
                    $responseJson->no_collapse = true;
                    $responseJson->status = true;
                    $responseJson->title = __('Successfully Synchronized!', 'wp-facebook-importer');
                    $responseJson->msg = __('Posts successfully updated.', 'wp-facebook-importer');
                } else {
                    $postErrMsg = '';
                    foreach ($syncImport->msg as $tmp) {
                        $postErrMsg .= '<div class="mb-2"> <span class="d-block"><b class="font-strong">Post | Event: </b>' . $tmp['fb_id'] . '</span><span class="d-block"><b class="font-strong">Message: </b> ' . $tmp['msg'] . '</span></div>';
                    }
                    $responseJson->msg = '<h5 class="d-inline-block">' . __('Error', 'wp-facebook-importer') . '</h5>' . $postErrMsg;
                }
                break;

            case'sync_fb_events':
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                $responseJson->status_type = '';
                if (!$id) {
                    $responseJson->title = __('Error', 'wp-facebook-importer');
                    $responseJson->msg = __('Ajax transmission error', 'wp-facebook-importer');
                    return $responseJson;
                }
                $syncImport = apply_filters($this->basename . '/sync_facebook_events', $id);
                if ($syncImport->status) {
                    $responseJson->id = $id;
                    $responseJson->count = $syncImport->count;
                    $responseJson->no_collapse = true;
                    $responseJson->status = true;
                    $responseJson->title = __('Successfully Synchronized!', 'wp-facebook-importer');
                    $responseJson->msg = __('Events successfully updated.', 'wp-facebook-importer');
                } else {
                    $postErrMsg = '';
                    foreach ($syncImport->msg as $tmp) {
                        $postErrMsg .= '<div class="mb-2"> <span class="d-block"><b class="font-strong">Post | Event: </b>' . $tmp['fb_id'] . '</span><span class="d-block"><b class="font-strong">Message: </b> ' . $tmp['msg'] . '</span></div>';
                    }
                    $responseJson->msg = '<h5 class="d-inline-block">' . __('Error', 'wp-facebook-importer') . '</h5>' . $postErrMsg;
                }

                if ($syncImport->status_type == 'warning') {
                    $postErrMsg = '';
                    $responseJson->status_type = 'warning';
                    foreach ($syncImport->msg as $tmp) {
                        $postErrMsg .= '<div class="mb-2"> <span class="d-block"><b class="font-strong">Post | Event: </b>' . $tmp['fb_id'] . '</span><span class="d-block"><b class="font-strong">Message: </b> ' . $tmp['msg'] . '</span></div>';
                    }
                    $responseJson->msg = '<h5 class="d-inline-block">' . __('Info', 'wp-facebook-importer') . '</h5>' . $postErrMsg;
                }

                break;
            case'cronjob_system_settings':
                $min_sleep_post = filter_input(INPUT_POST, 'min_sleep_post', FILTER_SANITIZE_NUMBER_INT);
                $min_sleep_event = filter_input(INPUT_POST, 'min_sleep_event', FILTER_SANITIZE_NUMBER_INT);
                $min_event_count = filter_input(INPUT_POST, 'min_event_count', FILTER_SANITIZE_NUMBER_INT);

                if($min_sleep_post < 120 || !$min_sleep_post){
                    $min_sleep_post = 150;
                }
                if($min_sleep_event < 60 || !$min_sleep_event){
                    $min_sleep_event = 70;
                }
                if($min_event_count < 2 || !$min_event_count){
                    $min_event_count = 5;
                }
                if($min_event_count > 15 ){
                    $min_event_count = 15;
                }

                $settings = [
                    'min_sleep_post' => $min_sleep_post,
                    'min_sleep_event' => $min_sleep_event,
                    'min_event_count' => $min_event_count
                ];

                update_option('fb_cronjob_settings', $settings);
                $responseJson->status = true;
                $responseJson->msg = __('Changes saved successfully.', 'wp-facebook-importer');
                $responseJson->title = __('saved', 'wp-facebook-importer');
                break;
            case'delete_log':
                $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

                switch ($type){
                    case'one-log':
                        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                        apply_filters($this->basename.'/delete_plugin_syn_log', $id);
                        break;
                    case'all-log':
                         $log = apply_filters($this->basename.'/get_plugin_syn_log','');
                         if($log->status){
                             foreach ($log->record as $tmp){
                                 apply_filters($this->basename.'/delete_plugin_syn_log', $tmp->id);
                             }
                         }
                        break;
                }
                  $responseJson->status = true;
                break;
            case'get_next_sync_time':
                $settings = apply_filters($this->basename.'/get_plugin_settings','');
                if(!$settings->status){
                    return  $responseJson;
                }
                if(!$settings->record->cron_aktiv){
                    return $responseJson;
                }
                $nextTime = apply_filters($this->basename . '/get_next_cron_time', 'fb_api_plugin_sync');
                $responseJson->status = true;
                $responseJson->next_time = date('Y-m-d H:i:s', current_time('timestamp') + $nextTime);
                break;

            case'imports_data_table':
                $query = '';
                $columns = [
                    "bezeichnung",
                    "aktiv",
                    "max_count",
                    "user_id",
                    "page_id",
                    "user_aktiv",
                    "post_term",
                    "event_term",
                    ""];

                if (isset($_POST['search']['value'])) {
                    $query = ' WHERE bezeichnung LIKE "%' . $_POST['search']['value'] . '%"
                     OR max_count LIKE "%' . $_POST['search']['value'] . '%"
                     OR user_id LIKE "%' . $_POST['search']['value'] . '%"
                     OR page_id LIKE "%' . $_POST['search']['value'] . '%"
                    ';
                }
                if (isset($_POST['order'])) {
                    $query .= ' ORDER BY ' . $columns[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
                } else {
                    $query .= ' ORDER BY created_at ASC';
                }

                $limit = '';
                if ($_POST["length"] != -1) {
                    $limit = ' LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
                }
                $dbImports = apply_filters($this->basename . '/get_facebook_imports', $query . $limit);

                $data_arr = [];
                if (!$dbImports->status) {
                    return [
                        "draw" => $_POST['draw'],
                        "recordsTotal" => 0,
                        "recordsFiltered" => 0,
                        "data" => $data_arr
                    ];
                }

                foreach ($dbImports->record as $tmp) {
                    $post_term = apply_filters($this->basename . '/get_term_by_term_id', $tmp->post_term);
                    $event_term = apply_filters($this->basename . '/get_term_by_term_id', $tmp->event_term);
                    $tmp->max_count ? $max_count = $tmp->max_count : $max_count = __('No limit', 'wp-facebook-importer');
                    $tmp->aktiv ? $a = 'checked' : $a = '';
                    $tmp->user_aktiv ? $ua = 'checked' : $ua = '';
                    $check1 = '<div class="w-100 d-flex align-items-center justify-content-center"><div class="form-check form-switch">
                            <input data-id="' . $tmp->id . '" data-type="aktiv" class="form-check-input" type="checkbox"
                            id="CheckUserIdActive' . $tmp->id . '" ' . $a . '> </div></div>';

                    $check2 = '<div class="w-100 d-flex align-items-center justify-content-center"><div class="form-check form-switch">
                                <input data-id="' . $tmp->id . '" data-type="user_aktiv" class="form-check-input no-blur" type="checkbox"
                                id="CheckUserIdActive' . $tmp->id . '" ' . $ua . '> </div></div>';
                    $data_item = [];
                    $data_item[] = '<span class="font-strong">' . $tmp->bezeichnung . '</span>';
                    $data_item[] = $check1;
                    $data_item[] = '<span class="d-none">' . $tmp->max_count . '</span><span class="text-nowrap small">' . $max_count . '</span>';
                    $data_item[] = '<span class="small">' . $tmp->user_id . '</span>';
                    $data_item[] = '<span class="small">' . $tmp->page_id . '</span>';
                    $data_item[] = $check2;
                    $data_item[] = '<span class="d-block small lh-2">' . $post_term->term->name . '</span>';
                    $data_item[] = '<span class="d-block small lh-2">' . $event_term->term->name . '</span>';
                    $data_item[] = '<button data-id="' . $tmp->id . '" class="btn-load-import btn btn-blue-outline btn-table text-nowrap"><i class="bi bi-hdd-network me-1"></i> ' . __('Edit', 'wp-facebook-importer') . ' </button>';
                    $data_arr[] = $data_item;
                }

                $importCount = apply_filters($this->basename . '/get_facebook_imports', '');
                $responseJson = [
                    "draw" => $_POST['draw'],
                    "recordsTotal" => $importCount->count,
                    "recordsFiltered" => $importCount->count,
                    "data" => $data_arr
                ];

                break;

            case'cronjob_data_table':
                $query = '';
                $columns = [
                    "im.bezeichnung",
                    "im.post_term",
                    "im.event_term",
                    "sl.start_post",
                    "sl.end_post",
                    "sl.post_status",
                    "sl.start_event",
                    "sl.end_event",
                    "sl.event_status",
                    ""];

                if (isset($_POST['search']['value'])) {
                    $query = ' WHERE im.bezeichnung LIKE "%' . $_POST['search']['value'] . '%"
                    ';
                }
                if (isset($_POST['order'])) {
                    $query .= ' ORDER BY ' . $columns[$_POST['order']['0']['column']] . ' ' . $_POST['order']['0']['dir'] . ' ';
                } else {
                    $query .= ' ORDER BY created_at ASC';
                }

                $limit = '';
                if ($_POST["length"] != -1) {
                    $limit = ' LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
                }

                $table = apply_filters($this->basename.'/get_plugin_syn_log', $query . $limit);
                $data_arr = [];
                if (!$table->status) {
                    return [
                        "draw" => $_POST['draw'],
                        "recordsTotal" => 0,
                        "recordsFiltered" => 0,
                        "data" => $data_arr
                    ];
                }

                foreach ($table->record as $tmp) {
                    $post_term = apply_filters($this->basename . '/get_term_by_term_id', $tmp->post_term);
                    $event_term = apply_filters($this->basename . '/get_term_by_term_id', $tmp->event_term);
                    $tmp->post_status ? $statusPost = 'text-success bi bi-check-circle' : $statusPost = 'text-danger bi bi-x-circle';
                    $tmp->event_status ? $statusEvent = 'text-success bi bi-check-circle' : $statusEvent = 'text-danger bi bi-x-circle';

                    $data_item = [];
                    $data_item[] = '<span class="font-strong">' . $tmp->bezeichnung . '</span>';
                    $data_item[] = '<span class="d-block small lh-2">' . $post_term->term->name . '</span>';
                    $data_item[] = '<span class="d-none">' . $tmp->start_post . '</span><span class="small d-block lh-1">' . date('d.m.Y', $tmp->start_post) . '</span><span class="small-lg">' . date('H:i:s', $tmp->start_post) . '</span>';
                    $data_item[] = '<span class="d-none">' . $tmp->end_post . '</span><span class="small d-block lh-1">' . date('d.m.Y', $tmp->end_post) . '</span><span class="small-lg">' . date('H:i:s', $tmp->end_post) . '</span>';
                    $data_item[] = '<span class="d-none">'.$tmp->post_status.'</span><i class="'.$statusPost.'"></i>';
                    $data_item[] = '<span class="d-block small lh-2">' . $event_term->term->name . '</span>';
                    $data_item[] = '<span class="d-none">' . $tmp->start_event . '</span><span class="small d-block lh-1">' . date('d.m.Y', $tmp->start_event) . '</span><span class="small-lg">' . date('H:i:s', $tmp->start_event) . '</span>';
                    $data_item[] = '<span class="d-none">' . $tmp->end_event . '</span><span class="small d-block lh-1">' . date('d.m.Y', $tmp->end_event) . '</span><span class="small-lg">' . date('H:i:s', $tmp->end_event) . '</span>';
                    $data_item[] = '<span class="d-none">'.$tmp->event_status.'</span><i class="'.$statusEvent.'"></i>';
                    $data_item[] = '<button data-type="one-log" data-id="' . $tmp->id . '" class="btn-delete-log btn btn-outline-danger btn-table text-nowrap"><i class="bi bi-trash me-1"></i> ' . __('delete', 'wp-facebook-importer') . ' </button>';
                    $data_arr[] = $data_item;
                }


                $importCount = apply_filters($this->basename . '/get_plugin_syn_log', '');
                $responseJson = [
                    "draw" => $_POST['draw'],
                    "recordsTotal" => $importCount->count,
                    "recordsFiltered" => $importCount->count,
                    "data" => $data_arr
                ];
                break;
        }

        return $responseJson;
    }
}