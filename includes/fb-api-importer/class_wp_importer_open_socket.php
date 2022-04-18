<?php

namespace WPFacebook\Importer;

use Wp_Facebook_Importer;

class Wp_Importer_Open_Socket
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    private static $instance;

    use WP_Facebook_Importer_Defaults;

    private WP_Facebook_Importer_Database $db;
    private WP_Facebook_Importer_Helper $helper;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Facebook_Importer $main The main class.
     */
    private Wp_Facebook_Importer $main;


    /**
     * @return static
     */
    public static function instance(string $plugin_dir, Wp_Facebook_Importer $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($plugin_dir, $main);
        }
        return self::$instance;
    }


    public function __construct(string $plugin_name, Wp_Facebook_Importer $main)
    {
        $this->main = $main;
        $this->basename = $plugin_name;
        global $FbImporterDatabase, $pluginHelper;
        $this->db = $FbImporterDatabase;
        $this->helper = $pluginHelper;
        $this->fb_api_make_cronjob();
    }

    public function fb_api_make_cronjob()
    {

        if($_REQUEST['cronjob'] !== $this->main->get_cronjob_exec_id()){
            exit();
        }

        $id = (int) $_REQUEST['import'];
        if(!$id){
            exit();
        }

        $logDir = WP_FACEBOOK_IMPORTER_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'log';
        $file = $logDir . DIRECTORY_SEPARATOR . 'cron-extern-sync.log';
        if(is_file($file)){
           // @unlink($file);
        }
        $time = date('\a\m d.m.Y \u\m H:i:s', current_time('timestamp'));
        $args = sprintf('WHERE id=%d', $id);
        $import = apply_filters('wp-facebook-importer/get_facebook_imports', $args, false);
        if(!$import->status){
            $msg = __('Synchronisation error', 'wp-facebook-importer');
            $logMsg = $time . ' Uhr | Import ID: '.$_REQUEST['import'].' | Message: '.$msg.' ' . "\r\n";
            file_put_contents($file, $logMsg, FILE_APPEND | LOCK_EX);
        }
        $syncPosts = apply_filters($this->basename . '/sync_facebook_posts', $id);
        if ($syncPosts->status) {
            $msg = __('Posts successfully updated.', 'wp-facebook-importer');
        } else {
            $msg = __('Synchronisation error', 'wp-facebook-importer');
        }
        $logMsg = $time . ' Uhr | Type: Post | Import ID: '.$_REQUEST['import'].' | Bezeichnung: ' . $import->record->bezeichnung . ' |  Message: '.$msg.' ' . "\r\n";
        file_put_contents($file, $logMsg, FILE_APPEND | LOCK_EX);

        $syncEvents = apply_filters($this->basename . '/sync_facebook_events', $id);
        if ($syncEvents->status) {
            $msg = __('Posts successfully updated.', 'wp-facebook-importer');
        } else {
            $msg = __('Synchronisation error', 'wp-facebook-importer');
        }
        $logMsg = $time . ' Uhr | Type: Event | Import ID: '.$_REQUEST['import'].' | Bezeichnung: ' . $import->record->bezeichnung . ' |  Message: '.$msg.' ' . "\r\n";
        file_put_contents($file, $logMsg, FILE_APPEND | LOCK_EX);

    }
}

