<?php
require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/cah-faculty-staff-helper.php';

use CAH_FacultyStaffHelper as FSH;
?>
<div class="row">
<?php

?>
<?php while( $row = mysqli_fetch_assoc( $result ) ) : // Iterate through entries ?>
<?php //foreach( $result as $row ) : ?>
    <div class="col-lg-6 col-md-12">
        <div class="cah-staff-list">
            <a href="<?= home_url( 'faculty-staff' ) ?>?id=<?= $row['id'] ?>">
                <div class="staff-list">
                <?php if( $format ) : // Show photo if we're not in the A-Z List ?>
                    <div class="media">
                    <?= FSH::get_staff_img( 
                        $row['fullname'],
                        ( !empty( $row['photo_path'] ) ? $row['photo_path'] : 'profilephoto.jpg' ),
                        ( !empty( $row['photo_extra'] ) ? $row['photo_extra'] : "" ),
                        5 ); // Grab the staff image, if available. ?>
                        <div class="media-body">
                <?php endif; // We'll show the name regardless ?>
                        <p><strong><?= $row['fullname'] ?></strong></p>
                        <br />
                <?php
                    // Get the user's title
                    $title = !empty( $row['title_dept_short'] ) || !empty( $row['title_dept'] ) ? ( !empty( $row['title_dept_short'] ) ? $row['title_dept_short'] : $row['title_dept'] ) : $row['title'];

                    // Back to non-A-Z List stuff.
                    if( $format ) :
                ?>
                        <div class="fs-list">
                            <small>
                                <p class="staff-title">
                                <?php if( $title == 'Director' ) : // Directors at the front ?>
                                    <span class="fa fa-star mr-1 text-primary" aria-hidden="true"></span>
                                <?php endif; ?>
                                    <em><?= $title ?></em>
                                </p>
                                <?= $row['email'] ?><br />
                                <?= isset( $row['phone'] ) ? FSH::format_phone_us( $row['phone'] ) : "" ?>
                                <?php
                                // Add an interests field, if the user wanted one in the options.
                                if( $include_interests ) : 
                                    if( !empty( $row['interests'] ) || !empty( $row['prog_interests'] ) ) :

                                        $interests = html_entity_decode( !empty( $row['interests'] ) ? $row['interests'] : $row['prog_interests'], ENT_QUOTES, "utf-8" );

                                        // Processess, normalizes, and/or abbreviates the interests.
                                        $interests_out = FSH::interests_short( $interests );
                                        ?>
                                        <p class="fs-interest"><em>Interests: </em><?= $interests_out ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </small>
                        </div>
                <?php else : ?>
                    <small>
                        <span class="staff-title"><em><?= $title ?></em></span><br />
                        <?= $row['email'] ?><br />
                        <?= isset( $row['phone'] ) ? FSH::format_phone_us( $row['phone'] ) : "" ?>
                    </small>
                <?php endif; ?>
                <?php if( $format ) : // Closing out the extra divs. ?>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </a>
        </div>
    </div>
<?php //endforeach; ?>
<?php endwhile; ?>
</div>