<?php

/**
 * Reporting Template
 */

get_header();

global $aloa_options;

$new_group_page = isset($aloa_options['new-group-page-id']) ? $aloa_options['new-group-page-id'] : '';
$reporting_page = isset($aloa_options['reporting-page']) ? $aloa_options['reporting-page'] : '';
$course_id = isset($aloa_options['course-id']) ? $aloa_options['course-id'] : '';



$group_list = aloa_teacher_groups_ids();

//$group_list = learndash_get_administrators_group_ids(get_current_user_id());

$group_id = isset($_GET['group_id']) && !empty($_GET['group_id']) ? $_GET['group_id'] : '';
$course_id = isset($_GET['course_id']) && !empty($_GET['course_id']) ? $_GET['course_id'] : '';
$per_page = isset($_GET['per_page']) && !empty($_GET['per_page']) ? $_GET['per_page'] : 10;
$current_page = isset($_GET['cpage']) && !empty($_GET['cpage']) ? $_GET['cpage'] : 1;

$group_users = array();
$student_data = array();


if ($group_id != '' && $course_id != '') {

    $student_data = aloa_get_group_course_students($group_id, $course_id);
}


?>
<div class="famb-hub-wrap">
    <div class="famb-hub-sidebar">
        <?php
        if (isset($group_list) && !empty($group_list)) {
        ?>
            <ul id="menu">
                <li id="aloa-teacher-groups">
                    <p><?php echo __('My Groups', 'aloa-agency-learndash-reporting-tool'); ?><span class="arrow"></span></p>
                    <ul style="display:<?php echo isset($group_id) && $group_id != '' ? 'block' : 'none'; ?>">
                        <?php
                        foreach ($group_list as  $each_group_id) {

                            $active_class = isset($group_id) && $group_id == $each_group_id ? ' class="active"' : '';

                        ?>
                            <li<?php echo $active_class; ?>>
                                <a class="aloa-load-group-course" href="javascript:void(0)" data-group-id="<?php echo esc_attr($each_group_id); ?>">
                                    <?php echo get_the_title($each_group_id); ?>
                                </a>
                </li>
            <?php
                        }
            ?>
            </ul>
            </li>
            </ul>
        <?php
        } else {
        ?>
            <p><?php _e('Seems you have not created any group or you are not permitted to access the group list.', 'aloa-agency-learndash-reporting-tool'); ?></p>
        <?php
        }
        ?>
    </div>
    <div class="famb-hub-content">

        <div class="famb-hub-header">

            <div class="famb-group-name">
                <h1><?php echo $group_id != '' ? get_the_title($group_id) : ''; ?></h1>
            </div>

            <div class="famb-group-create">
                <a href="<?php echo esc_url(get_the_permalink($new_group_page)); ?>" class="famb-group-new">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0,0,256,256" width="24px" height="24px">
                        <g fill="#ffffff" fill-rule="evenodd" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                            <g transform="scale(10.66667,10.66667)">
                                <path d="M11,2v9h-9v2h9v9h2v-9h9v-2h-9v-9z"></path>
                            </g>
                        </g>
                    </svg>
                    <span><?php _e('Add New Group', 'aloa-agency-learndash-reporting-tool') ?></span>
                </a>
            </div>

        </div>
        <div class="aloa-std-progress-wrap" style="display:<?php echo isset($student_data['progress_data']) && $student_data['progress_data'] != '' ? 'block' : 'none'; ?>">
            <?php
            if (isset($student_data['progress_data']) && $student_data['progress_data'] != '') {

                echo aloa_student_progress_data($student_data['progress_data'],$group_id);
            }
            ?>
        </div>

        <div class="famb-main-wrap">
            <?php
            $group_course_list = learndash_get_groups_courses_ids(get_current_user_id(), array($group_id));
            ?>
            <div class="famb-course-selection">
                <select name="course_id" class="aloa-course-dropdown" data-group-id="<?php echo $group_id; ?>">
                    <option value="">Select Course</option>
                    <?php
                    if (isset($group_course_list) && !empty($group_course_list)) {
                        foreach ($group_course_list as $course__id) {
                    ?>
                            <option <?php selected($course__id, $course_id, true); ?> value="<?php echo absint($course__id); ?>"><?php echo get_the_title($course__id); ?></option>
                    <?php
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="famb-hub-course">
                <div class="famb-course-head">
                    <!-- <h3 class="aloa-head-course"><?php //echo $course_id != '' ? get_the_title($course_id) : ''; ?></h3> -->
                    <h5 class="aloa-head-group"><?php echo $group_id != '' ? get_the_title($group_id) : ''; ?></h5>
                </div>
                <div class="famb-group-link">
                    <?php
                    $group_enrollment_link = '';
                    if (function_exists('famb_generate_group_enrolment_link')) {

                        $group_enrollment_link = famb_generate_group_enrolment_link($group_id);
                    }
                    ?>
                    <a href="javascript:void(0)" data-copy="<?php echo $group_enrollment_link; ?>" class="famb-group-copy">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="12" viewBox="0 0 24 12" id="link">
                            <g fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                                <g stroke="#000" stroke-width="2" transform="translate(-919 -1753)">
                                    <g transform="translate(920 1754)">
                                        <path d="M14 0h3a5 5 0 010 10h-3m-6 0H5A5 5 0 015 0h3M7 5h8"></path>
                                    </g>
                                </g>
                            </g>
                        </svg>
                        <span><?php _e('Copy Group Link', 'aloa-agency-learndash-reporting-tool') ?></span>
                    </a>
                </div>

            </div>

            <div class="famb-group-std-data">
                <?php
                if (isset($student_data['student_data']) && $student_data['student_data'] != '') {
                    echo aloa_students_record_table_data($student_data['student_data'], $per_page, $current_page);
                } else {
                    echo aloa_students_record_table_data(array(), $per_page, $current_page);
                }
                ?>
            </div>
        </div>
        <div class="aloa-reporting-loader-overlay">
            <div class="aloa-reporting-loader"></div>
        </div>
    </div>

</div>
<div id="wpProQuiz_user_overlay" style="display: none;">
    <div class="wpProQuiz_modal_window" style="padding: 20px; overflow: scroll;">
        <input type="button" value="<?php esc_html_e('X', 'aloa-agency-learndash-reporting-tool'); ?>" style="background-color:#000 !important;position: fixed; top: 48px; right: 70px; z-index: 160001;padding: 0;width: 30px;height: 30px;" id="wpProQuiz_overlay_close">

        <div id="wpProQuiz_user_content" style="margin-top: 20px;font-family: poppins;"></div>

        <div id="wpProQuiz_loadUserData" class="wpProQuiz_blueBox" style="display: none; margin: 50px;">
            <img alt="load" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" />
            <?php esc_html_e('Loading', 'aloa-agency-learndash-reporting-tool'); ?>
        </div>
    </div>
    <div class="wpProQuiz_modal_backdrop"></div>
</div>
<?php

get_footer();
