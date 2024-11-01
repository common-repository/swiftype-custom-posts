<?php
/*
Plugin Name: Swiftype Custom Posts
Plugin URI: http://wordpress.org/plugins/swiftype-custom-posts/
Description: This add-on extends the functionality of the WordPress Swiftype Search plugin. It allows to index the custom post types, taxonomies with custom fields easily.
Author: Phuc Pham
Version: 0.3
Author URI: http://www.clientsa2z.com
Copyright: © 2015 Phuc Pham.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Requires WordPress at least: 3.5
Tested up to WordPress: 4.3.1
*/

// don't load directly
if ( !defined('ABSPATH') )
    die('-1');


require_once 'swifposts-config.php';
require_once 'swifposts-install.php';
require_once 'swifposts-loader.php';

register_activation_hook( __FILE__, array("Swifposts_Install", 'activate') );