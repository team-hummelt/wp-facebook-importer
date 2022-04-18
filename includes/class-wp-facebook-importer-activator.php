<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Facebook_Importer
 * @subpackage Wp_Facebook_Importer/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Facebook_Importer_Activator
{
    private static array $customCaps = array(
        [ 'singular' => 'facebook', 'plural' => 'facebooks' ],
    );

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        self::add_admin_capabilities();
        self::register_facebook_importer_post_type();
        self::register_facebook_importer_taxonomies();

        //self::add_author_capabilities();

       flush_rewrite_rules();
    }

    /**
     * Register Custom Post-Type FB Importer.
     *
     * @since    1.0.0
     */
    public static function register_facebook_importer_post_type()
    {
        register_post_type(
            'wp_facebook_posts',
            array(
                'labels' => array(
                    'name' => __('Facebook Post', 'wp-facebook-importer'),
                    'singular_name' => __('Facebook Posts', 'wp-facebook-importer'),
                    'edit_item' => __('Edit Facebook Post', 'wp-facebook-importer'),
                    'items_list_navigation' => __('Facebook Post navigation', 'wp-facebook-importer'),
                    'add_new_item' => __('Add new post', 'wp-facebook-importer'),
                    'archives' => __('Facebook Posts Archives', 'wp-facebook-importer'),
                ),
                'public' => true,
                'publicly_queryable' => true,
                'show_in_rest' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'has_archive' => true,
                'query_var' => true,
                'show_in_nav_menus' => true,
                'exclude_from_search' => false,
                'hierarchical' => true,
              //  'capability_type' => 'facebook, facebooks',
            /*    'capabilities' => array(
                    'edit_post' => 'edit_facebook',
                    'edit_posts' => 'edit_facebooks',
                    'edit_others_posts' => 'edit_other_facebooks',
                    'publish_posts' => 'publish_facebooks',
                    'read_post' => 'read_facebook',
                    'read_private_posts' => 'read_private_facebooks',
                    'delete_post' => 'delete_facebook'
                ),*/
            //    'map_meta_cap'    => true, //map_meta_cap must be true
                'menu_icon' => self::get_svg_icons('facebook'),
                'menu_position' => 3,
                'supports' => array(
                    'title', 'excerpt', 'page-attributes', 'author', 'editor', 'thumbnail', 'comments', 'custom-fields'
                ),
                'taxonomies' => array('wp_facebook_category'),
            )
        );
    }

    /**
     * Register Custom Taxonomies for FB-Importer Post-Type.
     *
     * @since    1.0.0
     */
    public static function register_facebook_importer_taxonomies()
    {
        $labels = array(
            'name' => __('Facebook Categories', 'wp-facebook-importer'),
            'singular_name' => __('Facebook Category', 'wp-facebook-importer'),
            'search_items' => __('Search Facebook Categories', 'wp-facebook-importer'),
            'all_items' => __('All Facebook Categories', 'wp-facebook-importer'),
            'parent_item' => __('Parent Facebook Category', 'wp-facebook-importer'),
            'parent_item_colon' => __('Parent Facebook Category:', 'wp-facebook-importer'),
            'edit_item' => __('Edit Facebook Category', 'wp-facebook-importer'),
            'update_item' => __('Update Facebook Category', 'wp-facebook-importer'),
            'add_new_item' => __('Add New Facebook Category', 'wp-facebook-importer'),
            'new_item_name' => __('New Facebook Category', 'wp-facebook-importer'),
            'menu_name' => __('Facebook Categories', 'wp-facebook-importer'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'show_ui' => true,
            'sort' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'args' => array('orderby' => 'term_order'),
            'rewrite' => array('slug' => 'wp_facebook_category'),
            'show_admin_column' => true
        );
        register_taxonomy('wp_facebook_category', array('wp_facebook_posts'), $args);

        //Kategorie erstellen (STANDARD CATEGORY)
        if (!term_exists('Facebook Allgemein', 'wp_facebook_category')) {
            wp_insert_term(
                'Facebook Allgemein',
                'wp_facebook_category',
                array(
                    'description' => __('Standard category for posts', 'wp-facebook-importer'),
                    'slug' => 'wp-facebook-posts'
                )
            );
        }

        //Kategorie erstellen (STANDARD CATEGORY EVENTS)
        if (!term_exists('Facebook Veranstaltungen', 'wp_facebook_category')) {
            wp_insert_term(
                'Facebook Veranstaltungen',
                'wp_facebook_category',
                array(
                    'description' => __('Standard category for events', 'wp-facebook-importer'),
                    'slug' => 'wp-facebook-veranstaltungen'
                )
            );
        }
    }

    /**
     * Add custom capabilities for admin
     */
    public static function add_admin_capabilities() {

        $role = get_role( 'administrator' );

        foreach( self::$customCaps as $cap ){

            $singular = $cap['singular'];
            $plural = $cap['plural'];

            $role->add_cap( "edit_{$singular}" );
            $role->add_cap( "edit_{$plural}" );
            $role->add_cap( "edit_others_{$plural}" );
            $role->add_cap( "publish_{$plural}" );
            $role->add_cap( "read_{$singular}" );
            $role->add_cap( "read_private_{$plural}" );
            $role->add_cap( "delete_{$singular}" );
            $role->add_cap( "delete_{$plural}" );
            $role->add_cap( "delete_private_{$plural}" );
            $role->add_cap( "delete_others_{$plural}" );
            $role->add_cap( "edit_published_{$plural}" );
            $role->add_cap( "edit_private_{$plural}" );
            $role->add_cap( "delete_published_{$plural}" );

        }
    }

    /**
     * Add custom capabilities for author
     */
    public static function add_author_capabilities() {
        $role = get_role( 'author' );
        foreach( self::$customCaps as $cap ){
            $singular = $cap['singular'];
            $plural = $cap['plural'];
            $role->add_cap( "edit_others_{$plural}" );
            $role->add_cap( "edit_{$singular}" );
            $role->add_cap( "edit_{$plural}" );
            $role->add_cap( "edit_published_{$plural}" );
            $role->add_cap( "publish_{$plural}" );
            $role->add_cap( "delete_others_{$plural}" );
            $role->add_cap( "delete_{$singular}" );
            $role->add_cap( "delete_{$plural}" );
        }
    }

    /**
     * Add custom capabilities for editor
     */
    public static function add_editor_capabilities() {
        $role = get_role( 'editor' );
        foreach( self::$customCaps as $cap ){
            $singular = $cap['singular'];
            $plural = $cap['plural'];
            $role->add_cap( "edit_others_{$plural}" );
            $role->add_cap( "edit_{$singular}" );
            $role->add_cap( "edit_{$plural}" );
            $role->add_cap( "edit_published_{$plural}" );
        }
    }

    /**
     * @param $name
     *
     * @return string
     */
    private static function get_svg_icons($name): string {
        $icon = '';
        switch ($name){
            case'facebook':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16">
                         <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
                         </svg>';
                break;
            case'sign-split':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="bi bi-signpost-split" viewBox="0 0 16 16">
                         <path d="M7 7V1.414a1 1 0 0 1 2 0V2h5a1 1 0 0 1 .8.4l.975 1.3a.5.5 0 0 1 0 .6L14.8 5.6a1 1 0 0 1-.8.4H9v10H7v-5H2a1 1 0 0 1-.8-.4L.225 9.3a.5.5 0 0 1 0-.6L1.2 7.4A1 1 0 0 1 2 7h5zm1 3V8H2l-.75 1L2 10h6zm0-5h6l.75-1L14 3H8v2z"/>
                         </svg>';
                break;
            case'square':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="er-chat-square-text" viewBox="0 0 16 16">
                         <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                         <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                          </svg>';
                break;
            case 'cast':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cast" viewBox="0 0 16 16">
                          <path d="m7.646 9.354-3.792 3.792a.5.5 0 0 0 .353.854h7.586a.5.5 0 0 0 .354-.854L8.354 9.354a.5.5 0 0 0-.708 0z"/>
                          <path d="M11.414 11H14.5a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.5-.5h-13a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h3.086l-1 1H1.5A1.5 1.5 0 0 1 0 10.5v-7A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v7a1.5 1.5 0 0 1-1.5 1.5h-2.086l-1-1z"/>
                          </svg>';
                break;
            default:
        }
        return 'data:image/svg+xml;base64,'. base64_encode($icon);

    }
}
