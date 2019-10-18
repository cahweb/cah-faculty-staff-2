<?php
/**
 * Handles our AJAX processing on the back-end.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once 'cah-faculty-staff-query-ref.php';
require_once 'cah-faculty-staff-helper.php';
require_once 'config/cah-faculty-staff-config.php';

use CAH_FacultyStaffQueryRef as FSQEnum;
use CAH_FacultyStaffHelper as FSH;
use CAH_FacultyStaffConfig as FSCfg;

if( !class_exists( 'CAH_FacultyStaffAJAX' ) ) {
    class CAH_FacultyStaffAJAX
    {
        private function __construct() {}

        /**
         * Our AJAX back-end handler, called by admin-ajax.php for both wp_ajax_print_faculty_staff and 
         * wp_ajax_nopriv_print_faculty_staff (since we'll want the same behavior regardless of
         * whether the user is logged in).
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return void;
         */
        public static function print_faculty() {

            // Check the nonce. Calls die() if it fails.
            check_ajax_referer( 'ajax-nonce', 'security' );

            // Set the subdepartment ID and/or user ID, if applicable.
            // Probably won't ever have both set at once, but preferences subdepartment.
            $sub_dept = isset( $_POST[ 'sub_dept'] ) ? intval( $_POST[ 'sub_dept' ] ) : 0;
            $user_id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

            // Gets the relevant staff query result.
            $result = self::_get_staff( $sub_dept, $user_id );

            // If we've defined a subdepartment, we'll go get that.
            if( $sub_dept !== 0 )
                echo self::_print_staff( $result, TRUE );

            // Or, if we've defined a user, we'll get them instead.
            else if( $user_id !== 0 )
                echo self::_print_staff_detail( $result );

            // Barring that, we'll get e'er'body.
            else
                echo self::_print_staff( $result );

            // We're done with the database at this point, so close the connection.
            FSH::db_close();

            // Important to remind the script to die at the end, for security's sake.
            die();
        }


        /**
         * The main "switchboard" of the class. Determines which query string to retrieve from the
         * FacultyStaffQueryLib object and run.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $sub_dept  | The subdepartment, in case we're trying to be more specific.
         * @param int|string $user_id   | The user ID, in case we're looking for a specific staff member.
         * 
         * @return mysqli_result|bool $result | The results of the query, or FALSE if no result.
         */
        private static function _get_staff( int $sub_dept = 0, int $user_id = 0 ) : mysqli_result {

            $result = NULL;

            // 1 is for Administration
            if( $sub_dept === -1 )
                $result = FSH::query( FSQEnum::DEPT_ADMIN );
            
            // 2 is for Advising
            else if( $sub_dept === -2 )
                $result = FSH::query( FSQEnum::DEPT_STAFF );
            
            // Any of the department's unique subdepartments
            else if( $sub_dept !== 0 )
                $result = FSH::query( FSQEnum::DEPT_SUB_GENERAL, $sub_dept );
            
            // Single staff member's info
            else if( $user_id !== 0 )
                $result = FSH::query( FSQEnum::DEPT_USER, $user_id );
            
            // General query for e'er'body
            else
                $result = FSH::query( FSQEnum::DEPT_ALL );
            
            // Return whatever we get. Shouldn't make it this far without being successful.
            return $result;
        }


        private static function _print_staff( mysqli_result $result, bool $format = FALSE ) : string {

            // Check if the option to include interests is set (defaults to TRUE);
            $include_interests = intval( get_option( FSCfg::get_opt_prefix() . 'interests' ) );

            // Start HTML buffer
            ob_start();

            include_once 'views/cah-faculty-staff-print-staff.php';

            // Free up the memory
            mysqli_free_result( $result );

            // Return buffered HTML
            return ob_get_clean();
        }
    
        private static function _print_staff_detail( mysqli_result $result ) : string {
            
            // We only need one result.
            $row = mysqli_fetch_assoc( $result );

            // Start HTML buffer
            ob_start();

            include_once 'views/cah-faculty-staff-print-staff-detail.php';

            // Free up memory
            //mysqli_free_result( $result );

            // Return buffered HTML
            return ob_get_clean();
        }
    }
}
?>