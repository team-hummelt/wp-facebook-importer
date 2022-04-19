<?php

namespace WPFacebook\Importer;

use Exception;
use Facebook\Exceptions\FacebookSDKException;
use stdClass;
use Wp_Facebook_Importer;

/**
 *  Import Custom Post-Type class.
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes/fb-api-importer
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
defined('ABSPATH') or die();

class Import_WP_Custom_Events
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * The Version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current Version of this plugin.
     */
    private string $version;


    private WP_Facebook_Importer_Database $db;
    private WP_Facebook_Importer_Helper $helper;
    private Facebook_Import_Api $fb_api;

    /**
     * The FB-API Settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $settings The FB-API Settings for this Plugin
     */
    private object $settings;

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

        global $FbImporterDatabase, $fbApi, $pluginHelper;
        $this->db = $FbImporterDatabase;
        $this->helper = $pluginHelper;
        $this->fb_api = $fbApi;
        $this->basename = $basename;
        $this->version = $version;
        $this->main = $main;
        $this->settings = (object)[];
        $settings = $this->main->get_settings();
        if ($settings) {
            $this->settings = $settings;
        }

    }

    /**
     * Synchronisieren der Facebook Events.
     *
     * @throws FacebookSDKException
     * @throws Exception
     * @subpackage Wp_Facebook_Importer/includes/fb-api-importer
     * @author     Jens Wiecker <email@jenswiecker.de>
     * @package    Wp_Facebook_Importer
     */
    public function sync_facebook_events($id):object
    {
        $response = new stdClass();
        $response->status = false;
        $response->count = 0;
        $record = new  stdClass();

        $imports = $this->db->get_import_daten($id);

        if (!$imports) {
            return $response;
        }

        $eventOpt = get_option('fb_cronjob_settings');
        $record->limit = (int) $eventOpt['min_event_count'];
        $imports->user_aktiv ? $record->apiId = $imports->user_id : $record->apiId = $imports->page_id;

        $fbEvents = $this->fb_api->get_api_facebook_events($record);
        if (!$fbEvents->status) {
            $response->title = __('NO Data: ', 'wp-facebook-importer');
            $response->msg = __('no new data found!', 'wp-facebook-importer');
            return $response;
        }

        $errArr = [];
        $i = 0;
        $c = 0;
        $count = $fbEvents->count > 0 ? $fbEvents->count - 1 : 0;
        foreach ($fbEvents->record as $tmp) {

            if ($this->db->check_double_post($tmp['id'])) {
                continue;
            }
            $makeEvent = $this->sync_events_imports($tmp, $imports);

            if($makeEvent->status != 'warning'){
                $i++;
            }

            if (!$makeEvent->status) {
                $err_item = [
                    'status' => $makeEvent->status,
                    'fb_id' => $tmp['id'],
                    'title' => $makeEvent->title,
                    'msg' => $makeEvent->msg
                ];
                $errArr[] = $err_item;
                $c--;
            }
            $c++;

        }

        //Delete Old Events
        $oldEvents = $this->db->check_db_events_by_args('event','older_now');
        if($oldEvents){
            foreach ($oldEvents as $tmp){
                wp_delete_post($tmp, true);
            }
        }

        $this->db->update_last_sync($id, current_time('timestamp'));

        $response->status = !$errArr;
        $response->count = $c;
        $response->events_found = $count;
        $response->msg = $errArr;
        return $response;
    }


    /**
     * @param $fbData
     * @param $import
     * @return object
     * @throws Exception
     */
    private function sync_events_imports($fbData, $import):object
    {
        $return = new stdClass();
        $record = new stdClass();

        $record->fb_id = $fbData['id'];
        $record->type = $fbData['type'];
        $record->is_canceled = $fbData['is_canceled'];
        $record->is_draft = $fbData['is_canceled'];
        $record->name = $fbData['name'];
        if (isset($fbData['place']) && $fbData['place']) {
            $record->place = json_encode($fbData['place']);
        } else {
            $record->place = false;
        }


        if(isset($fbData['end_time']) && $fbData['end_time']){
            $record->end_time = $this->helper->convert_event_time($fbData['end_time']);
        } else {
            $record->end_time= '';
        }

        if(isset($fbData['start_time']) && $fbData['start_time']){
            $record->start_time = $this->helper->convert_event_time($fbData['start_time']);
        } else {
            $record->start_time = '';
        }

        if(isset($fbData['updated_time']) && $fbData['updated_time']){
            $record->post_modified = $this->helper->convert_datetime($fbData['updated_time']);
        } else {
            $record->post_modified = '';
        }


        //$record->start_time = $this->helper->convert_event_time($fbData['start_time']);
       // $record->end_time = $this->helper->convert_event_time($fbData['end_time']);
       // $record->post_modified = $this->helper->convert_datetime($fbData['updated_time']);

        isset($fbData['description']) && $fbData['description'] ? $record->description = $fbData['description'] : $record->description = 'no-content';
        if($record->end_time) {
            $endString = strtotime($record->end_time);
            $now = current_time('timestamp');

            if ($endString - $now < 0) {
                $return->status = 'warning';
                $return->title = __('Info', 'wp-facebook-importer');
                $return->msg = __('Event has expired!', 'wp-facebook-importer');
                return $return;
            }
        } else {
            $record->end_time = date('Y-m-d H:i:s', current_time('timestamp'));
        }


        if($this->db->check_double_post($fbData['id'])){
            $this->db->delete_post_by_fb_id($fbData['id'], 'event');
        }

        $dbPosts = $this->db->get_wp_facebook_posts('event');
        $idsArr = [];
        if ($dbPosts->status) {
            foreach ($dbPosts->record as $post) {
                $fbId = get_post_meta($post->ID, '_fb_id', true);
                if ($fbId) {
                    $ids_item = [
                        'fb_id' => $fbId,
                        'post_id' => $post->ID
                    ];
                    $idsArr[] = $ids_item;
                }
            }
        }

        foreach ($idsArr as $arr) {
           if($arr['fb_id'] == $record->fb_id) {
               $record->post_id = $arr['post_id'];
               $update = $this->update_wp_custom_event_post($record);
               if(!$update->status){
                   $return->title = $update->title;
                   $return->msg = $update->msg;
                   return $return;
               }
               $return->status = 'warning';
               $return->title = __('Error', 'wp-facebook-importer');
               $return->msg = __('Event already available!', 'wp-facebook-importer');
               return $return;
           }
        }

        $term = $this->db->get_term_by_term_id($import->event_term);
        if (!$term->status) {
            $return->title = __('Error', 'wp-facebook-importer');
            $return->msg = 'Term not found.' . $record->name;
            return $return;
        }
        $term = $term->term;

        $args = array(
            'post_title' => $this->set_wp_event_title(strtotime($record->start_time), $import->bezeichnung),
            'post_type' => 'wp_facebook_posts',
            'post_content' => $record->description,
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'post_excerpt' => $record->name,
            'post_date' => current_time('mysql'),
            'post_author' => $this->helper->get_admin_id(),
            'post_category' => array((int)$import->event_term),
            'meta_input' => array(
                '_fb_id' => $record->fb_id,
                '_import_id' => $import->id,
                '_post_type' => 'event',
                '_fb_page_id' => $import->page_id,
                '_fb_type' => $record->type,
                '_event_order' => strtotime($record->start_time),
                '_fb_event_id' => $record->fb_id,
                '_fb_event_link' => 'https://www.facebook.com/events/' . $record->fb_id . '/',
                '_fb_event_name' => $record->name,
                '_fb_start_time' => $record->start_time,
                '_fb_end_time' => $record->end_time,
                '_fb_place' => $record->place,
                '_fb_has_place' => (bool)$record->place,
            )
        );


        $wp_post_id = wp_insert_post($args, true);
        if (is_wp_error($wp_post_id)) {
            $return->title = __('Error', 'wp-facebook-importer');
            $return->msg = $wp_post_id->get_error_message();
            return $return;
        }


        //JOB Kategorie für neuen Beitrag setzen
       $setTerms = wp_set_object_terms($wp_post_id, array($term->term_id), $term->taxonomy);
        if (is_wp_error($setTerms)) {
            $return->title = __('Error', 'wp-facebook-importer');
            $return->msg = $setTerms->get_error_message();
            return $return;
        }

        $return->status = true;
        $return->event_name = $args['post_title'];
        $return->fb_event_created = current_time('mysql');
        return $return;
    }

    /**
     * @param $record
     * @return object
     */
    private function update_wp_custom_event_post($record):object
    {
        $return = new stdClass();
        $return->status = false;
        $args = array(
            'ID' => $record->post_id,
            'post_type' => 'wp_facebook_posts',
            'post_content' => $record->description,
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'post_excerpt' => $record->name,
            'post_date' => $record->post_modified,
            'meta_input' => array(
                '_fb_type' => $record->type,
                '_fb_id' => $record->fb_id,
                '_fb_event_link' => 'https://www.facebook.com/events/' . $record->fb_id . '/',
                '_fb_event_name' => $record->name,
                '_event_order' => strtotime($record->start_time),
                '_fb_start_time' => $record->start_time,
                '_fb_end_time' => $record->end_time,
                '_fb_place' => $record->place,
                '_fb_has_place' => (bool)$record->place,
            )
        );
       $update = wp_update_post($args, true);
        if (is_wp_error($update)) {
            $return->title = __('Error', 'wp-facebook-importer');
            $return->msg = $update->get_error_message();
            return $return;
        }
        $return->status = true;
        return $return;
    }

    /**
     * @param int $timestamp
     * @param string $bezeichnung
     * @return string
     */
    private function set_wp_event_title(int $timestamp, string $bezeichnung): string
    {
        return $bezeichnung . ' ' . __('Event', 'wp-facebook-importer') . ' - ' . date('d.m.Y', $timestamp) . ' um ' . date('H:i', $timestamp) . ' Uhr';
    }

}
