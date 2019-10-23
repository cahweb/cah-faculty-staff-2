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

        private static $templates = array(
            'page-faculty-staff.php' => 'Faculty Staff Plugin Page Template'
        );

        private static $option_prefix = "cah_faculty_staff_";
        private static $option_defaults = array(
                'dept' => 11,
                'interests' => 1,
                'img_type' => 'circle'
            );

        private function __construct() {}
        

        public static function setup_template() {

            // Add the template to the relevant metaboxes, based on WP Version.
            if( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {

                // 4.6 or older
                add_filter(
                    'page_attributes_dropdown_pages_args',
                    array( __CLASS__, 'register_project_templates' )
                );

            } else {

                // 4.7 or higher
                add_filter(
                    'theme_page_templates', 
                    array( __CLASS__, 'add_new_template' )
                );
            }

            // Add filter to save post to get our filter in.
            add_filter(
                'wp_insert_post_data',
                array( __CLASS__, 'register_project_templates' )
            );

            // Find our template.
            add_filter(
                'template_include',
                array( __CLASS__, 'view_project_template' )
            );

        }


        public static function register_project_templates( $atts ) {
            // Create the cache key.
            $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

            // Get cache list. If it's empty or nonexistent, make an array.
            $templates = wp_get_theme()->get_page_templates();
            if( empty( $templates ) ) {
                $templates = array();
            }

            // Delete old cache.
            wp_cache_delete( $cache_key, 'themes' );

            // Add our template to the list.
            $templates = array_merge( $templates, self::$templates );

            // Add modified cache and template list, so it'll show up among available templates.
            wp_cache_add( $cache_key, $templates, 'themes', 1800 );

            return $atts;
        }


        public static function view_project_template( $template ) {

            if( is_search() ) {
                return $template;
            }

            global $post;

            if( !$post ) {
                return $template;
            }

            if( !isset( self::$templates[get_post_meta( $post->ID, '_wp_page_template', TRUE ) ] ) ) {
                return $template;
            }

            $filepath = apply_filters( 'page_templater_plugin_dir_path', CAH_FACULTY_STAFF__PLUGIN_DIR );

            $file = $filepath . get_post_meta( $post->ID, '_wp_page_template', TRUE );

            if( file_exists( $file ) ) {
                return $file;

            } else {
                echo $file;
            }

            return $template;
        }


        public static function add_new_template( $posts_templates ) {
            $posts_templates = array_merge( $posts_templates, self::$templates );
            return $posts_templates;
        }


        public static function config() {
            self::_add_options();
        }

        private static function _add_options() {

            $defaults = self::$option_defaults;
            add_option( self::$option_prefix . 'dept', $defaults['dept'] );
            add_option( self::$option_prefix . 'interests', $defaults['interests'] );
            add_option( self::$option_prefix . 'img_type', $defaults['img_type'] );
        }

        public static function deconfig() {
            self::_delete_options();
        }

        private static function _delete_options() {

            delete_option( self::$option_prefix . 'dept' );
            delete_option( self::$option_prefix . 'interests' );
            delete_option( self::$option_prefix . 'img_type' );
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
            add_action( 'wp_ajax_print_faculty_staff', array( 'CAH_FacultyStaffAJAX', 'print_faculty' ), 10, 0 );
            add_action( 'wp_ajax_nopriv_print_faculty_staff', array( 'CAH_FacultyStaffAJAX', 'print_faculty' ), 10, 0 );
            add_action( 'admin_init', array( 'CAH_FacultyStaffAdmin', 'register_menu' ), 10, 0 );
            add_action( 'admin_menu', array( 'CAH_FacultyStaffAdmin', 'add_options_page' ), 10, 0 );
        }


        /**
         * Load our JavaScript and pass the values we need with wp_localize_script().
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @return void
         */
        public static function load_scripts() {

            // Don't want to load this if we're in the Dashboard.
            if( is_admin() ) return;

            // Load our CSS
            wp_enqueue_style( 'faculty-staff-style', CAH_FACULTY_STAFF__PLUGIN_URL .'static/css/faculty-staff.min.css' );

            // Load our JS. Come on, you know how this works by now... :P
            wp_enqueue_script( 'faculty-staff-script', CAH_FACULTY_STAFF__PLUGIN_URL . 'static/js/faculty-staff.min.js', array( 'jquery', 'script' ), '1.0.1', TRUE );

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
        public static function jquery_footer( $wp_scripts ) {

            // We don't want to load this if we're in the Dashboard.
            if( is_admin() ) return;

            // Modifying the jQuery entries in the $wp_scripts object to load in the footer.
            $wp_scripts->add_data( 'jquery', 'group', 1 );
            $wp_scripts->add_data( 'jquery-core', 'group', 1 );
            $wp_scripts->add_data( 'jquery-migrate', 'group', 1 );
        }


        // Getters
        public static function get_opt_prefix() { return self::$option_prefix; }
        public static function get_opt_defaults() { return self::$option_defaults; }
    }
}
?>