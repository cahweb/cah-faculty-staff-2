<?php
/**
 * Static class to help build the back-end stuff.
 * 
 * @author Mike W. Leavitt
 * @version 2.0.0
 */
require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/config/cah-faculty-staff-config.php';

use CAH_FacultyStaffConfig as FSCfg;

if( !class_exists( 'CAH_FacultyStaffAdmin' ) ) {
    class CAH_FacultyStaffAdmin
    {

        private function __construct() {}

        public static function register_menu() {
            self::_settings_init();
            self::_action_hooks();
        }


        public static function settings_field_info() {

            echo '<p>The general settings for the CAH Faculty/Staff plugin.';
        }


        public static function build_options() {

            if( !current_user_can( 'manage_options' ) ) wp_die( "You're not supposed to be here." );

            ob_start();

            include_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/views/cah-faculty-staff-options.php';

            echo ob_get_clean();
        }


        public static function build_dept_list() {

            ob_start();

            include_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/views/cah-faculty-staff-dept-list.php';

            echo ob_get_clean();
        }


        public static function build_interest_checkbox() {

            //if( !current_user_can( 'manage_options' ) ) wp_die( "You don't have permission to be here." );

            ob_start();

            include_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/views/cah-faculty-staff-interest-checkbox.php';

            echo ob_get_clean();
        }


        public static function load_style() {

            if( !is_admin() ) return;

            wp_enqueue_style( 'faculty-staff-admin-css', CAH_FACULTY_STAFF__PLUGIN_URL . 'static/css/faculty-staff-admin.min.css' );
        }


        public static function update_options() {

            if( !isset( $_POST['update_options'] ) || !wp_verify_nonce( $_POST['update_options'], 'faculty_staff_update_options' ) )
                wp_die( "Could not verify your nonce." );

            if( isset( $_POST['department'] ) ) {
                update_option( FSCfg::get_opt_prefix() . 'dept', intval( $_POST['department'] ) );
            }

            if( isset( $_POST['show-interests'] ) ) {
                update_option( FSCfg::get_opt_prefix() . 'interests', intval( $_POST['show-interests'] ) );
            }

            $get_vars = "?success=1";

            $base_url = menu_page_url( 'cah-faculty-staff-options' );

            if( wp_redirect( $base_url . $get_vars ) )
                exit();
            else
                die();
        }


        private static function _action_hooks() {
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_style' ), 11, 0 );
            add_action( 'admin_post_faculty_staff_update_options', array( __CLASS__, 'update_options' ), 10, 0 );
        }


        public static function add_options_page() {
            $page_title = "CAH Faculty/Staff Options";
            $menu_title = "CAH Faculty/Staff";
            $capability = "manage_options";
            $menu_slug = "cah-faculty-staff-options";
            $callback = array( __CLASS__, 'build_options' );

            return add_options_page(
                $page_title,
                $menu_title,
                $capability,
                $menu_slug,
                $callback
            );
        }


        private static function _settings_init() {

            register_setting( 'cah_faculty_staff', FSCfg::get_opt_prefix() . "dept" );
            register_setting( 'cah_faculty_staff', FSCfg::get_opt_prefix() . "interests" );

            add_settings_section(
                'cah_faculty_staff_options_main',
                'General Settings',
                array( __CLASS__, 'settings_field_info' ),
                'cah-faculty-staff-options'
            );

            add_settings_field(
                FSCfg::get_opt_prefix() . "dept",
                "Department: ",
                array( __CLASS__, 'build_dept_list' ),
                'cah-faculty-staff-options',
                'cah_faculty_staff_options_main'
            );

            add_settings_field(
                FSCfg::get_opt_prefix() . "interests",
                "Display Interests ",
                array( __CLASS__, 'build_interest_checkbox' ),
                'cah-faculty-staff-options',
                'cah_faculty_staff_options_main'
            );
        }
    }
}
?>