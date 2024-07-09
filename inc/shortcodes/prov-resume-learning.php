<?php

/**
 * Aloa Resume Learning Shortcodes
 */

function aloa_resume_button_shortcode_callback($atts)
{
    global $aloa_options;
    $aloa_options = get_option('aloa_options');

    $course_id_ = isset($aloa_options['course-id']) && !empty($aloa_options['course-id']) ? $aloa_options['course-id'] : '';

    $atts = shortcode_atts(
        array(
            'url_only' => 'no',
            'btn_label' => 'Resume Learning',
            'course_id' => $course_id_,
        ),
        $atts,
        'aloa_resume_learning'
    );

    $url_only = $atts['url_only'];
    $btn_label = $atts['btn_label'];
    $course_id = $atts['course_id'];


    $resume_course_url = 'javascript:void(0)';
    $resume_course_title = get_the_title(get_the_ID());

    $resume_id = 0;

    if (is_user_logged_in()) {

        $user = wp_get_current_user();


        $all_course_steps = get_incomplete_course_steps($user->ID, $course_id);

        if (isset($all_course_steps) && !empty($all_course_steps)) {

            foreach ($all_course_steps as $parent_step => $child_step) {

                if (famb_have_child_completed($child_step)) {

                    foreach ($child_step as $chils_step_id => $chils_step_status) {

                        if ($chils_step_status === 0) {
                            $resume_id = $chils_step_id;
                            break;
                        }
                    }
                    if ($resume_id != 0) {
                        break;
                    }
                } else {
                    $resume_id = $parent_step;
                    break;
                }
            }
        }

        if ($resume_id != 0) {
            $resume_course_url = get_the_permalink($resume_id);
            $resume_course_title = get_the_title($resume_id);
        }
    }

    $output = '';

    if ($resume_id != 0) {

        $output .= '<div class="learndash-resume-button">';
        if ($url_only == 'yes') {
            $output .= $resume_course_url;
        } else {
            $output .= '<a href="' . $resume_course_url . '" title="Resume ' . $resume_course_title . '"> <input type="submit" value="' . $btn_label . '" class=""></a>';
            $output .= '<div class="resume-item-name">' . $resume_course_title . '</div>';
        }

        $output .= "</div>";
    } else {


        $check_incom_arr = learndash_user_progress_get_all_incomplete_steps($user->ID, $course_id);

        if (empty($check_incom_arr)) {

            if (function_exists('badgeos_get_user_achievements')) {
                $user_achievements     = badgeos_get_user_achievements(array('user_id' => $user->ID, 'no_step' => true));

                if (empty($user_achievements)) {

                    $output .= '<div class="btn-blue"><a class="elementor-button elementor-button-link elementor-size-sm" href="' . home_url('/fambiz-survey') . '">
                                    <span class="elementor-button-content-wrapper">
                                    <span class="elementor-button-text">Claim your badge!</span>
                                    </span>
                                </a></div>';
                }
            }
        }
    }

    return $output;
}
add_shortcode('aloa_resume_learning', 'aloa_resume_button_shortcode_callback');




function get_incomplete_course_steps($user_id, $course_id)
{

    $incomplete_steps = array();

    $course_steps = learndash_get_course_steps($course_id, array('sfwd-lessons'));

    foreach ($course_steps as  $lesson_ids__) {

        $child_arr = learndash_course_get_children_of_step($course_id, $lesson_ids__, '', 'ids', true);

        $child_steps = array();

        foreach ($child_arr as  $child_ids__) {

            if (learndash_user_progress_is_step_complete($user_id, $course_id, $child_ids__)) {
                $child_steps[$child_ids__] = 1;
            } else {
                $child_steps[$child_ids__] = 0;
            }
        }
        $incomplete_steps[$lesson_ids__] = $child_steps;
    }

    return $incomplete_steps;
}


function famb_have_child_completed($child_steps)
{

    if (empty($child_steps)) {

        return false;
    }

    foreach ($child_steps as $course_status) {
        if ($course_status === 1) {
            return true;
            break;
        }
    }

    return false;
}

/**
 * Student Restriction to visit completed course steps
 */

add_filter('template_redirect', 'learndash_student_redirect');

function learndash_student_redirect()
{
    global $aloa_options;

    $ld_post_types = array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz');

    if (is_user_logged_in() && current_user_can('student') && is_singular($ld_post_types)) {

        $single_step_id = get_the_ID();

        $aloa_options = get_option('aloa_options');
        $course_id = isset($aloa_options['course-id']) && !empty($aloa_options['course-id']) ? $aloa_options['course-id'] : '';

        $user = wp_get_current_user();
        $user_id = $user->ID;

        /**
         * Redirect to fefault if the step is not completed
         */

        if (!learndash_user_progress_is_step_complete($user_id, $course_id, $single_step_id)) {
            return;
        }

        $resume_id = 0;

        $all_course_steps = get_incomplete_course_steps($user->ID, $course_id);

        if (isset($all_course_steps) && !empty($all_course_steps)) {

            foreach ($all_course_steps as $parent_step => $child_step) {

                if (famb_have_child_completed($child_step)) {

                    foreach ($child_step as $chils_step_id => $chils_step_status) {

                        if ($chils_step_status === 0) {
                            $resume_id = $chils_step_id;
                            break;
                        }
                    }
                    if ($resume_id != 0) {
                        break;
                    }
                } else {
                    $resume_id = $parent_step;
                    break;
                }
            }
        }

        if ($resume_id != 0) {
            $redirect_url = get_the_permalink($resume_id);
            wp_redirect($redirect_url);
            exit();
        }

    }
}
