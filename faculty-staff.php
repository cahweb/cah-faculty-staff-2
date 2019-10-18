<?php
/**
 * Plugin Name: CAH Faculty and Staff Plugin
 * Description: A plugin to provide a page template, functionality, and styling for dynamic, responsive Faculty and Staff pages in the CAH family of sites. Based in part on code by Mannong Pang.
 * Author: Mike W. Leavitt
 * Version: 2.0.0
 */

/*
 * NOTE: Make sure there is a constant named "DEPT" with the individual department's ID defined
 * in your functions.php file, or use the Options page to set your department (both will work).
 */

// Keep people out of the file directly.
defined( 'ABSPATH' ) or die( 'No direct access plzthx' );

define( 'CAH_FACULTY_STAFF__PLUGIN_FILE', __FILE__ );
define( 'CAH_FACULTY_STAFF__PLUGIN_DIR', plugin_dir_path(__FILE__ ) );
define( 'CAH_FACULTY_STAFF__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once 'includes/config/cah-faculty-staff-config.php';
require_once 'includes/admin/cah-faculty-staff-admin.php';
require_once 'includes/cah-faculty-staff-helper.php';
require_once 'includes/cah-faculty-staff-query-lib.php';
require_once 'includes/cah-faculty-staff-query-ref.php';
require_once 'includes/cah-faculty-staff-ajax.php';

use CAH_FacultyStaffConfig as FSConfig;

if( !function_exists( 'cah_faculty_staff_plugin_activate' ) ) {
    function cah_faculty_staff_plugin_activate() {
        FSConfig::config();
        flush_rewrite_rules();
    }
}
register_activation_hook( CAH_FACULTY_STAFF__PLUGIN_FILE, 'cah_faculty_staff_plugin_activate' );

if( !function_exists( 'cah_faculty_staff_plugin_deactivate' ) ) {
    function cah_faculty_staff_plugin_deactivate() {
        FSConfig::deconfig();
        flush_rewrite_rules();
    }
}
register_deactivation_hook( CAH_FACULTY_STAFF__PLUGIN_FILE, 'cah_faculty_staff_plugin_deactivate' );

add_action( 'plugins_loaded', function() {
    FSConfig::action_hooks();
    FSConfig::setup_template();

    $dept = get_option( FSConfig::get_opt_prefix() . 'dept' );
    if( !defined( 'DEPT' ) ) {

        if( $dept === FALSE ) FSConfig::config();
        else {
            define( 'DEPT', intval( $dept ) );
        }

    } else {

        if( intval( $dept ) != DEPT )
            update_option( FSConfig::get_opt_prefix() . 'dept', DEPT );
    }
}, 10, 0);
?>