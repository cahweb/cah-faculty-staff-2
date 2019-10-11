<?php
require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/cah-faculty-staff-helper.php';
require_once CAH_FACULTY_STAFF__PLUGIN_DIR . 'includes/cah-faculty-staff-query-ref.php';

use CAH_FacultyStaffHelper as FSH;
use CAH_FacultyStaffQueryRef as FSQEnum;
?>
<div class="staff-detail row flex-column">
    <div class="media">
        <?= FSH::get_staff_img( // Get the faculty/staff portrait.
            $row['fullname'],
            ( !empty( $row['photo_path'] ) ? $row['photo_path'] : "profilephoto.jpg" ),
            ( !empty( $row['photo_extra'] ) ? $row['photo_extra'] : "" ),
            2
        ); ?>
        <div class="media-body">
            <h4><?= $row['fullname'] ?></h4>
            <span><em>
                <?= !empty( $row['title_dept'] ) ? $row['title_dept'] : $row['title'] ?>
            </em></span>
            <br />
            <a href="mailto:<?= $row['email'] ?>"><?= $row['email'] ?></a>
            <br />
        <?php if( !empty( $row['phone'] ) ) : ?>
            <a href="tel:<?= $row['phone'] ?>"><?= FSH::format_phone_us( $row['phone'] ) ?></a>
            <br />
        <?php endif; ?>
        <?php if( !empty( $row['office'] ) ) : ?>
            Office Hours: <?= $row['office'] ?>
            <br />
        <?php endif; ?>
        <?php if( !empty( $row['room_id'] ) ) :
            
            // Get the room information
            $loc = mysqli_fetch_assoc( FSH::query( FSQEnum::USER_OFFICE, $row['room_id'] ) );

            if( !empty( $loc['building_number'] ) ) : ?>
            Campus Location: <a href="https://map.ucf.edu/locations/<?= $loc['building_number'] ?>" target="_blank">
            <?php endif; ?>
                <?= $loc['short_description'] . $loc['room_number'] ?>
            <?php if( !empty( $loc['building_number'] ) ) : ?>
                </a>
            <?php endif; ?>
                <br />
        <?php elseif( $row['location'] ) : ?>
            Campus Location: <?= $row['location'] ?><br />
        <?php endif; ?>
        <?php if( !empty( $row['has_cv'] ) ) : ?>
            <a href="https://cah.ucf.edu/common/files/cv/<?= $row['id'] ?>.pdf">View CV</a><br />
        <?php elseif( !empty( $row['resume_path'] ) ) : ?>
            <a href="<?= $row['resume_path'] ?>"<?= stripos( $row['homepage'], "ucf.edu" ) !== FALSE ? ' rel="external"' : '' ?>>View CV</a><br />
        <?php endif; ?>
        </div>
    </div>
<?php if( !empty( $row['biography'] ) ) : ?>
    <div class="pt-2">
        <?= $row['biography'] ?>
    </div>
<?php endif; ?>
<?php if( ( $edu = FSH::query( FSQEnum::USER_EDU, intval( $row['id'] ) ) ) && $edu->num_rows > 0 ) : ?>
    <h3 class="heading-underline">Education</h3>
        <ul>
    <?php while( $edu_row = mysqli_fetch_assoc( $edu ) ) : ?>
            <li>
                <?= trim( $edu_row['short_description'] ) 
                    . ( !empty( $edu_row['field'] ) ? " in " . trim( $edu_row['field'] ) : "" )
                    . ( !empty( $edu_row['institution'] ) ? " from " . trim($edu_row['institution'] ) : "" )
                    . ( !empty( $edu_row['year'] ) ? " ({$edu_row['year']})" : "" )
                ?>
            </li>
    <?php endwhile; ?>
        </ul>
<?php endif; ?>
<?= isset( $row['interests'] ) ? FSH::maybe_print( $row['interests'], "Research Interests" ) : "" ?>
<?= isset( $row['research'] ) ? FSH::maybe_print( $row['research'], "Recent Research Activities" ) : "" ?>
<?php if( ( $pubs = FSH::query( FSQEnum::USER_PUB, intval( $row['id'], TRUE ) ) ) && $pubs->num_rows > 0 ) : ?>
    <h3 class="heading-underline">Selected Publications</h3>
    <?php 
    $pub_type = "";
    $i = 0;
    while( $pub_row = mysqli_fetch_assoc( $pubs ) ) : ?>
    <?php if( $i != 0 && strcmp( $pub_type, $pub_row['pubtype'] ) ) : ?>
        <ul>
    <?php endif; ?>
    <?php if( strcmp( $pub_type, $pub_row['pubtype'] ) ) : ?>
    <h4 class="pt-4"><?= $pub_row['pubtype'] ?></h4>
        <ul>
    <?php endif; ?>
            <li>
                <?= ( $pub_row['forthcoming'] ? "<em>Forthcoming</em> " : "" )
                    . $pub_row['publish_date'] . " "
                    . html_entity_decode( $pub_row['citation'], ENT_QUOTES, "utf-8" )
                ?>
            </li>
    <?php 
        $i++;
        $pub_type = $pub_row['pubtype'];
    ?>
    <?php endwhile; ?>
        </ul>
<?php endif; ?>
</div>