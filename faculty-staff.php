<?php
/**
 * Plugin Name: CAH Faculty and Staff Plugin (Newer)
 * Description: A plugin to provide a page template, functionality, and styling for dynamic, responsive Faculty and Staff pages in the CAH family of sites. Based in part on code by Mannong Pang.
 * Author: Mike W. Leavitt
 * Version: 2.0.0
 */

/*
 * NOTE: Make sure there is a constant named "DEPT" with the individual department's ID defined in your functions.php file, or use the Options page to set your department (both will work).
 */

// Keep people out of the file directly.
defined( 'ABSPATH' ) or die();

define( 'CAH_FACULTY_STAFF__PLUGIN_FILE', __FILE__ );

require_once 'includes/cah-faculty-staff-config.php';

use CAH_FacultyStaffConfig as FSConfig;

if( !function_exists( 'cah_faculty_staff_plugin_activate' ) ) {
    function cah_faculty_staff_plugin_activate() {
        return FSConfig::config();
    }
}
register_activation_hook( CAH_FACULTY_STAFF__PLUGIN_FILE, 'cah_faculty_staff_plugin_activate' );

if( !function_exists( 'cah_faculty_staff_plugin_deactivate' ) ) {
    function cah_faculty_staff_plugin_deactivate() {
        return FSConfig::deconfig();
    }
}
register_deactivation_hook( CAH_FACULTY_STAFF__PLUGIN_FILE, 'cah_faculty_staff_plugin_deactivate' );

add_action( 'plugins_loaded', function() {
    FSConfig::action_hooks();
}, 10, 0);
?>