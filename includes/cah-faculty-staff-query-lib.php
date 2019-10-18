<?php
/**
 * A helper class meant to generate the SQL statements for the Faculty/Staff page, for the
 * sake of cleaner, more readable code.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once 'cah-faculty-staff-query-ref.php';

use CAH_FacultyStaffQueryRef as FSQEnum;

if( !class_exists( 'CAH_FacultyStaffQueryLib' ) ) {

    class CAH_FacultyStaffQueryLib {

        // Member variable to hold Dept number and the base query string.
        private $dept, $query_base, $excluded_subs, $order_by, $user_query, $case, $advising_case, $gen_query;

        // Array of academic advising positions for the "Advising" query section.
        private $advisor_positions = array( 53, 67, 84, 85, 121, 152, 153, 154, 155, 156, 157, 166, 185, 186, 187, 197 );


        /**
         * Constructor. Accepts Department ID number.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $dept | Department ID number.
         * 
         * @return void
         */
        public function __construct( $dept ) {

            // Set Department
            $this->dept = $dept;

            // This is the base string for all Department-level requests.
            $this->gen_query = "SELECT DISTINCT u.id, u.lname, CONCAT_WS(' ', u.fname, u.mname, u.lname) AS fullname, u.email, u.phone, t.description AS title, u_d.prog_title_dept AS title_dept, u_d.prog_title_dept_short AS title_dept_short, t.title_group AS title_group, u.photo_path, u.photo_extra, u.interests FROM cah.users AS u JOIN cah.users_departments AS u_d ON u.id = u_d.user_id JOIN cah.titles AS t ON u_d.title_id = t.id WHERE u_d.department_id = $this->dept AND u.active = 1 AND u.show_web = 1 AND u_d.affiliation = 'active'";

            // This is the base string for subdepartment-level requests.
            $this->query_base = "SELECT DISTINCT u.id, u.lname, CONCAT_WS(' ', u.fname, u.mname, u.lname) AS fullname, u.email, u.phone, t.description AS title, u_d.prog_title_dept AS title_dept, u_d.prog_title_dept_short AS title_dept_short, t.title_group AS title_group, u.photo_path, u.photo_extra, u.interests FROM cah.users AS u JOIN cah.users_departments AS u_d ON u.id = u_d.user_id JOIN cah.titles AS t ON u_d.title_id = t.id LEFT JOIN cah.academics AS a ON a.user_id = u.id LEFT JOIN cah.academic_categories AS a_c ON a_c.id = a.academic_id WHERE u.active = 1 AND u.show_web = 1 AND u_d.affiliation = 'active' AND u_d.department_id = $this->dept";

            // This is the base string for user-level requests.
            $this->user_query = "SELECT u.id, u.lname, u.fname, CONCAT_WS(' ', u.fname, u.mname, u.lname) AS fullname, u.email, u.phone, u.photo_path, u.photo_extra, u.location, u.room_id, u.office, u.interests, u.activities, u.awards, u.research, u.has_cv, u.homepage, u.biography, t.description AS title, t.title_group, u_d.prog_title_dept AS title_dept, u_d.prog_title_dept_short AS title_dept_short FROM cah.users AS u JOIN cah.users_departments AS u_d ON u.id = u_d.user_id JOIN cah.titles AS t ON u_d.title_id = t.id WHERE u_d.department_id = $this->dept AND u.id = ";

            // The case statement that shifts Chairs and Directors to the front of the line.
            $this->case = " (CASE WHEN title LIKE '%Chair%' THEN 0 WHEN title LIKE '%Director%' THEN 1 WHEN title NOT LIKE '%Chair%' OR title NOT LIKE '%Director%' THEN 2 END)";

            // The case statement that preferences someone's advising title over others.
            $this->advising_case = " (CASE WHEN title LIKE '%Advis%' THEN 0 WHEN title NOT LIKE '%Advis%' THEN 1 END)";

            $excluded_subs = $this->_excluded_subs();
        }
        

        /**
         * The meat and potatoes of this class. Takes an Enum argument and an arbitrary number of
         * extra arguments and returns an SQL query string for that particular type of query.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $type | The type of query to return. Corresponds to values in the
         * FacultyStaffQueryRef class.
         * @param mixed $args | an array of any extra arguments that the user passed to the
         * function.
         * 
         * @return mixed string/bool | Either returns the query string, or returns FALSE if there's an error.
         */
        public function get_query_str(int $type, ... $args) {

            switch( $type ) {

                case FSQEnum::DEPT_ALL :
                    return self::_dept_all( ... $args );

                case FSQEnum::DEPT_ADMIN :
                    return self::_dept_admin( ... $args );

                case FSQEnum::DEPT_STAFF :
                    return self::_dept_staff( ... $args );

                case FSQEnum::DEPT_SUB_GENERAL :
                    return self::_dept_sub_general( ... $args );

                case FSQEnum::DEPT_USER :
                    return self::_dept_user( ... $args );

                case FSQEnum::USER_OFFICE :
                    return self::_user_office( ... $args );

                case FSQEnum::USER_EDU :
                    return self::_user_edu( ... $args );

                case FSQEnum::USER_PUB :
                    return self::_user_pub( ... $args );
                
                case FSQEnum::TERM_GET :
                    return self::_term_get( ... $args );

                case FSQEnum::COURSE_LIST :
                    return self::_course_list( ... $args );

                case FSQEnum::ACAD_CATS :
                    return self::_dept_sub_categories( ... $args );

                case FSQEnum::DEPT_LIST :
                    return self::_dept_list( ... $args );

                case FSQEnum::EXCL_SUBS :
                    return self::_excluded_subs( ... $args );

                default:
                    return FALSE;
            }
        }

        /**
         * The standard query, for displaying the A-Z List entry.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param array $args | Any extra arguments passed to the function. Ignored here.
         * 
         * @return string (anon) | The baseline query string, which pulls everyone in the department.
         */
        private function _dept_all( ... $args ) : string {
            $base = $this->gen_query . " ORDER BY" . $this->case . ", u.lname";

            return "SELECT * FROM ( $base ) AS x GROUP BY x.lname";
        }


        /**
         * Gets the adminsitrative staff, and puts anyone with "Director" in their title first.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param array $args | Any extra arguments passed to the function. Ignored here.
         * 
         * @return string (anon) | The administrative staff query string.
         */
        private function _dept_admin( ... $args ) : string {
            $base = $this->gen_query . " AND t.title_group IN( 'Administrative Faculty', 'Chair', 'Staff' ) ORDER BY $this->case, u.lname";

            return "SELECT * FROM ( $base ) AS x GROUP BY $this->case, x.lname";
        }


        /**
         * Gets Advising staff.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param array $args | Anny extra arguments passed to the function. Ignored here.
         * 
         * @return string (anon) | The Advising staff query string.
         */
        private function _dept_staff( ... $args ) : string {
            $base = $this->gen_query . " AND (u_d.title_id IN( " . implode( ", ", $this->advisor_positions ) . " ) OR t.description LIKE '%Advis%') ORDER BY $this->advising_case, u.lname";

            return "SELECT * FROM ( $base ) AS x GROUP BY x.lname";
        }


        /**
         * Gets a string for any other sub department.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $sub_dept | The sub-department ID number.
         * @param array $args | Any extra arguments passed to the function. Ignored here.
         * 
         * @return string (anon) | The query screen for any other subdepartment.
         */
        private function _dept_sub_general( int $sub_dept, ... $args ) : string {
            $base = $this->query_base . " AND a.academic_id = $sub_dept ORDER BY $this->case, u.lname";

            return "SELECT * FROM ( $base ) AS x GROUP BY x.lname ORDER BY $this->case";
        }


        /**
         * Get an individual user's information.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $user_id | The user's User ID, so we can access their entry quickly.
         */
        private function _dept_user( int $user_id, ... $args ) : string {
            return $this->user_query . $user_id . " LIMIT 1";
        }


        /**
         * Get specific office number/location.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $room_id | The ID number of the room in question.
         * @param array $args | Any extra arguments passed to the function. Ignored here.
         * 
         * @return string (anon) | The query string to pull a particular office number.
         */
        private function _user_office( string $room_id, ... $args ) : string {
            return "SELECT room_number, buildings.short_description, buildings.building_number FROM rooms LEFT JOIN buildings ON building_id = buildings.id WHERE rooms.id = $room_id";
        }


        /**
         * Gets the user's education history.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $user_id | the ID of the requested User.
         * @param array $args | Any extra aruments passed to the function. Ignored here.
         * 
         * @return string (anon) | The query to get the user's education background.
         */
        private function _user_edu( int $user_id, ... $args ) : string {
            return "SELECT * FROM education LEFT JOIN degrees ON education.degrees_id = degrees.id WHERE user_id = $user_id ORDER BY year DESC";
        }
        

        /**
         * Gets the user's listed publication credits.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $user_id | The ID of the User we're looking for.
         * @param bool $approved | Whether or not the requested publications have been approve. 
         * 
         * @return string (anon) | The query to select a user's publications.
         */
        private function _user_pub( int $user_id, bool $approved = FALSE, ... $args ) : string {
            return "SELECT publications.id, photo_path, forthcoming, DATE_FORMAT( publish_date, '%M %Y' ) AS pubdate, citation, plural_description AS pubtype FROM publications LEFT JOIN publications_categories ON publications.publication_id = publications_categories.id WHERE user_id = $user_id AND approved = " . ( $approved ? "1" : "0") . " ORDER BY level, pubtype, publish_date DESC, citation";
        }

        
        /**
         * Gets the terms for creating a course list.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param array $args | Any extra arguments passed to the function. Ignored here.
         * 
         * @return string (anon) | The query to select the correct terms.
         */
        private function _term_get( ... $args ) : string {
            
            $sql_start = "SELECT DISTINCT term, term, CAST( SUBSTRING( term, LOCATE( ' ', term ) ) AS UNSIGNED ) + CAST( IF( SUBSTRING_INDEX( term, ' ', 1 ) = 'Fall', 1, 0 ) AS UNSIGNED ) AS ordering FROM courses WHERE term != CONCAT( 'Summer ', ( YEAR( NOW() ) + 1 ) )";

            $sql_end = " ORDER BY ordering DESC, term DESC";
            
            $year = date('Y');
            $today = strtotime( date('d.m.') . $year );
            $start = strtotime( "05.03.$year" );

            if( $today > $start )
                return $sql_start . $sql_end . " LIMIT 0, 5";
            
            else
                return "$sql_start AND term != CONCAT( 'Summer ', YEAR( NOW() ) ) AND term != CONCAT( 'Fall ', YEAR( NOW() ) ) AND term != CONCAT( 'Spring ', ( YEAR( NOW() ) + 1 ) )$sql_end";
        }


        /**
         * Gets a list of courses for an individual faculty entry.
         * 
         * @author Mike W. Leavitt
         * @since 1.0.0
         * 
         * @param int $user_id | The id of the individual faculty member.
         * @param string $term | The terms we're looking for.
         * @param string $aux | Extra conditionals for the term query.
         * @param string career | The level of courses, if any. Defaults to an empty string.
         * @param array $args | Any extra arguments. Ignored here.
         * 
         * @return string (anon) | The query to get the courses.
         */
        private function _course_list( int $user_id, string $term, string $aux, string $career = "", ... $args ) : string {

            if( !strcasecmp( $career, 'UGRD' ) )
                $career = " AND career = 'UGRD'";
            else if ( !strcasecmp( $career, 'GRAD' ) )
                $career = " AND career = 'GRAD'";
            
            return "SELECT courses.id, number, IF( description IS NULL, \"No Description Available\", description ) AS description, CONCAT( prefix, catalog_number ) AS catalogref, syllabus_file, term, section, title, instruction_mode, session, CONCAT( meeting_days, ' ', class_start, ' - ', class_end ) AS dateandtime FROM courses LEFT JOIN users ON courses.user_id = users.id WHERE $term$aux AND ( user_id = $user_id OR suser_id = $user_id )$career ORDER BY term, catalogref, title, number";
        }


        private function _dept_sub_categories( ... $args ) {

            return "SELECT id, `description` FROM cah.academic_categories AS a_c WHERE department_id = $this->dept ORDER BY `description`";
        }


        private function _dept_list( ... $args ) {

            return "SELECT id, short_description FROM cah.departments WHERE id NOT IN(99, 50, 36, 47, 30, 31, 32, 33, 34, 35)";
        }


        private function _excluded_subs( ... $args ) : string {

            return "SELECT id FROM (SELECT d_s.id, count(u.emplid) AS empl_count FROM cah.departments_sub AS d_s LEFT JOIN cah.users_departments AS u_d ON d_s.department_id = u_d.department_id LEFT JOIN cah.users AS u ON u.id = u_d.user_id WHERE empl_count > 0) AS non_empty_subs WHERE department_id = $this->dept ORDER BY id";
        }
    }
}
?>