<?php

if ( !defined('ABSPATH') )
    die('-1');

class Swifposts_Install{
    function __construct(){
        add_action('admin_init', array($this, 'upgrade'), 1);
    }

    public static function activate(){

    }

    public function upgrade(){
        $db_version = get_option('swifpost_db_version');
        if(empty($db_version)){
            $db_version = '0';
        }

        if(version_compare($db_version, '0.1', '<')){
            $this->upgrade_v01();
            update_option('swifpost_db_version', '0.1');
        }

        if(version_compare($db_version, '0.2', '<')){
            $this->upgrade_v02();
            update_option('swifpost_db_version', '0.2');
        }
    }

    public static function upgrade_v01(){
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = $wpdb->get_charset_collate();


        $table_name = $wpdb->prefix . 'swifposts';
        $sql = "
        CREATE TABLE `{$table_name}` (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `post_type` varchar(128) NOT NULL,
          `custom_title` TEXT NULL,
          `custom_url` TEXT NULL,
          `status` char(1) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB {$charset_collate} AUTO_INCREMENT=1 ;
        ";
        dbDelta( $sql );


        $table_name = $wpdb->prefix . 'swifposts_custom_fields';
        $sql = "
        CREATE TABLE `{$table_name}` (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `swifpost_id` bigint(20) NOT NULL,
          `field_key` varchar(256) NOT NULL,
          `data_type` varchar(128) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB {$charset_collate} AUTO_INCREMENT=1 ;
        ";
        dbDelta( $sql );

        /*
        $table_name = $wpdb->prefix . 'swifposts_taxonomies';
        $sql = "
        CREATE TABLE `{$table_name}` (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `swifpost_id` bigint(20) NOT NULL,
          `taxonomy` varchar(256) NOT NULL,
          `data_type` varchar(128) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB {$charset_collate} AUTO_INCREMENT=1 ;
        ";
        dbDelta( $sql );
        */

    }

    function upgrade_v02(){
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'swifposts';
        $sql = "
        CREATE TABLE `{$table_name}` (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `post_type` varchar(128) NOT NULL,
          `custom_title` TEXT NULL,
          `custom_url` TEXT NULL,
          `exclude_ids` TEXT NULL,
          `status` char(1) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB {$charset_collate} AUTO_INCREMENT=1 ;
        ";
        dbDelta( $sql );
    }
}

new Swifposts_Install();