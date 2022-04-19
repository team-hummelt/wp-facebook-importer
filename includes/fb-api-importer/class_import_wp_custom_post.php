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

class Import_WP_Custom_Post
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
     * Synchronisieren der Facebook Posts.
     *
     * @throws FacebookSDKException
     * @throws Exception
     * @subpackage Wp_Facebook_Importer/includes/fb-api-importer
     * @author     Jens Wiecker <email@jenswiecker.de>
     * @package    Wp_Facebook_Importer
     */
    public function sync_facebook_posts($importId): object
    {
        $response = new stdClass();
        $record = new stdClass();
        $response->status = false;
        $response->count = 0;
        $dbSince = '';
        $last_date = '';

        $imports = $this->db->get_import_daten($importId);

        if (!$imports) {
            return $response;
        }
        $limits = [];
        $imports->post_time_from ? $count = $imports->post_time_from : $count = 0;
        if ($count) {
            $since = '&since=' . strtotime(current_time('mysql') . '-' . $count . ' month');
            $dbSince = strtotime(current_time('mysql') . '-' . $count . ' month');
            $until = '&until=' . current_time('timestamp');
        } else {
            $since = '';
            $until = '';
        }

        if ($imports->until_sync) {
            $since = '&since=' . $imports->until_sync;
            $until = '&until=' . current_time('timestamp');
        }

        if($imports->last_sync){
            $limit = $imports->cron_sync_count;
        } else {
            if($imports->max_count > 0){
                $limit = $imports->max_count;
            } else {
                $limit = '';
            }
        }

        $limit ? $limits['limit'] = '&limit=' . $limit : $limits['limit'] = '';
        $imports->from_sync ? $limits['since'] = '&since=' . $imports->from_sync : $limits['since'] = $since;
        $imports->until_sync ? $limits['until'] = '&until=' . $imports->until_sync : $limits['until'] = $until;

        $filter = array_values(array_filter(array_merge_recursive($limits)));
        $record->filter = implode('', $filter);
        $record->last_sync = current_time('timestamp');
        $imports->user_aktiv ? $record->apiId = $imports->user_id : $record->apiId = $imports->page_id;

        $fbData = $this->fb_api->get_api_facebook_posts($record);
        if (!$fbData->status) {
            $response->title = __('NO Data: ', 'wp-facebook-importer');
            $response->msg = __('no new data found!', 'wp-facebook-importer');
            return $response;
        }

        $errArr = [];
        $i = 0;
        $count = $fbData->count - 1;
        foreach ($fbData->record as $tmp) {
            if ($this->db->check_double_post($tmp['id'])) {
                continue;
            }

            if ($imports->import_no_image && !isset($tmp['full_picture']) || !$tmp['full_picture']) {
                continue;
            }

            $makePost = $this->sync_post_imports($tmp, $imports);
            if (!isset($makePost->status) || !$makePost->status) {
                $err_item = [
                    'fb_id' => $tmp['id'],
                    'title' => __('Error'),
                    'msg' => $makePost->msg
                ];
                $errArr[] = $err_item;
            }
            if ($i == $count) {
                $last_date = $makePost->fb_post_created;
            }
            $i++;
        }

        if (!$errArr && $i != 0) {
            if ($last_date) {
                $last_date = strtotime($last_date);
            }

            $last_date ? $lastDate = $last_date: $lastDate = current_time('timestamp');
            $dbSince ? $since = $dbSince : $since = current_time('timestamp');
            $update = [
                'last_sync' => current_time('timestamp'),
                'from_sync' => $since,
                'until_sync' => $lastDate,
                'id' => $importId
            ];

            $this->db->update_last_sync_import((object)$update);
        } else {
            $this->db->update_last_sync($importId, current_time('timestamp'));
        }

        //Delete Post Limits
        $this->db->delete_old_wp_facebook_posts($importId);

        $response->status = !$errArr;
        $response->count = $i;
        $response->msg = $errArr;
        return $response;
    }

    /**
     * @param $fbData
     * @param $import
     * @return object
     * @throws Exception
     */
    private function sync_post_imports($fbData, $import): object
    {
        $data = new stdClass();
        $return = new stdClass();
        $fbID = filter_var($fbData['id'], FILTER_SANITIZE_STRING);
        $postIdArr = explode('_', $fbID);
        if (is_array($postIdArr)) {
            $data->fb_post_id = $postIdArr[1];
            $data->fb_user_id = $postIdArr[0];
        } else {
            $return->title = __('NO Data: ', 'wp-facebook-importer');
            $return->msg = __('no new data found!', 'wp-facebook-importer');
            return $return;
        }

        filter_var($fbData['full_picture'], FILTER_VALIDATE_URL) ? $data->fb_img_url = $fbData['full_picture'] : $data->fb_img_url = '';
        filter_var($fbData['permalink_url'], FILTER_VALIDATE_URL) ? $data->fb_permalink = $fbData['permalink_url'] : $data->fb_permalink = '';
        isset($fbData['attachments'][0]['unshimmed_url']) ? $data->fb_link = $fbData['attachments'][0]['unshimmed_url'] : $data->fb_link = '';
        isset($fbData['attachments'][0]['target']['id']) ? $data->fb_event_id = $fbData['attachments'][0]['target']['id'] : $data->fb_event_id = '';

        $data->fb_id = filter_var($fbData['id'], FILTER_SANITIZE_STRING);
        $data->fb_created = $this->helper->convert_datetime($fbData['created_time']);
        $data->fb_update = $this->helper->convert_datetime($fbData['updated_time']);
        $data->fb_user_name = $fbData['from']['name'];
        $data->fb_post_type = $fbData['attachments'][0]['type'];
        $data->titel = $this->set_wp_post_title(strtotime($data->fb_created), $import->bezeichnung);
        if ($fbData['message']) {
            $content = esc_html($fbData['message']);
            $content = preg_replace("/\s+/", " ", $content);
            $data->excerpt = substr($content, 0, 90) . '...';
        } else {
            $content = '';
        }

        if (!$content) {
            if ($fbData['description']) {
                $content = esc_html($fbData['description']);
                $content = preg_replace("/\s+/", " ", $content);
                $data->titel = substr($content, 0, 24);
                $data->excerpt = substr($content, 0, 90) . '...';
            } else {
                $content = '';
                $data->excerpt = '';
                $data->titel = __('no content', 'wp-facebook-importer');
            }
        }
        $data->content = $content;
        if (!$data->content && !$data->fb_img_url) {
            $return->title = __('NO Data:', 'wp-facebook-importer');
            $return->msg = 'kein Image und Content.  ' . $data->fb_created;
            return $return;
        }

        $term = $this->db->get_term_by_term_id($import->post_term);
        if (!$term->status) {
            $return->title = __('Error', 'wp-facebook-importer');
            $return->msg = 'Term not found.' . $data->fb_created;
            return $return;
        }
        $term = $term->term;
        $args = array(
            'post_title' => $data->titel,
            'post_type' => 'wp_facebook_posts',
            'post_content' => $data->content,
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'post_excerpt' => $data->excerpt,
            'post_date' => $data->fb_created,
            'post_author' => $this->helper->get_admin_id(),
            //'menu_order' => $i,
            'post_category' => array((int)$import->post_term),
            'meta_input' => array(
                '_import_bezeichnung' => $import->bezeichnung,
                '_post_type' => 'post',
                '_import_page_id' => $import->page_id,
                '_fb_id' => $data->fb_id,
                '_import_id' => $import->id,
                '_fb_post_id' => $data->fb_post_id,
                '_fb_user_id' => $data->fb_user_id,
                '_fb_img_url' => $data->fb_img_url,
                '_fb_permalink' => $data->fb_permalink,
                '_fb_link' => $data->fb_link,
                '_fb_type' => $data->fb_post_type,
                '_has_content' => (bool)$data->content,
                '_has_image' => (bool)$data->fb_img_url,
            )
        );

        $wp_post_id = wp_insert_post($args, true);
        if (is_wp_error($wp_post_id)) {
            $return->title = __('Error', 'wp-facebook-importer');
            $return->msg = $wp_post_id->get_error_message();
            return $return;
        }

        //TODO Kategorie für neuen Beitrag setzen
        $setTerms = wp_set_object_terms($wp_post_id, array($term->term_id), $term->taxonomy);

        if (is_wp_error($setTerms)) {
            $return->title = __('Error', 'wp-facebook-importer');
            $return->msg = $setTerms->get_error_message();
            return $return;
        }

        //TODO IMAGE SPEICHERN UND BEITRAGSBILD ERSTELLEN
        if ($data->fb_img_url) {
            $this->set_fb_image_to_post($data->fb_img_url, $wp_post_id);
        }
        $return->fb_post_created = $data->fb_created;
        $return->status = true;
        return $return;
    }

    private function set_fb_image_to_post($fb_img_url, $wp_post_id)
    {
        $url_parts = parse_url($fb_img_url);
        $extension = pathinfo($url_parts['path'], PATHINFO_EXTENSION);
        $extension = $extension ?: 'jpg';
        $wp_upload_dir = wp_upload_dir();
        $filename = $wp_upload_dir['path'] . '/' . $wp_post_id . '.' . $extension;
        if (copy($fb_img_url, $filename)) {
            $wp_filetype = wp_check_filetype(basename($filename), null);
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment($attachment, $filename, $wp_post_id);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
            wp_update_attachment_metadata($attach_id, $attach_data);
            set_post_thumbnail($wp_post_id, $attach_id);
        }
    }


    /**
     * GET Posts BY TermID
     * @param $termId
     * @return array
     */
    private function get_facebook_posts($termId): array
    {
        $args = [
            'post_type' => 'wp_facebook_posts',
            'tax_query' => [
                [
                    'taxonomy' => 'wp_facebook_category',
                    'terms' => $termId,
                    'include_children' => false
                ],
            ],
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        $posts = get_posts($args);
        $postIDS = [];
        foreach ($posts as $post) {
            $postIDS[] = $post->ID;
        }

        return $postIDS;
    }

    /**
     * @param int $timestamp
     * @param string $bezeichnung
     * @return string
     */
    protected function set_wp_post_title(int $timestamp, string $bezeichnung): string
    {
        return $bezeichnung . ' - ' . date('d.m.Y', $timestamp) . ' um ' . date('H:i', $timestamp) . ' Uhr';
    }


}
