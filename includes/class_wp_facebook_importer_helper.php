<?php
namespace WPFacebook\Importer;

use DateTime;
use DateTimeZone;
use Exception;
use Wp_Facebook_Importer;
use stdClass;


/**
 *  The helper plugin class.
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
defined( 'ABSPATH' ) or die();

class WP_Facebook_Importer_Helper
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

    /**
     * @param string $basename
     * @param string $version
     * @param Wp_Facebook_Importer $main
     */
    public function __construct(string $basename, string $version, Wp_Facebook_Importer $main ) {

        $this->basename   = $basename;
        $this->version    = $version;
        $this->main       = $main;
    }

    /**
     * @throws Exception
     */
    public function getRandomString(): string
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(16);
            $str = bin2hex($bytes);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(16);
            $str = bin2hex($bytes);
        } else {
            $str = md5(uniqid('post_selector_rand', true));
        }

        return $str;
    }

    /**
     * @param int $passwordlength
     * @param int $numNonAlpha
     * @param int $numNumberChars
     * @param bool $useCapitalLetter
     * @return string
     */
    public function getGenerateRandomId(int $passwordlength = 12, int $numNonAlpha = 1, int $numNumberChars = 4, bool $useCapitalLetter = true): string
    {
        $numberChars = '123456789';
        //$specialChars = '!$&?*-:.,+@_';
        $specialChars = '!$%&=?*-;.,+~@_';
        $secureChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $stack = $secureChars;
        if ($useCapitalLetter == true) {
            $stack .= strtoupper($secureChars);
        }
        $count = $passwordlength - $numNonAlpha - $numNumberChars;
        $temp = str_shuffle($stack);
        $stack = substr($temp, 0, $count);
        if ($numNonAlpha > 0) {
            $temp = str_shuffle($specialChars);
            $stack .= substr($temp, 0, $numNonAlpha);
        }
        if ($numNumberChars > 0) {
            $temp = str_shuffle($numberChars);
            $stack .= substr($temp, 0, $numNumberChars);
        }

        return str_shuffle($stack);
    }

    public function facebook_importer_user_roles_select(): array {

        return [
            'read'           => esc_html__( 'Subscriber', 'wp-facebook-importer' ),
            'edit_posts'     => esc_html__( 'Contributor', 'wp-facebook-importer' ),
            'publish_posts'  => esc_html__( 'Author', 'wp-facebook-importer' ),
            'publish_pages'  => esc_html__( 'Editor', 'wp-facebook-importer' ),
            'manage_options' => esc_html__( 'Administrator', 'wp-facebook-importer' )
        ];
    }

    public function FileSizeConvert(float $bytes): string
    {
        $result = '';
        $bytes = floatval($bytes);
        $arBytes = array(
            0 => array("UNIT" => "TB", "VALUE" => pow(1024, 4)),
            1 => array("UNIT" => "GB", "VALUE" => pow(1024, 3)),
            2 => array("UNIT" => "MB", "VALUE" => pow(1024, 2)),
            3 => array("UNIT" => "KB", "VALUE" => 1024),
            4 => array("UNIT" => "B", "VALUE" => 1),
        );

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem["VALUE"]) {
                $result = $bytes / $arItem["VALUE"];
                $result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
                break;
            }
        }
        return $result;
    }

    /**
     * @param $array
     *
     * @return object
     */
    final public function ArrayToObject($array): object
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::ArrayToObject($value);
            }
        }

        return (object)$array;
    }

    /**
     * @param $object
     * @return array
     */
    final public function ObjectToArray($object):array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * @param $name
     * @param bool $base64
     * @param bool $data
     *
     * @return string
     */
    public function svg_icons($name, bool $base64 = true, bool $data = true): string {
        $icon = '';
        switch ($name){
            case 'facebook':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="wp-importer-facebook" viewBox="0 0 16 16">
                         <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
                          </svg>';
                break;
            case 'layer':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="ps2-icon" viewBox="0 0 16 16">
  						  <path d="M8.235 1.559a.5.5 0 0 0-.47 0l-7.5 4a.5.5 0 0 0 0 .882L3.188 8 .264 9.559a.5.5 0 0 0 0 .882l7.5 4a.5.5 0 0 0 .47 0l7.5-4a.5.5 0 0 0 0-.882L12.813 8l2.922-1.559a.5.5 0 0 0 0-.882l-7.5-4zM8 9.433 1.562 6 8 2.567 14.438 6 8 9.433z"/>
						 </svg>';
                break;
            case'cast':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="er-cast" viewBox="0 0 16 16">
                         <path d="m7.646 9.354-3.792 3.792a.5.5 0 0 0 .353.854h7.586a.5.5 0 0 0 .354-.854L8.354 9.354a.5.5 0 0 0-.708 0z"/>
                         <path d="M11.414 11H14.5a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.5-.5h-13a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h3.086l-1 1H1.5A1.5 1.5 0 0 1 0 10.5v-7A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v7a1.5 1.5 0 0 1-1.5 1.5h-2.086l-1-1z"/>
                         </svg>';
                break;
            case'square':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="er-chat-square-text" viewBox="0 0 16 16">
                         <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                         <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                          </svg>';
                break;
            case 'cast2':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="er-cast" viewBox="0 0 16 16">
                          <path d="m7.646 9.354-3.792 3.792a.5.5 0 0 0 .353.854h7.586a.5.5 0 0 0 .354-.854L8.354 9.354a.5.5 0 0 0-.708 0z"/>
                          <path d="M11.414 11H14.5a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.5-.5h-13a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h3.086l-1 1H1.5A1.5 1.5 0 0 1 0 10.5v-7A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v7a1.5 1.5 0 0 1-1.5 1.5h-2.086l-1-1z"/>
                          </svg>';
                break;
        }
        if($base64){
            if ($data){
                return 'data:image/svg+xml;base64,'. base64_encode($icon);
            }
            return base64_encode($icon);
        }
        return $icon;
    }

    /**
     * @param $postArr
     * @param $value
     * @param $order
     *
     * @return array|mixed
     */
    public function order_by_args_string($postArr,$value, $order) {
        switch ($order){
            case'1':
                usort($postArr, fn ($a, $b) => strcasecmp($a[$value] , $b[$value]));
                return  array_reverse($postArr);
            case '2':
                usort($postArr, fn ($a, $b) => strcasecmp($a[$value] , $b[$value]));
                break;
        }
        return $postArr;
    }

    /**
     * @param $postArr
     * @param $value
     * @param $order
     *
     * @return array|mixed
     */
    public function order_by_args($postArr, $value, $order)
    {
        switch ($order) {
            case'1':
                usort($postArr, fn($a, $b) => $a[$value] - $b[$value]);
                return array_reverse($postArr);
            case '2':
                usort($postArr, fn($a, $b) => $a[$value] - $b[$value]);
                break;
        }

        return $postArr;
    }

    /**
     * @param $string
     * @return string
     */
    public function cleanWhitespace($string): string
    {
        return trim( preg_replace('/\s+/', '', $string) );
    }

    /**
     * Returns the time in seconds until a specified cron job is scheduled.
     *
     *@param string $cron_name The name of the cron job
     *@return int|bool The time in seconds until the cron job is scheduled. False if
     *it could not be found.
     */
    public function import_get_next_cron_time(string $cron_name ){

        foreach( _get_cron_array() as $timestamp => $crons ){
            if( in_array( $cron_name, array_keys( $crons ) ) ){
                return $timestamp - time();
            }
        }
        return false;
    }

    /**
     * @param DateTime $datetime
     * @return string
     * @throws Exception
     */
    public function convert_datetime(DateTime $datetime): string
    {
        get_option('timezone_string') ? $timezone = get_option('timezone_string') : $timezone = 'Europe/Berlin';
        $date = new DateTime($datetime->format('Y-m-d H:i:s'));
        $date->setTimeZone(new DateTimeZone($timezone));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @throws Exception
     */
    public function convert_event_time(DateTime $datetime): string
    {
        $date = new DateTime($datetime->format('Y-m-d H:i:s'));
        return $date->format('Y-m-d H:i:s');
    }

    public function get_admin_id(): int
    {
        $user_roles = get_users();
        foreach ($user_roles as $role) {
            if (in_array('administrator', $role->roles)) {
                return $role->data->ID;
            }
        }
        return 1;
    }
}