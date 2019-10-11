<?php
/**
 * This provides the HTML for the list of available departments.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/cah-faculty-staff-helper.php';
require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/cah-faculty-staff-query-ref.php';
require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/config/cah-faculty-staff-config.php';

use CAH_FacultyStaffHelper as FSH;
use CAH_FacultyStaffQueryRef as FSQEnum;
use CAH_FacultyStaffConfig as FSConfig;
?>
<div class="dept-container">
    <?php
    $result = FSH::query( FSQEnum::DEPT_LIST );
    $current = intval( get_option( FSConfig::get_opt_prefix() . 'dept' ) );
    while( $row = mysqli_fetch_assoc( $result ) ) :
    ?>
    <div class="dept-item">
        <input type="radio" 
            name="<?= FSConfig::get_opt_prefix() . "dept" ?>" 
            id="<?= str_replace( " ", "-", $row['short_description'] ) ?>" 
            value="<?= $row['id'] ?>" 
            <?= intval( $row['id'] ) == $current ? "checked" : "" ?>
        >
        <label for="<?= str_replace( " ", "-", $row['short_description'] ) ?>">
            <?= $row['short_description'] ?>
        </label>
    </div>
    <?php endwhile; ?>
</div>