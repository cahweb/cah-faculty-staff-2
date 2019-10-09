<?php
/**
 * Helper class to start everything up and configure stuff.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

if( !class_exists( 'CAH_FacultyStaffConfig' ) ) {
    class CAH_FacultyStaffConfig
    {

        private static 
            $option_prefix = "cah_faculty_staff_",
            $option_defaults = array(
                'dept' => 0
            );
        public static function config() {
            self::_add_options();
        }

        private static function _add_options() {

            $defaults = self::$option_defaults;
            add_option( self::$option_prefix . 'dept', $defaults['dept'] );
        }

        public static function deconfig() {
            self::_delete_options();
        }

        private static function _delete_options() {

            delete_option( self::$option_prefix . 'dept' );
        }

        /**
         * Register our action hooks with Wordpress. All the methods are found in this class.
         * (Called with add_action() from functions.php).
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @return void
         */
        public static function action_hooks() : void {
            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ), 9, 0 );
            add_action( 'wp_default_scripts', array( __CLASS__, 'jquery_footer' ), 5, 1 );
            add_action( 'wp_ajax_print_faculty_staff', array( __CLASS__, 'print_faculty' ), 10, 0 );
            add_action( 'wp_ajax_nopriv_print_faculty_staff', array( __CLASS__, 'print_faculty' ), 10, 0 );
        }


        /**
         * Load our JavaScript and pass the values we need with wp_localize_script().
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @return void
         */
        public static function load_scripts() : void {

            // Don't want to load this if we're in the Dashboard.
            if( is_admin() ) return;

            // Load our CSS
            wp_enqueue_style( 'faculty-staff-style', get_stylesheet_directory_uri() .'/static/css/cah-faculty-staff.min.css' );

            // Load our JS. Come on, you know how this works by now... :P
            wp_enqueue_script( 'faculty-staff-script', get_stylesheet_directory_uri() . '/static/js/cah-faculty-staff.min.js', array( 'jquery', 'script' ), '1.0.0', TRUE );

            // Stuff to pass to our page's JavaScript. The "security" field is a nonce
            // we're creating to make sure it's still the user making requests on the
            // back-end.
            wp_localize_script( 'faculty-staff-script', 'pageVars', array(
                    // The URL we'll use in $.ajax()
                    'url' => admin_url( 'admin-ajax.php' ),
                    // A nonce for an added layer of back-end security.
                    'security' => wp_create_nonce( 'ajax-nonce' )
                )
            );
        }

        
        /**
         * Make WordPress load JQuery in the footer, if at all possible, to speed up load times.
         * If another script's dependenccy requires JQuery to be loaded in the header, this won't
         * really have any effect.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param object $wp_scripts | The script handler object thingy for WordPress.
         * 
         * @return void
         */
        public static function jquery_footer( $wp_scripts ) : void {

            // We don't want to load this if we're in the Dashboard.
            if( is_admin() ) return;

            // Modifying the jQuery entries in the $wp_scripts object to load in the footer.
            $wp_scripts->add_data( 'jquery', 'group', 1 );
            $wp_scripts->add_data( 'jquery-core', 'group', 1 );
            $wp_scripts->add_data( 'jquery-migrate', 'group', 1 );
        }
    }
}
?>