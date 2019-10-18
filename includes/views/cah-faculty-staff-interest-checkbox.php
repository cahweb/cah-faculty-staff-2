<?php
/**
 * Provides the HTML for the "Display Interests" checkbox.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/config/cah-faculty-staff-config.php';

use CAH_FacultyStaffConfig as FSConfig;

$show_interests = intval( get_option( FSConfig::get_opt_prefix() . 'interests' ) );
?>

<input type="checkbox" 
    name="<?= FSConfig::get_opt_prefix() . "interests" ?>" 
    id="show-interests" 
    value="1" 
    <?= $show_interests ? " checked" : "" ?>
>

