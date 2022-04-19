<?php

namespace WPFacebook\Importer;

use Wp_Facebook_Importer;
use stdClass;

class WP_Facebook_Importer_Database
{


    private bool $force_delete = true;

    /**
     * The current version of the DB-Version.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $db_version The current version of the database Version.
     */
    private string $db_version;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;


    use WP_Facebook_Importer_Defaults;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Facebook_Importer $main The main class.
     */
    private Wp_Facebook_Importer $main;

    public function __construct(string $plugin_dir, string $db_version, Wp_Facebook_Importer $main)
    {
        $this->db_version = $db_version;
        $this->main = $main;
        $this->basename = $plugin_dir;
    }

    public function facebook_importer_check_jal_install()
    {
        if (get_option($this->basename . '/jal_db_version') != $this->db_version) {
            update_option($this->basename . '/jal_db_version', $this->db_version);
            $this->facebook_importer_jal_install();
            $this->hupa_set_plugin_default_settings('');
        }
    }

    /**
     * @param $args
     */
    public function hupa_set_plugin_default_settings($args)
    {
        $settings = $this->get_api_settings(false);
        if (!$settings->status) {
            global $wpdb;
            $table = $wpdb->prefix . $this->table_api_settings;
            $wpdb->insert(
                $table,
                array(
                    'id' => $this->main->get_settings_id(),
                    'sync_interval' => 'daily'
                ),
                array('%s', '%s')
            );
        }
    }

    /**
     * @param $args
     * @return object
     */
    public function get_api_settings($args): object
    {
        $record = new stdClass();
        $record->status = false;
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_settings;
        $results = $wpdb->get_row("SELECT * FROM {$table} {$args} ");
        if (!$results) {
            return $record;
        }
        $record->record = $results;
        $record->status = true;
        return $record;
    }

    /**
     * @param $record
     */
    public function plugin_update_settings($record): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_settings;
        $wpdb->update(
            $table,
            array(
                $record->column => $record->content,
            ),
            array('id' => $this->main->get_settings_id()),
            $record->type,
            array('%s')
        );
    }

    /**
     * @param string $taxonomy
     * @return object
     */
    public function get_custom_terms(string $taxonomy): object
    {
        $return = new  stdClass();
        $return->status = false;
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'parent' => 0,
            'hide_empty' => false,
        ));

        if (!$terms) {
            return $return;
        }
        $return->status = true;
        $return->terms = $terms;
        return $return;
    }

    /**
     * @param int $term_id
     * @return object
     */
    public function get_term_by_term_id(int $term_id): object
    {
        $return = new  stdClass();
        $return->status = false;
        $terms = get_terms(array(
            'taxonomy' => 'wp_facebook_category',
            'parent' => 0,
            'hide_empty' => false,
        ));

        foreach ($terms as $tmp) {
            if ($tmp->term_id == $term_id) {
                $return->term = $tmp;
                $return->status = true;
                return $return;
            }
        }
        return $return;
    }

    /**
     * @param $args
     * @param bool $isFetch
     * @return object
     */
    public function get_facebook_imports($args, bool $isFetch = true): object
    {
        $record = new stdClass();
        $record->status = false;
        $record->count = 0;
        $isFetch ? $fetch = 'get_results' : $fetch = 'get_row';
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_imports;
        $results = $wpdb->$fetch("SELECT *, DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') AS created FROM {$table} {$args} ");
        if (!$results) {
            return $record;
        }

        $isFetch ? $record->count = count($results) : $record->count = 1;
        $record->record = $results;
        $record->status = true;
        return $record;
    }

    /**
     * @param $fb_id
     * @return bool
     */
    public function check_double_post($fb_id): bool
    {

        $posts = get_posts(array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => 1,
            'meta_query' => array(
                array(
                    'key' => '_fb_id',
                    'value' => $fb_id,
                    'compare' => '==',
                )
            )
        ));
        if (isset($posts[0]) && $posts[0]->ID) {
            return true;
        }
        return false;
    }

    /**
     * @param $record
     * @return object
     */
    public function set_facebook_imports($record): object
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_imports;
        $wpdb->insert(
            $table,
            array(
                'aktiv' => (int)$record->aktiv,
                'bezeichnung' => (string)$record->import_name,
                'description' => (string)$record->post_description,
                'max_count' => (int)$record->import_count,
                'user_id' => (string)$record->user_id,
                'user_aktiv' => (int)$record->check_user_id,
                'page_id' => (string)$record->page_id,
                'post_term' => (int)$record->post_term_id,
                'event_term' => (int)$record->event_term_id,
                'post_time_from' => (int)$record->post_time_from,
                'import_no_image' => (int)$record->import_no_image
            ),
            array('%d', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d', '%d', '%d')
        );

        $return = new stdClass();
        if ($wpdb->insert_id) {
            $return->status = true;
            $return->id = $wpdb->insert_id;
            $return->msg = sprintf(__('New import "%s" with the ID: %d created!', 'wp-facebook-importer'), $record->import_name, $wpdb->insert_id);
            return $return;
        }
        $return->status = false;
        $return->msg = sprintf(__('"%s" could not be saved!', 'wp-facebook-importer'), $record->import_named);
        return $return;
    }

    /**
     * @param $record
     */
    public function update_facebook_import($record): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_imports;
        $wpdb->update(
            $table,
            array(
                'bezeichnung' => (string)$record->import_name,
                'description' => (string)$record->post_description,
                'max_count' => (int)$record->import_count,
                'user_id' => (string)$record->user_id,
                'user_aktiv' => (int)$record->check_user_id,
                'page_id' => (string)$record->page_id,
                'post_term' => (int)$record->post_term_id,
                'event_term' => (int)$record->event_term_id,
                'post_time_from' => (int)$record->post_time_from,
                'import_no_image' => (int)$record->import_no_image
            ),
            array('id' => (int)$record->id),
            array(
                '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d', '%d', '%d'
            ),
            array('%d')
        );
    }

    public function update_last_sync_import($record)
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_imports;
        $wpdb->update(
            $table,
            array(
                'last_sync' => (string)$record->last_sync,
                'from_sync' => (string)$record->from_sync,
                'until_sync' => (int)$record->until_sync,
            ),
            array('id' => (int)$record->id),
            array(
                '%s', '%s', '%s'
            ),
            array('%d')
        );
    }

    /**
     * @param $record
     */
    public function update_imports_inputs($record): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_imports;
        $wpdb->update(
            $table,
            array(
                $record->column => $record->content,
            ),
            array('id' => (int)$record->id),
            array(
                $record->type
            ),
            array('%d')
        );
    }

    /**
     * @param int $id
     */
    public function delete_imports_input(int $id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_imports;
        $wpdb->delete(
            $table,
            array(
                'id' => $id),
            array('%d')
        );
    }

    /**
     * @param int $id
     * @param string $type
     */
    public function delete_facebook_posts(int $id, string $type): void
    {
        $posts = get_posts(array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_post_type',
                    'value' => $type,
                    'compare' => '=='
                ),
                array(
                    'key' => '_import_id',
                    'value' => $id,
                    'compare' => '==',
                )
            )
        ));

        if ($posts) {
            foreach ($posts as $post) {
                if ($thumbnail_id = get_post_thumbnail_id($post->ID)) {
                    wp_delete_attachment($thumbnail_id, $this->force_delete);
                }
                wp_delete_post($post->ID, $this->force_delete);
            }
        }

        $args = sprintf('WHERE id=%d', $id);
        $imports = $this->get_facebook_imports($args, false);
        if ($imports->status) {
            $delSync = [
                'last_sync' => '',
                'from_sync' => '',
                'until_sync' => '',
                'id' => $id
            ];
            $this->update_last_sync_import((object)$delSync);
        }
    }

    /**
     * @param int $id
     */
    public function delete_facebook_events(int $id): void
    {
        $posts = get_posts(array(
                'post_type' => 'wp_facebook_posts',
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_post_type',
                        'value' => 'event',
                        'compare' => '=='
                    ),
                    array(
                        'key' => '_import_id',
                        'value' => $id,
                        'compare' => '==',
                    )
                )
            )
        );
        if ($posts) {
            foreach ($posts as $post) {
                $import_type = get_post_meta($post->ID, '_post_type', true);
                if ($import_type == 'post') {
                    if ($this->force_delete) {
                        if ($thumbnail_id = get_post_thumbnail_id($post->ID)) {
                            wp_delete_attachment($thumbnail_id, $this->force_delete);
                        }
                    }
                }
                wp_delete_post($post->ID, $this->force_delete);
            }
        }
    }


    /**
     * @param $fb_id
     * @param $post_type
     * @return void
     */
    public function delete_post_by_fb_id($fb_id, $post_type)
    {
        $posts = get_posts(array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => 1,
            'meta_query' => array(
                array(
                    'key' => '_fb_id',
                    'value' => $fb_id,
                    'compare' => '==',
                )
            )
        ));

        if ($posts) {
            foreach ($posts as $post) {
                $import_type = get_post_meta($post->ID, '_post_type', true);
                if ($import_type == $post_type) {
                    if ($this->force_delete) {
                        $thumbnail_id = get_post_thumbnail_id($post->ID);
                        wp_delete_attachment($thumbnail_id, $this->force_delete);
                    }
                }
                wp_delete_post($post->ID, $this->force_delete);
            }
        }
    }


    /**
     * @param $post_type
     * @return object
     */
    public function get_wp_facebook_posts($post_type): object
    {
        $return = new stdClass();
        $return->status = false;
        $args = array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_post_type',
                    'value' => $post_type,
                    'compare' => '==',
                )
            )
        );
        $posts = get_posts($args);

        if (!$posts) {
            return $return;
        }

        $return->status = true;
        $return->record = $posts;
        return $return;
    }

    /**
     * @param int $importId
     * @param string $post_type
     * @param bool $getPosts
     * @return object
     */
    public function get_wp_facebook_import_count(int $importId, string $post_type, bool $getPosts = false): object
    {
        $return = new stdClass();
        $return->status = false;
        $return->count = 0;
        $args = array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_post_type',
                    'value' => $post_type,
                    'compare' => '=='
                ),
                array(
                    'key' => '_import_id',
                    'value' => $importId,
                    'compare' => '==',
                )
            )
        );
        $posts = get_posts($args);

        if (!$posts) {
            return $return;
        }

        if ($getPosts) {
            $return->record = $posts;
        }
        $return->status = true;
        $return->count = count($posts);
        return $return;
    }

    /**
     * @param $import_id
     * @param string $post_type
     * @return object
     */
    public function delete_old_wp_facebook_posts($import_id, string $post_type = 'post'): object
    {

        $return = new stdClass();
        $return->status = false;
        $args = sprintf('WHERE id=%d AND max_count>0', $import_id);
        $import = $this->get_facebook_imports($args, false);
        if (!$import->status) {
            return $return;
        }

        $maxCount = $import->record->max_count;

        $args = array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_post_type',
                    'value' => $post_type,
                    'compare' => '=='
                ),
                array(
                    'key' => '_import_id',
                    'value' => $import_id,
                    'compare' => '=='
                )
            )
        );

        $posts = get_posts($args);
        if (!$posts) {
            return $return;
        }

        if (count($posts) <= $maxCount) {
            return $return;
        }

        $i = 1;
        foreach ($posts as $post) {
            if ($i > $maxCount) {
                $thumbnail_id = get_post_thumbnail_id($post->ID);
                wp_delete_attachment($thumbnail_id, $this->force_delete);
                wp_delete_post($post->ID, $this->force_delete);
            }
            $i++;
        }

        $return->status = true;
        return $return;
    }

    /**
     * @param string $type
     * @param string $method
     * @return array
     */
    public function check_db_events_by_args(string $type, string $method = ''): array
    {
        $args = array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_post_type',
                    'value' => $type,
                    'compare' => '==',
                )
            )
        );
        $posts = get_posts($args);
        $idArr = [];
        foreach ($posts as $post) {
            if ($method == 'older_now') {
                $dbStartTime = get_post_meta($post->ID, '_fb_end_time', true);
                $dbStartTime = strtotime($dbStartTime);
                if ($dbStartTime < current_time('timestamp')) {
                    $idArr[] = $post->ID;
                }
            }
        }
        return $idArr;
    }

    /**
     * @param $fb_id
     * @return object
     */
    public function get_wp_facebook_post_by_fb_id($fb_id): object
    {
        $return = new stdClass();
        $return->status = false;
        $args = array(
            'post_type' => 'wp_facebook_posts',
            'numberposts' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_fb_id',
                    'value' => $fb_id,
                    'compare' => '==',
                )
            )
        );
        $posts = get_posts($args);

        if (!$posts) {
            return $return;
        }

        $return->status = true;
        $return->record = $posts;
        return $return;
    }


    /**
     * GET SETTINGS and all Imports
     *
     * @param string $importId
     * @return object
     */
    public function get_import_daten(string $importId = ''): object
    {
        $return = new stdClass();
        $args = sprintf('WHERE id="%s"', $this->main->get_settings_id());
        $settings = $this->get_api_settings($args);
        if ($settings->status) {
            $s = $settings->record;
            $return->cron_status = true;
            $return->cron_aktiv = $s->cron_aktiv;
            $return->cron_sync_count = str_pad($s->max_sync, 2, 0);
            $return->cron_last_sync = $s->last_api_sync;
        } else {
            $return->cron_status = false;
        }

        $import_record = [];
        if ($importId) {
            $args = sprintf('WHERE id=%d AND aktiv=1', $importId);
            $isFetch = false;
        } else {
            $args = 'WHERE aktiv=1';
            $isFetch = true;
        }
        $import = $this->get_facebook_imports($args, $isFetch);

        if ($import->status) {
            $import->record->cron_aktiv = $return->cron_aktiv;
            $import->record->cron_sync_count = $return->cron_sync_count;
            $import->record->cron_status = $return->cron_status;
            if ($importId) {
                return $import->record;
            }
            foreach ($import->record as $tmp) {
                $import_record[] = $tmp;
            }
        }

        $return->imports = $import_record;
        return $return;
    }

    public function update_last_sync($importId, $time = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_imports;
        //$time ? $setTime = strtotime($time) : $setTime = '';
        $wpdb->update(
            $table,
            array(
                'last_sync' => $time
            ),
            array('id' => $importId),
            array('%s'),
            array('%d')
        );
    }


    /**
     * @param $args
     * @param bool $isFetch
     * @return object
     */
    public function get_plugin_syn_log($args, bool $isFetch = true): object
    {
        $record = new stdClass();
        $record->status = false;
        $record->count = 0;
        $isFetch ? $fetch = 'get_results' : $fetch = 'get_row';
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_sync_log;
        $table_import = $wpdb->prefix . $this->table_api_imports;
        $results = $wpdb->$fetch("SELECT sl.*,
         DATE_FORMAT(sl.created_at, '%d.%m.%Y %H:%i') AS created,
         im.bezeichnung,im.post_term,im.event_term   
        FROM $table sl 
        LEFT JOIN $table_import im ON sl.import_id = im.id
        {$args} ");
        if (!$results) {
            return $record;
        }
        $isFetch ? $record->count = count($results) : $record->count = 1;
        $record->record = $results;
        $record->status = true;
        return $record;
    }

    /**
     * @param $record
     */
    public function set_plugin_syn_log($record)
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_sync_log;
        $wpdb->insert(
            $table,
            array(
                'import_id' => $record->import_id,
                'start_post' => $record->start_post,
                'end_post' => $record->end_post,
                'start_event' => $record->start_event,
                'end_event' => $record->end_event,
                'post_status' => $record->post_status,
                'event_status' => $record->event_status,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
        );

    }

    /**
     * @param int $id
     */
    public function delete_plugin_syn_log(int $id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . $this->table_api_sync_log;
        $wpdb->delete(
            $table,
            array(
                'id' => $id),
            array('%d')
        );
    }

    public function facebook_importer_jal_install()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        if (!$this->if_fb_import_table_exists($this->table_api_settings)) {
            $table_name = $wpdb->prefix . $this->table_api_settings;
            $sql = "CREATE TABLE $table_name (
            `id` varchar(12) NOT NULL,
            `app_id` varchar(255) NOT NULL,
            `app_secret` varchar (255) NOT NULL,
            `access_token` TINYTEXT NULL,
            `cron_aktiv` tinyint(1) NOT NULL DEFAULT 0,
            `max_sync` tinyint(1) NOT NULL DEFAULT 2,
            `sync_interval` varchar(24) NOT NULL DEFAULT 'daily', 
            `last_api_sync` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)) 
            $charset_collate;";
            dbDelta($sql);
        }

        if (!$this->if_fb_import_table_exists($this->table_api_imports)) {
            $table_name = $wpdb->prefix . $this->table_api_imports;
            $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aktiv` tinyint(1) NOT NULL,
            `bezeichnung` varchar(255) NOT NULL UNIQUE,
            `description` tinytext NULL,
            `max_count` int(11) NOT NULL DEFAULT 100,
            `user_id` varchar(255) NOT NULL DEFAULT 'me',
            `user_aktiv` tinyint(1) NOT NULL DEFAULT 0,
            `page_id` varchar(255) NULL,
            `post_term` int(9) NOT NULL,
            `post_time_from` int(2) NULL DEFAULT 0,
            `import_no_image` int(1) NOT NULL DEFAULT 1,
            `event_term` int(6) NOT NULL,
            `last_sync` varchar (255) NULL,
            `from_sync` varchar (255) NULL,
            `until_sync` varchar (255) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)) 
            $charset_collate;";
            dbDelta($sql);
        }

        if (!$this->if_fb_import_table_exists($this->table_api_sync_log)) {
            $table_name = $wpdb->prefix . $this->table_api_sync_log;
            $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `import_id` int(11) NOT NULL,
            `start_post` varchar (14)NOT NULL DEFAULT '',
            `end_post` varchar (14)NOT NULL DEFAULT '',
            `start_event` varchar (14)NOT NULL DEFAULT '',
            `end_event` varchar (14)NOT NULL DEFAULT '',
            `post_status` tinyint(1) NOT NULL DEFAULT 1,
            `event_status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)) 
            $charset_collate;";
            dbDelta($sql);
        }
    }

    /**
     * @param $table
     * @return bool
     */
    private function if_fb_import_table_exists($table): bool
    {
        global $wpdb;
        $checkTable = $wpdb->prefix . $table;
        $ifTable = $wpdb->get_var("SHOW TABLES LIKE '{$checkTable}'");
        if ($ifTable) {
            return true;
        }
        return false;
    }
}
