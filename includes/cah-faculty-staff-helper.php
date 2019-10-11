<?php
/**
 * A static helper class for processing and formatting the HTML output.
 * 
 * @author Mike W. Leavitt
 * @version 2.0.0
 */

require_once 'dbconfig.php';
require_once 'cah-faculty-staff-query-lib.php';
require_once 'cah-faculty-staff-query-ref.php';

use CAH_FacultyStaffQueryLib as FSQLib;
use CAH_FacultyStaffQueryRef as FSQEnum;

if( !class_exists( 'CAH_FacultyStaffHelper' ) ) {
    class CAH_FacultyStaffHelper
    {
        private static $_query_lib, $_db_connection;

        private function __construct() {}

        
        /**
         * Runs an SQL query based on the kind of information the user is looking for.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int   $type | The type flag, for grabbing the SQL we'll need. Technically an
         *                      int, but will correspond to one of the constants in the
         *                      CAH_FacultyStaffQueryRef pseudo-enum class, for more readable
         *                      code.
         * @param array $args | Whichever other arguments the specific function requires. Passed
         *                      directly to the CAH_FacultyStaffQueryLib::get_query_str() function.
         * 
         * @return mysqli_result $result | The results of the query, validated to make sure they're
         *                                  good.
         */
        public static function query( int $type, ... $args ) : mysqli_result {

            $qlib = self::_get_lib();

            $sql = $qlib->get_query_str( $type, ... $args );

            $result = self::_run_query( $sql );

            return $result;
        }


        /**
         * Closes the database connection, when we're done.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return void
         */
        public static function db_close() : void {
            
            // If there's a database connection, close it, and set the class member to FALSE.
            if( self::$_db_connection !== FALSE ) mysqli_close( self::$_db_connection );
            self::$_db_connection = FALSE;
        }


        /**
         * Gets the appropriate staff image, formatted as HTML.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $fullname | The staff member's name.
         * @param string $filename | The staff member's profile photo, from their photo_path entry.
         *                              Defaults to "profilephoto.jpg," an empty silhouette.
         * @param string $extra    | The value of the staff member's photo_extra field, if any. Defaults
         *                              to an empty string.
         * @param int    $size     | The size code for the image. Defaults to 5.
         * 
         * @return string (anon) | Buffered HTML string containing the approprite <img> tag.
         */
        public static function get_staff_img( $fullname, string $filename = "profilephoto.jpg", string $extra = "", int $size = 5 ) : string {

            // Set the base URL and classes we want every image to have.
            $resize_url = "https://cah.ucf.edu/common/resize.php";
            $classes = array( 'img-circle', 'mr-3' );

            // Add the extra class for the blank, generic silhouette photo.
            if( $filename == 'profilephoto.jpg' ) array_push( $classes, 'd-flex' );

            ob_start(); // Start output buffer and create <img> tag to return.
            ?>
            <img class="<?= implode( " ", $classes ); ?>" src="<?= $resize_url ?>?filename=<?= $filename ?><?= $extra ?>&sz=<?= $size ?>" alt="<?= $fullname ?>">
            <?php

            return ob_get_clean(); // Return buffered HTML
        }


        /**
         * Retrieves and formats a truncated version of a faculty or staff member's stated
         * research interests. Current limit is 45 characters. Called from staff_detail().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $interests | The string drawn from the field in staff_detail().
         * 
         * @return string $interests_out | The formatted and normalized string of interests.
         */
        public static function interests_short( string $interests ) {

            // If it's formatted as an unordered list, do this:
            if( stripos( $interests, "<ul>" ) !== FALSE ) {

                $interest_arr = array();
                
                // NEVER USE REGEX TO PARSE HTML
                libxml_use_internal_errors( TRUE );
                try {
                    $xml = new SimpleXMLElement( "<body>$interests</body>");

                } catch( Exception $e ) {
                    $xml = NULL;
                }

                if( $xml != NULL ) {
                    $xml_parse = $xml->xpath( 'ul/li' );
                    foreach( $xml_parse as $interest ) {
                        array_push( $interest_arr, trim( $interest ) );
                    }

                } else { // If something goes wrong with the parser, do it this way instead.
                    $interests = strip_tags( $interests, "<li>" );
                    $interest_arr = explode( "<li>", $interests );
                }

            } else {
                // If it's in paragraph form, strip out the tags we don't want, so we just
                // have plain text.
                $interests = strip_tags( $interests );
                $interests = str_ireplace( "<p>", "", $interests );
                $interests = str_ireplace( "</p>", "", $interests );
                $interests = str_replace( "<br />", "", $interests );
                $interests = str_replace( "<br>", "", $interests );

                // Break up any one of the weird ways the users delimit their lists of interests.
                // Using strpos() and substr_count() because it's cheaper than any of the preg_*
                // family of functions.
                if( strpos( $interests, ";" ) !== FALSE )
                    $interest_arr = explode( ";", $interests );
                else if( strpos( $interests, "," ) !== FALSE )
                    $interest_arr = explode( ",", $interests );
                else if( strpos( $interests, "." ) !== FALSE && ( substr_count( $interests, "." ) > 1 || strpos( $interests, ".", -1 ) === FALSE ) )
                    $interest_arr = explode( ".", $interests );
            }

            // Format everything nicely, with commas and stuff, putting in an ellipsis if the
            // list isn't complete.
            $interests_out = "";
            foreach( $interest_arr as $idx => $interest ) {

                $interests_out .= trim( $interest );

                if( $idx + 1 == count( $interest_arr ) ) {
                    $interests_out .= ".";
                    break;
                }

                if( strlen( $interests_out ) >= 45 ) { 
                    $interests_out .= "&hellip;";
                    break;

                } else {
                    $interests_out .= ", ";
                }
            }

            // Return the shiny, new formatted string.
            return $interests_out;
        }


        /**
         * Retrieves the list of courses for the upcoming academic year. Called by staff_detail().
         * A lot of these extra parameters aren't used in the current iteration of this script,
         * but they might come in handy later on, for other purposes.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param int|string $user_id                 | The staff member's ID in the `users` table.
         * @param string     $term                    | The specific term, if applicable. Defaults to
         *                                                  empty.
         * @param string     $career                  | The specific career (Undergraduate or Graduate).
         *                                                  Defaults to empty.
         * @param string     $catalog_ref_filter_any  | A filter for the course results.
         * @param string     $catalog_ref_filter_none | Another filter for the results, but negative.
         * @param string     $prefix_filter_any       | Another filter, for specific course prefixes.
         * 
         * @return string (anon) | Buffered HTML containing the tabbed course list, sorted by semester.
         */
        public static function get_course_list( $user_id = 0, $term = "", $career = "", $catalog_ref_filter_any = "", $catalog_ref_filter_none = "", $prefix_filter_any = "" ) {

            // Initialize some stuff, since we'll be referring to it throughout:

            $terms = array(); // Will be a list of the terms we'll be grabbing courses for
            $term_courses = array(); // Will be a list of the courses, sorted by term
            $current_term = ""; // The current term, so we know where to begin.

            $sql_term = ""; // Beginning of SQL statement we'll be using later on
            $sql_aux = ""; // End of the same string.

            $summer_flag = FALSE; // Checks whether to display/look for summer courses.

            // The location where we keep what syllabi we have.
            $syllabus_url_base = "https://cah.ucf.edu/common/files/syllabi/";

            $career = ""; // Graduate or Undergraduate, in case we need to narrow things down.

            // If we don't have a target term, get the current one, then get the others.
            if( empty( $term ) ) {

                $result = self::query( FSQEnum::TERM_GET );

                while( $row = mysqli_fetch_assoc( $result ) ) {

                    if( $row[ 'term' ] != '-' ) {

                        array_push( $terms, $row[ 'term' ] ); // Add to the array

                        if( empty( $sql_term ) ) {
                            $sql_term = "`term` IN ("; // Define the SQL

                        } else {
                            $sql_term .= ",";
                        }
                        $sql_term .= "'{$row['term']}'";
                    }
                }

                if( !empty( $sql_term ) ) $sql_term .= ") "; // Close the parentheses, if need be

                $current_term = self::_get_semester(); // Grab the current term

            } else {
                // If term is defined, just add it to the $terms array and add it to the SQL
                array_push( $terms, $term );
                $current_term = $term;
                $sql_term = "`term` = '" . mysqli_real_escape_string( self::db_get(), $term ) . "'";
            }

            // Any course prefixes/numbers we explicitly want *in* the query
            if( !empty( $catalog_ref_filter_any ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $catalog_ref_filter_any ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            // Any course prefixes/numbers we explicitly *don't* want in the query
            if( !empty( $catalog_ref_filter_none ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $catalog_ref_filter_none, FALSE ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            // If we just want prefixes
            if( !empty( $prefix_filter_any ) ) {

                if( !empty( $sql_filter = self::_parse_filters( $prefix_filter_any, TRUE, TRUE ) ) )
                    $sql_aux .= " AND $sql_filter";
            }

            // Generate the SQL and run the query.
            $result = self::query( FSQEnum::COURSE_LIST, $user_id, $sql_term, $sql_aux, $career );
            
            if( $result->num_rows == 0) {
                mysqli_free_result( $result );
                self::db_close();
                return ""; // If we find nothing, we return empty.
            }

            // Iterate through the courses.
            while( $row = mysqli_fetch_assoc( $result ) ) {

                $term_idx = trim( $row[ 'term' ] );

                //if( preg_match( "/summer/i", $term_idx ) )
                if( stripos( $term_idx, "summer" ) )
                    $summer_flag = TRUE;
                
                // If this is the first time we're doing this, open the table and create the
                // header row
                if( empty( $term_courses[ $term_idx ] ) ) {
                    
                    ob_start(); // Start output buffer
                    ?>
                    <table class="table table-condensed table-bordered table-striped volumes" cellspacing="0" title="<?= $term_idx ?> Offered Courses">
                        <thead>
                            <tr>
                                <th>Course Number</th>
                                <th>Course</th>
                                <th>Title</th>
                                <th>Mode</th>
                                
                                <?php if( $summer_flag ) : ?>
                                <th>Session</th>
                                <?php endif; ?>

                                <th>Date and Time</th>
                                <th>Syllabus</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                    $term_courses[ $term_idx ] = ob_get_clean(); // Store buffered HTML
                }

                ob_start(); // Start output buffer again, and spit out the row.
                ?>
                            <tr>
                                <td><?= $row[ 'number' ] ?></td>
                                <td><?= trim( $row[ 'catalogref' ] ); ?></td>
                                <td><?= trim( $row[ 'title' ] ); ?></td>
                                <td><?= trim( $row[ 'instruction_mode' ] ); ?></td>

                                <?php if( $summer_flag ) : ?>
                                <td><?= trim( $row[ 'session' ] ); ?>
                                <?php endif; ?>

                                <td><?= trim( $row[ 'dateandtime' ] ); ?></td>
                                <td>
                                <?php if( !empty( $row[ 'syllabus_file' ] ) ) : ?>
                                    <a href="<?= $syllabus_url_base . str_replace( " ", "", $row[ 'catalogref' ] ) . $row[ 'section' ] . $row[ 'term' ] . ".pdf" ?>" rel="external">Aviailable</a>
                                <?php else: ?>
                                    Unavailable
                                <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="<?= $summer_flag ? 7 : 6 ?>">
                                    <?= $row[ 'description' ] ?>
                                </td>
                            </tr>
                <?php
                $term_courses[ $term_idx ] .= ob_get_clean(); // Store buffered HTML
            }

            // Free up the memory from the result.
            mysqli_free_result( $result );

            ob_start(); // Start output buffer again, and create the nav tabs.
            ?>
            <div style="width: 100%;">
                <ul class="nav nav-tabs" id="courseTab" role="tablist">
            <?php
            $term_labels = str_replace( " ", "", $terms );

            // Using a standard for loop to avoid problems with PHP's foreach iterator getting
            // stuck at the last item.
            for( $c = 0; $c < count( $terms ); $c++ ) {
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?= !strcmp( $current_term, $terms[ $c ] ) ? "active" : "" ?>" data-toggle="tab" href="#<?= $term_labels[ $c ] ?>" role="tab" aria-controls="<?= $term_labels[ $c ] ?>"><?= $terms[ $c ] ?></a>
                    </li>
                <?php
            }
            ?>
                </ul>
            </div>

            <div class="tab-content">
            <?php

            // Same thing here, for the same reason, only creating the nav panes this time
            for( $c = 0; $c < count( $terms ); $c++ ) {
                ?>
                    <div class="pt-3 tab-pane <?= !strcmp( $current_term, $terms[ $c ] ) ? "active" : "" ?>" id="<?= $term_labels[ $c ] ?>" role="tabpanel">

                <?php if( !empty( $term_courses[ $terms[ $c ] ] ) ) : // Print info if we've got it ?>

                    <?= $term_courses[ $terms[ $c ] ] ?></div>
                        </tbody>
                    </table>
                </div>

                <?php else: ?>

                     <p>No courses found for <?= $terms[ $c ] ?></p></div>

                <?php endif;
            }

            return ob_get_clean(); // Return buffered HTML
        }


        public static function maybe_print( string $item, string $heading ) : string {

            if( empty( $item ) ) return "";

            ob_start();
            ?>
            <h3 class="heading-underline"><?= $heading ?></h3>
            <?php 
            $item_str = html_entity_decode( $item );
            if( stripos( $item_str, "<ul>" ) ) : ?>
                <?= $item_str ?>
            <?php else : ?>
                <p><?= $item_str ?></p>
            <?php endif;

            return ob_get_clean();
        }


        /**
         * Formats a phone number, depending on its length. We're assuming a US number, for the sake
         * of expediency and convenience.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $phone | The phone number to parse.
         * 
         * @return string $phone | The parsed and rearranged phone number, or the original if it doesn't
         *                          meet standard US length requirements.
         */
        public static function format_phone_us( string $phone ) : string {
            
            if( !isset( $phone ) ) return "";

            $phone = preg_replace( "/[^0-9]/", "", $phone );
            switch( strlen( $phone ) ) {

                case 7: // It's a "local" number, without an area code or country code.
                    return preg_replace( "/(\d{3})(\d{4})/", "$1-$2", $phone );
                    break;
                case 10: // It's a US number with area code, but no country code.
                    return preg_replace( "/(\d{3})(\d{3})(\d{4})/", "($1) $2-$3", $phone );
                    break;
                case 11: // It's a US number with the leading country code, as well.
                    return preg_replace( "/(\d)(\d{3})(\d{3})(\d{4})/", "+$1 ($2) $3-$4", $phone );
                    break;
                default: // It's not a US number, so just bounce it back.
                    return $phone;
                    break;
            }
        }


        /**
         * Instantiates the FacultyStaffQueryLib helper object, for concocting the SQL strings
         * we'll need.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return FacultyStaffQueryLib $query_lib | The FacultyStaffQueryLib object instance.
         */
        private static function _get_lib() : FSQLib {

            // If we don't have an instance stored already, create one.
            if( is_null( self::$_query_lib ) || !is_a( self::$_query_lib, 'CAH_FacultyStaffQueryLib' ) )
                self::$_query_lib = new FSQLib( DEPT );
            
            return self::$_query_lib;
        }


        /**
         * Connect to the database, or return the active connection, if one exists.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return mysqli $db_connection | The static class member connection object.
         */
        private static function _db_get() : mysqli {
            // Global database variables, set in dbconfig.php
            global $db_user, $db_pass, $db, $db_server;

            // If we've already got a connection open, just return that.
            if( self::$_db_connection ) return self::$_db_connection;

            // Otherwise, create one, and kill the script if it doesn't work.
            self::$_db_connection = mysqli_connect( $db_server, $db_user, $db_pass ) or exit( 'Could not connect to server.' );
            mysqli_set_charset( self::$_db_connection, 'utf8' ); // Set charset to UTF-8.
            // Select the appropriate database.
            mysqli_select_db( self::$_db_connection, $db ) or exit( 'Could not select database.' );

            // Return the new connection.
            return self::$_db_connection;
        }


        /**
         * Runs a given query, then validates and returns the results.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string $sql | The SQL query we'll be running.
         * 
         * @return mysqli_result $result | Return the query result.
         */
        private static function _run_query( string $sql ) : mysqli_result {

            // Do the thing.
            $result = mysqli_query( self::_db_get(), $sql );

            // Check to see if something went wrong. _validate() technically returns TRUE if
            // it works, but since the script dies if the result doesn't validate, it doesn't
            // really matter--if we get past it, we're good.
            self::_validate( $result, $sql );
            return $result;
        }


        /**
         * Makes sure there was a result, and dies with a message to the user if there wasn't.
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param mysqli_result|bool $result | The result of an SQL query, or FALSE if it failed.
         * @param string             $sql    | The SQL query string to print if $debug is TRUE.
         * @param bool               $debug  | Whether or not to display verbose query messaging 
         *                                      for debug purposes.
         * 
         * @return bool (anon) | Whether the SQL validates or not. Since the process dies on failure,
         *                          only ever actually returns TRUE.
         */
        private static function _validate( $result, string $sql, bool $debug = TRUE ) : bool {

            $msg = "";

            // If there's a problem with the query, $result will evaluate to FALSE. If we
            // call this with DEBUG on, then a problem will output the problem query to
            // the screen.
            if( !$result ) {
                if( $debug ) {

                    $msg .= "<p>Invalid query: " . mysqli_error( self::_db_get() ) . "</p>";
                    $msg .= "<p>Query: " . $sql . "</p>";

                } else {
                    $msg .= "There was a database problem. Please report this to <a href=\"mailto:cahweb@ucf.edu\">cahweb@ucf.edu</a>";
                }

                die( $msg );

            } else return TRUE; // If the variable exists, though, everything's fine.
        }


        /**
         * Determines the proper semester to start displaying. Called from _get_course_list().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @return string $term | The first term to show, with semester and four-digit year.
         */
        private static function _get_semester() {

            $now = getdate();
            $term = "";

            switch( $now[ 'mon' ] ) {

                case 10:
                case 11:
                case 12: // The Fall has already started, so start with Spring the following year.
                    $term = "Spring " . ( intval( $now['year'] ) + 1 );
                    break;

                case 1:
                case 2: // Spring again, but without adding to the year.
                    $term = "Spring {$now['year']}";
                    break;

                case 3:
                case 4:
                case 5:
                case 6: // The Spring semester, Summer is upcoming.
                    $term = "Summer {$now['year']}";
                    break;

                default: // Otherwise, we're focused on Fall
                    $term = "Fall {$now['year']}";
                    break;
            }

            return $term; // Return whatever we've come up with.
        }


        /**
         * Handles the modifications to the SQL statements called for by the given filter(s). Called
         * from _get_course_list().
         * 
         * @author Mike W. Leavitt
         * @since 2.0.0
         * 
         * @param string|array $catalog_ref | The filter(s) to use.
         * @param bool $in | Whether this filter will explicitly include or exclude things. Defaults
         *                      to TRUE.
         * @param bool $prefix_only | Wether to filter only the course prefixes. Defaults to FALSE.
         * 
         * @return string $sql_filter | The additional SQL, to further refine the course query.
         */
        private static function _parse_filters( $catalog_ref, bool $in = TRUE, bool $prefix_only = FALSE ) {

            // Shows whether we're only looking for the prefixes, or the entire course number.
            if( $prefix_only )
                $statement_begin = "`prefix` ";
            else
                $statement_begin = "CONCAT( `prefix`, `catalog_number` ) ";

            // If it's an array, store it.
            if( is_array( $catalog_ref ) )
                    $filters = $catalog_ref;
                
            // If not, make it an array and store it.
            else
                $filters = explode( ",", $catalog_ref );

            // Initialize.
            $sql_filter = "";

            foreach( $filters as $filter ) { // Go through and apply each filter.

                if( !empty( $sql_filter ) ) $sql_filter .= " , ";

                // Put "NOT" before "IN()" if $in === FALSE
                else $sql_filter = $statement_begin . ( !$in ? "NOT " : "" ) . "IN(";

                $sql_filter .= "'" . strtoupper( $filter ) . "'";
            }

            // If we found anything, close the parentheses.
            if( !empty( $sql_filter ) ) $sql_filter .= ")";

            // Return the filter part of the SQL
            return $sql_filter;
        }
    }
}
?>