<?php
namespace WPFacebook\Importer;
use stdClass;
use Wp_Facebook_Importer;

/**
 *  The Facebook API CronJob class.
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes/fb-api-importer
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
defined( 'ABSPATH' ) or die();

class Import_Api_Cronjob {

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

    public function __construct(string $basename, string $version, Wp_Facebook_Importer $main ) {

        $this->basename   = $basename;
        $this->version    = $version;
        $this->main       = $main;
        $this->settings = (object) [];
        $settings = $this->main->get_settings();
        if($settings){
            $this->settings = $settings;
            if($this->settings->cron_aktiv){
                if (!wp_next_scheduled('fb_api_plugin_sync')) {
                    wp_schedule_event(time(), $this->settings->sync_interval, 'fb_api_plugin_sync');
                }
            }
        }
    }

    public function fb_importer_plugin_wp_un_schedule_event($args): void
    {
        $timestamp = wp_next_scheduled('fb_api_plugin_sync');
        wp_unschedule_event($timestamp, 'fb_api_plugin_sync');
    }

    public function fb_importer_plugin_wp_delete_event($args): void
    {
        wp_clear_scheduled_hook('fb_api_plugin_sync');
    }

    public function fb_importer_plugin_run_schedule_task($args): void
    {

        if($this->settings){
            $schedule = $this->settings->sync_interval;
        } else {
           $schedule = 'daily';
        }
        $time = get_gmt_from_date(gmdate('Y-m-d H:i:s', current_time('timestamp')), 'U');
        $args = [
            'timestamp' => $time,
            'recurrence' => $schedule->recurrence,
            'hook' => 'fb_api_plugin_sync'
        ];

        $this->schedule_task($args);
    }

    /**
     * @param $task
     * @return void
     */
    private function schedule_task($task): void
    {

        /* Must have task information. */
        if (!$task) {
            return;
        }

        /* Set list of required task keys. */
        $required_keys = array(
            'timestamp',
            'recurrence',
            'hook'
        );

        /* Verify the necessary task information exists. */
        $missing_keys = [];
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $task)) {
                $missing_keys[] = $key;
            }
        }

        /* Check for missing keys. */
        if (!empty($missing_keys)) {
            return;
        }

        /* Task darf nicht bereits geplant sein. */
        if (wp_next_scheduled($task['hook'])) {
            wp_clear_scheduled_hook($task['hook']);
        }

        /* Schedule the task to run. */
        wp_schedule_event($task['timestamp'], $task['recurrence'], $task['hook']);
    }

}
