<?php

namespace WPFacebook\Importer;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Wp_Facebook_Importer;
use stdClass;

/**
 *  The Facebook API class.
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes/fb-api-importer
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
defined('ABSPATH') or die();

class Facebook_Import_Api
{
    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_dir plugin Slug Path.
     */
    protected string $plugin_dir;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * The Facebook-SDK of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Facebook $fbApi Facebook-SDK
     */
    private Facebook $fbApi;


    /**
     * The Version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current Version of this plugin.
     */
    private string $version;

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

    /**
     * The trait for the default settings
     * of the plugin.
     */
    use WP_Facebook_Importer_Defaults;

    /**
     * @param string $basename
     * @param string $version
     * @param Wp_Facebook_Importer $main
     * @throws FacebookSDKException
     */
    public function __construct(string $basename, string $version, Wp_Facebook_Importer $main)
    {

        $this->basename = $basename;
        $this->version = $version;
        $this->main = $main;
        if ($this->main->get_fb_sdk()) {
            $this->fbApi = $this->main->get_fb_sdk();
        }
        $this->settings = (object)[];
        $settings = $this->main->get_settings();
        if ($settings) {
            $this->settings = $settings;
        }
    }


    /**
     * @param $args
     * @return object
     */
    public function get_api_facebook_posts($args) {
        $record = new stdClass();
        $record->status = false;
        $record->count = 0;

        if (!isset($this->settings->access_token) || !$this->settings->access_token) {
            $record->title = __('Error', 'wp-facebook-importer');
            $record->msg = __('no access token stored!', 'wp-facebook-importer');
            return $record;
        }

        try {
            $response = $this->fbApi->get('/'.$args->apiId.'/feed?fields=is_published, is_expired, is_hidden, media_type, status_type, created_time, updated_time, message, attachments{type, title, description, unshimmed_url, media{source}, target{id} }, id, permalink_url, full_picture, from'.$args->filter, $this->settings->access_token);
            //$response = $this->fbApp->get('/'.$args->apiId.'/feed?fields=is_published, is_expired, is_hidden, media_type, status_type, created_time, updated_time, message, attachments{type, title, description, unshimmed_url, media{source}, target{id} }, id, permalink_url, full_picture, from&since='.$args->since.'&until='.$args->until.'', $this->settings->access_token);
            $getGraphEdge = $response->getGraphEdge()->asArray();

        } catch (FacebookResponseException $e) {
            $record->title = __('Facebook Graph error: ', 'wp-facebook-importer');
            $record->msg = $e->getMessage();
            return $record;

        } catch (FacebookSDKException $e) {
            $record->title = __('Facebook SDK error: ', 'wp-facebook-importer');
            $record->msg = $e->getMessage();
            return $record;
        }

        if (!$getGraphEdge) {
            $record->title = __('NO Data: ', 'wp-facebook-importer');
            $record->msg = __('no new data found!', 'wp-facebook-importer');
            return $record;
        }

        $record->status = true;
        $record->record = $getGraphEdge;
        $record->count = count($getGraphEdge);
        return $record;
    }

    /**
     * @param $args
     *
     * @return object
     * @throws FacebookSDKException
     */
    public function get_api_facebook_events($args): object
    {
        date_default_timezone_set('Europe/Berlin');
        $record = new stdClass();
        $record->status = false;
        $record->count = 0;

        if (!isset($this->settings->access_token) || !$this->settings->access_token) {
            $record->title = __('Error', 'wp-facebook-importer');
            $record->msg = __('no access token stored!', 'wp-facebook-importer');
            return $record;
        }


        try {
            $response = $this->fbApi->get('/'.$args->apiId.'/events/?fields=description,end_time,event_times,is_canceled,is_draft,name,place,start_time,type,updated_time&limit='.$args->limit, $this->settings->access_token);
           // $response = $this->fbApi->get('/'.$args->apiId.'/events/?fields=description,end_time,event_times,is_canceled,is_draft,name,place,start_time,type,updated_time&start_time>now()&limit='.$args->limit, $this->settings->access_token);
            $getGraphEdge = $response->getGraphEdge()->asArray();

        } catch (FacebookResponseException $e) {
            $record->status = false;
            $record->msg = __('Facebook Graph error: ', 'wp-facebook-importer') . $e->getMessage();
            return $record;

        } catch (FacebookSDKException $e) {
            $record->status = false;
            $record->msg = __('Facebook SDK error: ', 'wp-facebook-importer') . $e->getMessage();
            return $record;
        }

        if (!$getGraphEdge) {
            $record->status = false;
            $record->msg = __('no data found!', 'wp-facebook-importer');
            return $record;
        }

        $record->status = true;
        $record->record = $getGraphEdge;
        $record->count = count($getGraphEdge);
        return $record;

    }

    public function check_fb_access_token(): object
    {
        $record = new stdClass();
        $record->status = false;
        $record->title = '';
        if (!isset($this->settings->access_token) || !$this->settings->access_token) {
            $record->title = __('Error', 'wp-facebook-importer');
            $record->msg = __('no access token stored!', 'wp-facebook-importer');
            return $record;
        }

        try {
            $response = $this->fbApi->get('/me', $this->settings->access_token);
        } catch (FacebookResponseException $e) {
            $record->title = __('Facebook Graph error: ', 'wp-facebook-importer');
            $record->msg = $e->getMessage();
            return $record;
        } catch (FacebookSDKException $e) {
            $record->title = __('Facebook SDK error: ', 'wp-facebook-importer');
            $record->msg = $e->getMessage();
            return $record;
        }

        try {
            $me = $response->getGraphUser();
        } catch (FacebookSDKException $e) {
            $record->title = __('Facebook SDK error: ', 'wp-facebook-importer');
            $record->msg = $e->getMessage();
            return $record;
        }

        $record->status = true;
        $record->msg = __('Logged in as ', 'wp-facebook-importer') . $me->getName();
        return $record;
    }


}