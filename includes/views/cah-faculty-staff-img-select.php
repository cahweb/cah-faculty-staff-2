<?php
/**
 * Provides the HTML for the "Headshot Image Shape" select box.
 * 
 * @author Mike W. Leavitt
 * @version 1.0.0
 */

require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/config/cah-faculty-staff-config.php';

use CAH_FacultyStaffConfig as FSCfg;

$img_type = get_option( FSCfg::get_opt_prefix() . 'img_type', "WTAF" );

$img_options = array(
    array(
        'text' => 'Circular',
        'name' => 'circle'
    ),
    array(
        'text' => 'Rounded Square',
        'name' => 'round-square'
    )
);
?>

<select name="<?= FSCfg::get_opt_prefix() . 'img_type' ?>">
    <?php foreach( $img_options as $option ) : ?>
    <option value="<?= $option['name'] ?>"<?= strcmp( $img_type, $option['name'] ) == 0 ? ' selected="selected"' : "" ?>><?= $option['text'] ?></option>
    <?php endforeach; ?>
</select>