<?php

if ( !defined('ABSPATH') )
    die('-1');

define('SWIFPOSTS_DOMAIN', 'swiftposts');
define('SWIFPOSTS_SLUG', basename(__DIR__));
define('SWIFPOSTS_URI', plugin_dir_url(__FILE__));

class Swifposts_Loader{

    private $swifposts;
    private $exclude_ids = array();


    function __construct(){

        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'), 1);
        add_action('admin_menu', array($this, 'admin_menu') );
    }

    function is_supported(){
        return class_exists('SwiftypePlugin');
    }

    function is_plugin_page(){
        return isset($_GET['page']) && $_GET['page'] == 'swiftype-custom-posts';
    }

    function init(){
        if(!$this->is_supported())
            return;


    }

    function admin_init(){
        if(!$this->is_supported())
            return;

        if($this->is_plugin_page()){
            $this->process_settings();
            add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
        }else{

            $this->load_settings();

            add_action( 'wp_ajax_index_batch_of_posts', array( $this, 'before_async_index_batch_of_posts' ), 9 );
            add_filter('swiftype_document_builder', array($this, 'document_builder'), 10, 2);
        }
    }

    function admin_menu(){
        add_options_page( __(Swifposts_Config::PAGE_TITLE, SWIFPOSTS_DOMAIN),
            __(Swifposts_Config::MENU_NAME, SWIFPOSTS_DOMAIN),
            'manage_options',
            SWIFPOSTS_SLUG,
            array($this, 'settings_page'));

    }

    function register_admin_scripts(){
        if(!$this->is_plugin_page())
            return;

        wp_enqueue_style('swifpost-css', SWIFPOSTS_URI.'/css/admin.css');
    }

    function settings_page(){
        global $wpdb, $page_message;

        if(!$this->is_supported()){
            echo "<div class='wrap'><h3>You have no Swiftype Search plugin installed.</h3></div>";
            return;
        }

        $base_url = self::get_setting_url();

        $view = isset($_GET['view'])?$_GET['view']:'';

        switch($view){
            case 'edit':
                require_once 'templates/edit.php';
                break;
            default:
                require_once 'templates/lists.php';
        }

    }

    function process_settings(){
        global $page_error, $page_message, $wpdb;

        if(!$this->is_plugin_page()){
            return;
        }

        $page_error = new WP_Error();
        $page_message = new WP_Error();

        if( isset($_GET['swiffield-add-nonce']) && wp_verify_nonce($_GET['swiffield-add-nonce'], 'swiffield-add-action')){
            $id = $_GET['id'];
            if($id){
                $sql = $wpdb->prepare("INSERT INTO {$wpdb->prefix}swifposts_custom_fields(swifpost_id, field_key, data_type) values(%s, '', 'string')", $id);
                $wpdb->query($sql);

                $edit_url = add_query_arg(array('view'=>'edit', 'id'=>$id), self::get_setting_url());
                wp_safe_redirect($edit_url);
                exit;
            }
        }elseif( isset($_GET['swiffield-delete-nonce']) && wp_verify_nonce($_GET['swiffield-delete-nonce'], 'swiffield-delete-action')){
            $id = $_GET['id'];
            $field_id = $_GET['field-id'];
            if($id && $field_id){
                $sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}swifposts_custom_fields where swifpost_id = %d and id = %d", $id, $field_id);
                $wpdb->query($sql);

                $edit_url = add_query_arg(array('view'=>'edit', 'id'=>$id), self::get_setting_url());
                wp_safe_redirect($edit_url);
                exit;
            }
        }elseif( isset($_POST['swiffield-nonce']) && wp_verify_nonce($_POST['swiffield-nonce'], 'swiffield-action')){
            $fields = $_POST['field'];
            if($fields && is_array($fields)){
                foreach($fields as $field_id => $field){
                    $field_key = $field['field-key']?$field['field-key']:$field['field-key-new'];
                    $data_type = $field['data-type'];

                    $sql = $wpdb->prepare("Update {$wpdb->prefix}swifposts_custom_fields set field_key = %s, data_type = %s where id = %d", $field_key, $data_type, $field_id);
                    $wpdb->query($sql);
                }

                $page_message->add('message', 'Custom fields have been saved');
            }

        }elseif( isset($_POST['swifpost-nonce']) && wp_verify_nonce($_POST['swifpost-nonce'], 'swifpost-action')){
            if(empty($_POST['swifpost'])){
                $page_error->add('post_type', __('Please select a post type', SWIFPOSTS_DOMAIN));
            }

            $error_codes = $page_error->get_error_codes();
            if(empty($error_codes)){
                $sql = $wpdb->prepare("INSERT INTO {$wpdb->prefix}swifposts(post_type, status) values(%s, 1)", $_POST['swifpost']);
                $wpdb->query($sql);

                $insert_id = $wpdb->insert_id;
                $edit_url = add_query_arg(array('view'=>'edit', 'id'=>$insert_id), self::get_setting_url());
                wp_safe_redirect($edit_url);
                exit;
            }

        }elseif( isset($_GET['swifpost-delete-nonce']) && wp_verify_nonce($_GET['swifpost-delete-nonce'], 'swifpost-delete-action')){
            $id = $_GET['id'];
            if($id){
                $sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}swifposts where id = %d", $id);
                $wpdb->query($sql);

                $sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}swifposts_custom_fields where swifpost_id = %d", $id);
                $wpdb->query($sql);

                $url = self::get_setting_url();
                wp_safe_redirect($url);
                exit;
            }
        }elseif( isset($_GET['swifpost-enable-nonce']) && wp_verify_nonce($_GET['swifpost-enable-nonce'], 'swifpost-enable-action')){
            $id = $_GET['id'];
            if($id){
                $sql = $wpdb->prepare("UPDATE {$wpdb->prefix}swifposts set status = 1 where id = %d", $id);
                $wpdb->query($sql);

                $url = self::get_setting_url();
                wp_safe_redirect($url);
                exit;
            }
        }elseif( isset($_GET['swifpost-disable-nonce']) && wp_verify_nonce($_GET['swifpost-disable-nonce'], 'swifpost-disable-action')){
            $id = $_GET['id'];
            if($id){
                $sql = $wpdb->prepare("UPDATE {$wpdb->prefix}swifposts set status = 0 where id = %d", $id);
                $wpdb->query($sql);

                $url = self::get_setting_url();
                wp_safe_redirect($url);
                exit;
            }
        }elseif( isset($_POST['swifpost-custom-nonce']) && wp_verify_nonce($_POST['swifpost-custom-nonce'], 'swifpost-custom-action')){
            $id = $_GET['id'];
            if($id){
                $exclude_ids = isset($_POST['exclude-ids'])?$_POST['exclude-ids']:'';
                $custom_title = isset($_POST['custom-title'])?$_POST['custom-title']:'';
                $custom_url = isset($_POST['custom-url'])?$_POST['custom-url']:'';

                $sql = $wpdb->prepare("UPDATE {$wpdb->prefix}swifposts set custom_title = %s, custom_url = %s, exclude_ids = %s where id = %d", $custom_title, $custom_url, $exclude_ids, $id);
                $wpdb->query($sql);

                $page_message->add('custom-setting', 'Custom settings have been saved');
            }
        }
    }

    function load_settings(){
        $results = self::get_swifposts(false, 1);
        $swifposts = array();

        foreach($results as $row){
            $post_type = $row['post_type'];
            $swifposts[$post_type] = $row;

            $custom_fields = self::get_swifposts_custom_fields($row['id']);

            $swifposts[$post_type]['custom_fields'] = $custom_fields;
        }

        $this->swifposts = $swifposts;


    }

    function before_async_index_batch_of_posts(){

        if($this->swifposts){

            $exclude_ids = array();
            foreach($this->swifposts as $setting){
                if($setting['exclude_ids']){
                    $value = explode(',', $setting['exclude_ids']);
                    $value = array_map('trim', $value);
                    $value = array_map('intval', $value);
                    $value = array_filter($value);
                    $value = array_unique($value);

                    $exclude_ids = array_merge($exclude_ids, $value);
                }
            }

            $this->exclude_ids = array_unique($exclude_ids);
        }

        add_action('pre_get_posts', array($this, 'pre_get_posts'));
    }

    function pre_get_posts(WP_Query &$query){
        remove_action('pre_get_posts', array($this, 'pre_get_posts'));

        $query->set('order', 'DESC');
        $query->set('suppress_filters', false);
    }

    function document_builder($document, $post){

        if($this->exclude_ids && in_array($post->ID, $this->exclude_ids)){
            $document = NULL;
        }
        elseif($this->swifposts){
            if(array_key_exists($post->post_type, $this->swifposts)){
                $setting = $this->swifposts[$post->post_type];
                $post_meta = get_post_meta($post->ID);

                if($setting['custom_fields']){

                    foreach($setting['custom_fields'] as $field){
                        $field_key = $field['field_key'];
                        $data_type = $field['data_type'];

                        $value = '';
                        if(isset($post_meta[$field_key])){
                            if(function_exists('get_field')){
                                $value = get_field($field_key, $post->ID);
                            }else{
                                $value = maybe_unserialize($post_meta[$field_key][0]);
                            }
                        }

                        $value = apply_filters('swifposts_custom_field_value', $value, $field_key, $data_type, $post);
                        if($value){
                            $document['fields'][] = array(
                                'name' => $field_key,
                                'type' => $data_type,
                                'value' => $value
                            );
                        }
                    }
                }

                if($setting['custom_title']){
                    $custom_title_setting = $setting['custom_title'];
                    $custom_title = self::parse_value_params($custom_title_setting, $post);
                    $custom_title = apply_filters('swifposts_custom_title_value', $custom_title, $custom_title_setting, $post);

                    if($custom_title){
                        foreach($document['fields'] as &$field){
                            if($field['name'] == 'title'){
                                $field['value'] = $custom_title;
                            }
                        }
                    }
                }

                if($setting['custom_url']){
                    $custom_url_setting = $setting['custom_url'];
                    $custom_url = self::parse_value_params($custom_url_setting, $post);
                    $custom_url = apply_filters('swifposts_custom_url_value', $custom_url, $custom_url_setting, $post);

                    if($custom_url){
                        foreach($document['fields'] as &$field){
                            if($field['name'] == 'url'){
                                $field['value'] = $custom_url;
                            }
                        }
                    }

                }

            }else{
                $document = NULL;
            }
        }

        return $document;
    }


    static function parse_value_params($rule, $post){
        $evaluate = $rule;

        $params = array();
        $matches = false;
        preg_match_all('/\{(.*?)\}/', $rule, $matches);
        if($matches && isset($matches[1])){
            $params = $matches[1];
        }

        if($params){
            $post_meta = get_post_meta($post->ID);

            $params = array_unique($params);

            foreach($params as $param_name){
                $value = '';

                if(strpos($param_name, 'field:') === 0){
                    list($meta, $field_key) = explode(':', $param_name);

                    if($field_key && isset($post_meta[$field_key])){
                        if(function_exists('get_field')){
                            $value = get_field($field_key, $post->ID);
                        }else{
                            $value = maybe_unserialize($post_meta[$field_key][0]);
                        }
                    }
                }elseif(strpos($param_name, 'tax:') === 0){
                    list($meta, $taxonomy) = explode(':', $param_name);
                    if($taxonomy){
                        $terms = wp_get_object_terms($post->ID, $taxonomy, array('fields'=>'names'));
                        $value = implode(', ', $terms);
                    }
                }elseif(strpos($param_name, 'tax_slug:') === 0){
                    list($meta, $taxonomy) = explode(':', $param_name);
                    if($taxonomy){
                        $terms = wp_get_object_terms($post->ID, $taxonomy, array('fields'=>'slugs'));
                        $value = implode(', ', $terms);
                    }
                }else{
                    switch($param_name){
                        case 'ID': $value = $post->ID; break;
                        case 'post_title': $value = $post->post_title; break;
                        case 'post_name': $value = $post->post_name; break;
                        case 'permalink': $value = get_permalink($post); break;
                    }
                }

                $value = apply_filters('swifposts_custom_param_value', $value, $param_name, $post);
                if($value !== false){
                    $evaluate = str_replace("{".$param_name."}", $value, $evaluate);
                }
            }
        }

        return $evaluate;
    }

    static function get_swifposts($id = false, $status = false){
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}swifposts WHERE 1=1 ";

        if($status === 1){
            $sql .= " AND status = 1 ";
        }elseif($status === 0){
            $sql .= " AND status = 0 ";
        }

        if($id){
            $sql .= $wpdb->prepare(" AND id = %d", $id);

            return $wpdb->get_row($sql, ARRAY_A);
        }

        $rules = $wpdb->get_results($sql, ARRAY_A);

        return $rules;
    }

    static function get_swifposts_custom_fields($swifpost_id){
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}swifposts_custom_fields where swifpost_id = %d", $swifpost_id);
        $rules = $wpdb->get_results($sql, ARRAY_A);

        return $rules;
    }

    static function get_swifposts_taxonomies($swifpost_id){
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}swifposts_taxonomies where swifpost_id = %d", $swifpost_id);
        $rules = $wpdb->get_results($sql, ARRAY_A);

        return $rules;
    }

    static function get_custom_fields(){
        global $wpdb;

        $sql = "Select DISTINCT meta_key from {$wpdb->postmeta}";

        $results = $wpdb->get_col($sql);

        $results = apply_filters('swifposts_custom_fields', $results);

        return $results;
    }

    static function get_field_types(){
        $types = array('string', 'text', 'enum', 'integer', 'float', 'date', 'location');

        $types = apply_filters('swifposts_field_type', $types);

        return $types;
    }

    static function get_post_types(){
        $post_types = get_post_types(array(), 'objects');

        $post_types = apply_filters('swifposts_post_types', $post_types);

        return $post_types;
    }

    static function get_setting_url(){
        $base_url = admin_url('options-general.php');
        $base_url = add_query_arg('page', SWIFPOSTS_SLUG, $base_url);

        return $base_url;
    }

    static function display_errors($error){
        $message = $error;
        if($error instanceof WP_Error){
            $message = implode('<br/>', $error->get_error_messages());
        }

        if(!empty($message)){
            echo "<div class='error'><p>".$message."</p></div>";
        }
    }
    static function display_messages($message){

        if($message instanceof WP_Error){
            $message = implode('<br/>', $message->get_error_messages());
        }

        if(!empty($message)){
            echo "<div class='updated'><p>".$message."</p></div>";
        }
    }
}

new Swifposts_Loader();

