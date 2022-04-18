<?php

namespace WPFacebook\Importer;

use Wp_Facebook_Importer;

class Import_Curl_Cronjob_Exec
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
        $this->importer_check_get_params();
    }


    public function importer_check_get_params()
    {
        $logDir = WP_FACEBOOK_IMPORTER_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'log';
        $file = $logDir . DIRECTORY_SEPARATOR . 'cron-extern-sync.log';
        $import = apply_filters('wp-facebook-importer/get_facebook_imports', 'WHERE aktiv=1');
        if(!$import->status){
            exit('no imports');
        }

        if (empty($_GET['id'])) {
            if (is_file($file)) {
                @unlink($file);
            }
            $this->curl_fb_api_response(1);

        } else {

            while (ob_get_level()) {
                ob_end_clean();
            }

            header('Connection: close');
            ignore_user_abort();
            ob_start();
            echo "Connection Closed" . date('d.m.Y H:i:s') . '<br>';
            $size = ob_get_length();
            header("Content-Length: $size");
            ob_end_flush();
            flush();

            $import = apply_filters('wp-facebook-importer/get_facebook_imports', 'WHERE aktiv=1');
            if (!$import->status) {
                exit('no imports');
            }

            $ids = [];
            foreach ($import->record as $tmp) {
                $ids[] = $tmp->id;
            }
            (int)$id = $_GET['id'];
            $where = sprintf('WHERE id=%d', $ids[$id - 1]);
            $import = apply_filters('wp-facebook-importer/get_facebook_imports', $where, false);
            if ($import->status) {
                $settingsSleep = get_option('fb_cronjob_settings');
                $postSleep = (int) $settingsSleep['min_sleep_post'];
                $eventSleep = (int) $settingsSleep['min_sleep_event'];
                $time = date('\a\m d.m.Y \u\m H:i:s', current_time('timestamp'));
                $import = $import->record;
                $msg = __('Synchronisation successful', 'hupa-fb-api');
                $logMsg = $time . ' Uhr|'.$import->bezeichnung.'|'.$import->id.' |'.$msg."\r\n";
                file_put_contents($file, $logMsg, FILE_APPEND | LOCK_EX);
                $post =  apply_filters($this->basename . '/sync_facebook_posts', $import->id);
                sleep($postSleep);
                $event = apply_filters($this->basename . '/sync_facebook_events', $import->id);
                sleep($eventSleep);
            }

            if ($id < count($ids)) {
                $id++;
                $this->curl_fb_api_response($id);
            }
        }
    }

    public function curl_fb_api_response(int $id)
    {
        $url = site_url() . '/?' . $this->main->get_cronjob_slug() . '=' . $this->main->get_cronjob_id() . '&id=' . $id;
        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array()
            )
        );

        if (curl_errno($ch)) {
            echo 'Curl-Fehler: ' . curl_error($ch);
            curl_close($ch);
        }

        $response = curl_exec($ch);
        echo 'Result: ' . $response . ' --Starte Synchronisation-- ' . date('d.m.Y H:i:s') . ' <br>';
        curl_close($ch);
    }
}


