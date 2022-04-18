<?php

use WPFacebook\Importer\Import_Curl_Cronjob_Exec;

/**
 *
 *   Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 *   Copyright 2021, Jens Wiecker
 *   License: Commercial - goto https://www.hummelt-werbeagentur.de/
 *   https://www.hummelt-werbeagentur.de/
 *
 */
class FB_Importer_Api_Cronjob_Exec
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
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Facebook_Importer $main The main class.
     */
    private Wp_Facebook_Importer $main;
    private static $instance;


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
    }


    public function fb_importer_plugin_synchronisation(): void
    {
        Import_Curl_Cronjob_Exec::instance($this->basename, $this->main);
    }
}
