<?php
namespace WPFacebook\Importer;
use stdClass;
use Wp_Facebook_Importer;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Plugin ENDPOINT
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 */
defined('ABSPATH') or die();

class Facebook_Importer_Rest_Endpoint extends WP_REST_Controller
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
     * The FB-API Settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $settings The FB-API Settings for this Plugin
     */
    private object $settings;

    /**
     * The ID of Cronjob.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $cronjob_id The ID of this plugin.
     */
    private string $cronjob_id;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Facebook_Importer $main The main class.
     */
    private Wp_Facebook_Importer $main;

    public function __construct(string $plugin_name, Wp_Facebook_Importer $main, $cronId ) {
        $this->basename = $plugin_name;
        $this->main = $main;
        $this->cronjob_id = $cronId;
        $this->settings = (object) [];
        $settings = $this->main->get_settings();
        if($settings){
            $this->settings = $settings;
        }
    }

    /**
     * Register the routes for the objects of the controller.
     */

    public function register_routes()
    {
        $version = '2';
        $namespace = 'fb-importer/v' . $version;
        $base = '/';

        @register_rest_route(
            $namespace,
            $base,
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_registered_items'),
                'permission_callback' => array($this, 'permissions_check')
            )
        );

        @register_rest_route(
            $namespace,
            $base . '(?P<method>[^/]+)/(?P<token>[\S^/]+)',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'fb_importer_api_rest_get_method_endpoint'),
                'permission_callback' => array($this, 'permissions_check')
            )
        );
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_registered_items(WP_REST_Request $request)
    {
        $data = [];

        return rest_ensure_response($data);

    }

    /**
     * Get one item from the collection.
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function fb_importer_api_rest_get_method_endpoint(WP_REST_Request $request) {

        $method = $request->get_param('method');
        $token = $request->get_param('token');

        if(strtoupper(sha1($this->cronjob_id)) !== $token){
            return new WP_Error(404, ' unknown - ID failed');
        }

        if (!$method) {
            return new WP_Error(404, ' unknown - Method failed');
        }

        $response = $this->make_api_job($method);

        $item_data = new WP_REST_Response($response, 200);
        $item_data->add_link(
            'self',
            rest_url('fb-importer/v2/' . $method.'/'.$token)
        );

        return $item_data;
    }

    private function make_api_job($method):array
    {
        $response = [];
        switch ($method){
            case 'cron':
              //  do_action('importer_fb_api_plugin_sync');
                    $response = [
                        'test' => 'HELLO'
                    ];
                break;
        }

        return $response;
    }

    /**
     * Check if a given request has access.
     *
     * @return string
     */
    public function permissions_check(): string
    {
        return '__return_true';
    }
}