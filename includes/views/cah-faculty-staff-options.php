<?php
/**
 * The options page HTML.
 */


?>
<div class="wrap">
    <?php
    if( isset( $_GET['success'] ) ) {
        ?>
        <div class="notice notice-success is-dismissable">
            <p>Options updated successfully!</p>
        </div>
        <?php
    }
    ?>
    <h1><?= esc_html( get_admin_page_title( 'cah-faculty-staff-options' ) ); ?></h2>
    <form action="<?= esc_url( admin_url( 'options.php' ) ); ?>" method="post">
        <input type="hidden" name="action" value="faculty_staff_update_options">
        <?php wp_nonce_field( "faculty_staff_update_options", "update_options" ); ?>
        <?php
        settings_fields( 'cah_faculty_staff' );
        do_settings_sections( 'cah-faculty-staff-options' );
        submit_button();
        ?>
    </form>
</div>