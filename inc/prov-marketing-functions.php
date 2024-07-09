<?php

/**
 * Responsible for reporting functions
 */


function get_user_single_course_overview($user_id, $course_id)
{

    $status                 = array();
    $status['completed']    = __('Completed', 'aloa-agency-learndash-reporting-tool');
    $status['notcompleted'] = __('Not Completed', 'aloa-agency-learndash-reporting-tool');

    // Get Lessons
    $lessons_list       = learndash_get_course_lessons_list($course_id, $user_id, array('per_page' => -1));
    $course_quiz_list   = array();
    $course_quiz_list[] = learndash_get_course_quiz_list($course_id);

    $course_label = \LearnDash_Custom_Label::get_label('course');

    $lessons      = array();
    $topics       = array();
    $lesson_names = array();
    $topic_names  = array();
    $quiz_names   = array();

    $lesson_order = 0;
    $topic_order  = 0;
    foreach ($lessons_list as $lesson) {

        $lesson_names[$lesson['post']->ID] = $lesson['post']->post_title;
        $lessons[$lesson_order]            = array(
            'name'   => $lesson['post']->post_title,
            'status' => $status[$lesson['status']],
        );

        $course_quiz_list[] = learndash_get_lesson_quiz_list($lesson['post']->ID, $user_id, $course_id);
        $lesson_topics      = learndash_get_topic_list($lesson['post']->ID, $course_id);

        foreach ($lesson_topics as $topic) {

            $course_quiz_list[] = learndash_get_lesson_quiz_list($topic->ID, $user_id, $course_id);

            $topic_progress = learndash_get_course_progress($user_id, $topic->ID, $course_id);

            $topic_names[$topic->ID] = $topic->post_title;

            $topics[$topic_order] = array(
                'name'              => $topic->post_title,
                'status'            => $status['notcompleted'],
                'associated_lesson' => $lesson['post']->post_title,
            );

            if ((isset($topic_progress['posts'])) && (!empty($topic_progress['posts']))) {
                foreach ($topic_progress['posts'] as $topic_progress) {

                    if ($topic->ID !== $topic_progress->ID) {
                        continue;
                    }

                    if (1 === $topic_progress->completed) {
                        $topics[$topic_order]['status'] = $status['completed'];
                    }
                }
            }
            $topic_order++;
        }
        $lesson_order++;
    }

    global $wpdb;

    // Assignments
    $assignments            = array();
    $sql_string             = "
    SELECT post.ID, post.post_title, post.post_date, postmeta.meta_key, postmeta.meta_value
    FROM $wpdb->posts post
    JOIN $wpdb->postmeta postmeta ON post.ID = postmeta.post_id
    WHERE post.post_status = 'publish' AND post.post_type = 'sfwd-assignment'
    AND post.post_author = $user_id
    AND ( postmeta.meta_key = 'approval_status' OR postmeta.meta_key = 'course_id' OR postmeta.meta_key LIKE 'ld_course_%' )";
    $assignment_data_object = $wpdb->get_results($sql_string);

    foreach ($assignment_data_object as $assignment) {

        // Assignment List
        $data               = array();
        $data['ID']         = $assignment->ID;
        $data['post_title'] = $assignment->post_title;

        $assignment_id                                = (int) $assignment->ID;
        $rearranged_assignment_list[$assignment_id] = $data;

        // User Assignment Data
        $assignment_id = (int) $assignment->ID;
        $meta_key      = $assignment->meta_key;
        $meta_value    = (int) $assignment->meta_value;

        $date = learndash_adjust_date_time_display(strtotime($assignment->post_date));

        $assignments[$assignment_id]['name']           = '<a target="_blank" href="' . get_edit_post_link($assignment->ID) . '">' . $assignment->post_title . '</a>';
        $assignments[$assignment_id]['completed_date'] = $date;
        $assignments[$assignment_id][$meta_key]      = $meta_value;
    }

    foreach ($assignments as $assignment_id => &$assignment) {
        if (isset($assignment['course_id']) && $course_id !== (int) $assignment['course_id']) {
            unset($assignments[$assignment_id]);
        } else {
            if (isset($assignment['approval_status']) && 1 == $assignment['approval_status']) {
                $assignment['approval_status'] = __('Approved', 'aloa-agency-learndash-reporting-tool');
            } else {
                $assignment['approval_status'] = __('Not Approved', 'aloa-agency-learndash-reporting-tool');
            }
        }
    }

    // Quizzes Scores Avg
    global $wpdb;

    $q = "SELECT a.activity_id, a.course_id, a.post_id, a.activity_status, a.activity_completed, m.activity_meta_value as activity_percentage
        FROM {$wpdb->prefix}learndash_user_activity a
        LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta m ON a.activity_id = m.activity_id
        WHERE a.user_id = {$user_id}
        AND a.course_id = {$course_id}
        AND a.activity_type = 'quiz'
        AND m.activity_meta_key = 'percentage'";

    $user_activities = $wpdb->get_results($q);

    // Quizzes
    $quizzes = array();

    foreach ($course_quiz_list as $module_quiz_list) {
        if (empty($module_quiz_list)) {
            continue;
        }

        foreach ($module_quiz_list as $quiz) {

            if (isset($quiz['post'])) {

                $quiz_names[$quiz['post']->ID] = $quiz['post']->post_title;
                $certificate_link                = '';
                $certificate                     = learndash_certificate_details($quiz['post']->ID, $user_id);
                if (!empty($certificate) && isset($certificate['certificateLink'])) {
                    $certificate_link = $certificate['certificateLink'];
                }

                foreach ($user_activities as $activity) {

                    if ($activity->post_id == $quiz['post']->ID) {

                        $pro_quiz_id = learndash_get_user_activity_meta($activity->activity_id, 'pro_quizid', true);
                        if (empty($pro_quiz_id)) {
                            // LD is starting to deprecated pro quiz IDs from LD activity Tables. This is a back up if its not there
                            $pro_quiz_id = absint(get_post_meta($quiz['post']->ID, 'quiz_pro_id', true));
                        }

                        $statistic_ref_id = learndash_get_user_activity_meta($activity->activity_id, 'statistic_ref_id', true);
                        if (empty($statistic_ref_id)) {

                            if (class_exists('\LDLMS_DB')) {
                                $pro_quiz_master_table   = \LDLMS_DB::get_table_name('quiz_master');
                                $pro_quiz_stat_ref_table = \LDLMS_DB::get_table_name('quiz_statistic_ref');
                            } else {
                                $pro_quiz_master_table   = $wpdb->prefix . 'wp_pro_quiz_master';
                                $pro_quiz_stat_ref_table = $wpdb->prefix . 'wp_pro_quiz_statistic_ref';
                            }

                            // LD is starting to deprecated pro quiz IDs from LD activity Tables. This is a back up if its not there
                            $sql_str = $wpdb->prepare(
                                'SELECT statistic_ref_id FROM ' . $pro_quiz_stat_ref_table . ' as stat
                                INNER JOIN ' . $pro_quiz_master_table . ' as master ON stat.quiz_id=master.id
                                WHERE  user_id = %d AND quiz_id = %d AND create_time = %d AND master.statistics_on=1 LIMIT 1',
                                $user_id,
                                $pro_quiz_id,
                                $activity->activity_completed
                            );

                            $statistic_ref_id = $wpdb->get_var($sql_str);
                        }

                        $modal_link = '';

                        $svg_data = '<img width="24" height="24" src="' . ALOA_REPORTING_PLUGIN_URL . '/assets/images/report.png" alt="' . __('Student Report Image', 'aloa-agency-learndash-reporting-tool') . '" />';


                        if (empty($statistic_ref_id) || empty($pro_quiz_id)) {
                            if (!empty($statistic_ref_id)) {
                                $modal_link = '<a class="user_statistic"
                                     data-statistic_nonce="' . wp_create_nonce('statistic_nonce_' . $statistic_ref_id . '_' . get_current_user_id() . '_' . $user_id) . '"
                                     data-user_id="' . $user_id . '"
                                     data-quiz_id="' . $pro_quiz_id . '"
                                     data-ref_id="' . intval($statistic_ref_id) . '"
                                     data-uo-pro-quiz-id="' . intval($pro_quiz_id) . '"
                                     data-uo-quiz-id="' . intval($activity->post_id) . '"
                                     data-nonce="' . wp_create_nonce('wpProQuiz_nonce') . '"
                                     href="javascript:void(0)" title="' . $quiz['post']->post_title . '" > ' . $svg_data . ' <span>' . $quiz['post']->post_title . '</span></a><br />';
                            }
                        } else {
                            if (!empty($statistic_ref_id)) {
                                $modal_link = '<a class="user_statistic"
                                     data-statistic_nonce="' . wp_create_nonce('statistic_nonce_' . $statistic_ref_id . '_' . get_current_user_id() . '_' . $user_id) . '"
                                     data-user_id="' . $user_id . '"
                                     data-quiz_id="' . $pro_quiz_id . '"
                                     data-ref_id="' . intval($statistic_ref_id) . '"
                                     data-uo-pro-quiz-id="' . intval($pro_quiz_id) . '"
                                     data-uo-quiz-id="' . intval($activity->post_id) . '"
                                     data-nonce="' . wp_create_nonce('wpProQuiz_nonce') . '"
                                     href="javascript:void(0)" title="' . $quiz['post']->post_title . '">';
                                $modal_link .= $svg_data;
                                $modal_link .= '<span>' . $quiz['post']->post_title . '</span></a><br />';
                            }
                        }

                        $quizzes[] = array(
                            'name'             => $quiz['post']->post_title,
                            'score'            => $activity->activity_percentage,
                            'detailed_report'  => $modal_link,
                            'completed_date'   => array(
                                'display'   => learndash_adjust_date_time_display($activity->activity_completed),
                                'timestamp' => $activity->activity_completed,
                            ),
                            'certificate_link' => $certificate_link,
                        );
                    }
                }
            }
        }
    }

    $progress = learndash_course_progress(
        array(
            'course_id' => $course_id,
            'user_id'   => $user_id,
            'array'     => true,
        )
    );

    $completed_date = '';

    if (100 <= $progress['percentage']) {
        $progress_percentage = $progress['percentage'];
        $completed_timestamp = learndash_user_get_course_completed_date($user_id, $course_id);
        if (absint($completed_timestamp)) {
            $completed_date = learndash_adjust_date_time_display(learndash_user_get_course_completed_date($user_id, $course_id));
            $status         = __('Completed', 'aloa-agency-learndash-reporting-tool');
        } else {
            $status = __('In Progress', 'aloa-agency-learndash-reporting-tool');
        }
    } else {
        $progress_percentage = absint($progress['completed'] / $progress['total'] * 100);
        $status              = __('In Progress', 'aloa-agency-learndash-reporting-tool');
    }

    if (0 === $progress_percentage) {
        $progress_percentage = '';
        $status              = __('Not Started', 'aloa-agency-learndash-reporting-tool');
    } else {
        $progress_percentage = $progress_percentage;
    }

    // Column Quiz Average
    $course_quiz_average = get_avergae_quiz_result($course_id, $user_activities);

    $avg_score = '';

    if ($course_quiz_average) {
        /* Translators: 1. number percentage */
        $avg_score = sprintf(__('%1$s%%', 'aloa-agency-learndash-reporting-tool'), $course_quiz_average);
    }

    // TinCanny
    global $wpdb;
    $table           = $wpdb->prefix . 'uotincan_reporting';
    $q_tc_statements = "SELECT lesson_id as post_id, module_name, target_name, verb as action, result, xstored FROM $table WHERE user_id = {$user_id} AND course_id = {$course_id}";
    $statements_list = $wpdb->get_results($q_tc_statements);
    $statements      = array();
    foreach ($statements_list as $statement) {

        if (isset($quiz_names[(int) $statement->post_id])) {
            $related_post_name = $quiz_names[(int) $statement->post_id];
        } elseif (isset($topic_names[(int) $statement->post_id])) {
            $related_post_name = $topic_names[(int) $statement->post_id];
        } elseif (isset($lesson_names[(int) $statement->post_id])) {
            $related_post_name = $lesson_names[(int) $statement->post_id];
        } elseif ((int) $statement->post_id === $course_id) {
            $related_post_name = get_the_title($course_id);
        } else {
            $tmp_post = get_post($statement->post_id);

            if ($tmp_post) {
                $related_post_name = $tmp_post->post_title;
                $tmp_post          = null;
            } else {
                $related_post_name = __('Not Found: ', 'aloa-agency-learndash-reporting-tool') . $statement->post_id;
            }
        }

        $date = $statement->xstored;

        $statements[] = array(
            'related_post' => $related_post_name,
            'module'       => $statement->module_name,
            'target'       => $statement->target_name,
            'action'       => $statement->action,
            'result'       => $statement->result,
            'date'         => $date,
        );
    }

    return array(
        'completed_date'      => $completed_date,
        'progress_percentage' => $progress_percentage,
        'avg_score'           => $avg_score,
        'status'              => $status,
        'lessons'             => $lessons,
        'topics'              => $topics,
        'quizzes'             => $quizzes,
        'assigments'          => $assignments,
        'statements'          => $statements,
        'course_certificate'  => learndash_get_course_certificate_link($course_id, $user_id),
    );
}

function get_avergae_quiz_result($course_id, $user_activities)
{

    $quiz_scores = array();

    foreach ($user_activities as $activity) {

        if ($course_id == $activity->course_id) {

            if (!isset($quiz_scores[$activity->post_id])) {

                $quiz_scores[$activity->post_id] = $activity->activity_percentage;
            } elseif ($quiz_scores[$activity->post_id] < $activity->activity_percentage) {

                $quiz_scores[$activity->post_id] = $activity->activity_percentage;
            }
        }
    }

    if (0 !== count($quiz_scores)) {
        $average = absint(array_sum($quiz_scores) / count($quiz_scores));
    } else {
        $average = false;
    }

    return $average;
}


/**
 * Load group courses
 */

add_action('wp_ajax_aloa_load_group_courses', 'aloa_load_group_courses_callback');

function aloa_load_group_courses_callback()
{

    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {

        global $aloa_options;

        $group_id = isset($_POST['group_id']) && !empty($_POST['group_id']) ? $_POST['group_id'] : '';

        $reporting_page = isset($aloa_options['reporting-page']) ? $aloa_options['reporting-page'] : '';
        $auto_select_course = isset($aloa_options['course-id']) ? $aloa_options['course-id'] : '';


        if ($group_id != '') {

            $group_course_list = learndash_get_groups_courses_ids(get_current_user_id(), array($group_id));

            $resp_course = [];

            if (isset($group_course_list) && !empty($group_course_list)) {
                foreach ($group_course_list as $course__id) {

                    $course_list['id'] =  $course__id;
                    $course_list['text'] =  html_entity_decode(get_the_title($course__id));

                    $resp_course[] = $course_list;
                }

                echo json_encode(
                    array(
                        'success' => true,
                        'data' => $resp_course,
                        'auto_select' => $auto_select_course,
                        'group_title' => html_entity_decode(get_the_title($group_id)),
                        'link' => esc_url(
                            urldecode_deep(
                                add_query_arg(
                                    'group_id',
                                    $group_id,
                                    get_the_permalink($reporting_page)
                                )
                            )
                        )
                    )
                );
                wp_die();
            } else {

                echo json_encode(array('success' => false, 'message' => __('No course found', 'aloa-agency-learndash-reporting-tool')));
                wp_die();
            }
        } else {
            echo json_encode(array('success' => false, 'message' => __('Failed to load group id', 'aloa-agency-learndash-reporting-tool')));
            wp_die();
        }
    } else {

        echo json_encode(array('success' => false, 'message' => __('Security verification failed.', 'aloa-agency-learndash-reporting-tool')));
        wp_die();
    }
}

/**
 * Load group courses Students
 */

add_action('wp_ajax_aloa_load_group_courses_students', 'aloa_load_group_courses_students_callback');

function aloa_load_group_courses_students_callback()
{



    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {



        global $aloa_options;

        $group_id = isset($_POST['group_id']) && !empty($_POST['group_id']) ? $_POST['group_id'] : '';
        $course_id = isset($_POST['course_id']) && !empty($_POST['course_id']) ? $_POST['course_id'] : '';
        $num_of_record = isset($_POST['num_of_record']) && !empty($_POST['num_of_record']) ? $_POST['num_of_record'] : 10;
        $current_page = isset($_POST['page']) && !empty($_POST['page']) ? $_POST['page'] : 1;
        $std_sort = isset($_POST['sort']) && !empty($_POST['sort']) ? $_POST['sort'] : 'desc';

        $reporting_page = isset($aloa_options['reporting-page']) ? $aloa_options['reporting-page'] : '';

        if ($group_id != ''  && $course_id != '') {

            $student_data = aloa_get_group_course_students($group_id, $course_id);

            $progress_data = isset($student_data['progress_data']) ? $student_data['progress_data'] : array();

            $student_data_ = isset($student_data['student_data']) ? $student_data['student_data'] : array();

            $link_data = array(
                'group_id' => $group_id,
                'course_id' => $course_id,
                'per_page' => $num_of_record,
                'cpage' => $current_page,
                'sort' => $std_sort,
            );

            if (!empty($student_data_)) {

                $link_data['per_page'] = $num_of_record;
                $link_data['cpage'] = $current_page;
            }


            echo json_encode(
                array(
                    'success' => true,
                    'course_title' => html_entity_decode(get_the_title($course_id)),
                    'data' => aloa_students_record_table_data($student_data_, $num_of_record, $current_page, $std_sort),
                    'progress' => aloa_student_progress_data($progress_data, $group_id),
                    'link' => htmlspecialchars_decode(
                        add_query_arg(
                            $link_data,
                            get_the_permalink($reporting_page)
                        )
                    )
                )
            );

            wp_die();
        } else {


            $arg_arr = array();
            if ($group_id != '') {
                $arg_arr['group_id'] = $group_id;
            }

            if ($course_id != '') {
                $arg_arr['course_id'] = $course_id;
            }

            echo json_encode(
                array(
                    'success' => false,
                    'message' => __('Failed to load group or course id', 'aloa-agency-learndash-reporting-tool'),
                    'link' => htmlspecialchars_decode(add_query_arg($arg_arr, get_the_permalink($reporting_page)))
                )
            );
            wp_die();
        }
    } else {

        echo json_encode(array('success' => false, 'message' => __('Security verification failed.', 'aloa-agency-learndash-reporting-tool')));
        wp_die();
    }
}

/**
 * Get Group course Reporting data
 */

function aloa_get_group_course_students($group_id, $course_id)
{

    $student_data = [];
    $student_course_progress = [];

    $complete_count = 0;
    $inprogress_count = 0;
    $notstarted_count = 0;

    if ($group_id != '' && $course_id != '') {

        $group_users = learndash_get_groups_user_ids($group_id);

        if (empty($group_users)) {

            return array(
                'student_data' => array(),
                'progress_data' => array(),

            );
        }


        foreach ($group_users as $group_user_id) {

            $each_student = [];

            //$progress = learndash_get_user_group_progress($group_id, $group_user_id);
            $orer_view = get_user_single_course_overview($group_user_id, $course_id);

            // echo '<pre>';
            // print_r($progress);
            // echo '</pre>';

            // echo '<pre>';
            // print_r($orer_view);
            // echo '</pre>';

            // if (
            //     isset($progress) &&
            //     !empty($progress) &&
            //     $progress['course_ids'] &&
            //     !empty($progress['course_ids']) &&
            //     in_array($course_id, $progress['course_ids'])
            // ) {

            if (isset($orer_view['status']) && $orer_view['status'] == 'Completed') {
                $complete_count++;
            }
            if (isset($orer_view['status']) && $orer_view['status'] == 'Not Started') {
                $notstarted_count++;
            }
            if (isset($orer_view['status']) &&  $orer_view['status'] == 'In Progress') {
                $inprogress_count++;
            }

            $user_info = get_userdata($group_user_id);

            $each_student['name'] = $user_info->display_name;
            $each_student['progress'] = 0;

            if (isset($orer_view['quizzes']) && !empty($orer_view['quizzes'])) {
                $each_student['quizzes'] = $orer_view['quizzes'];
            }

            if (isset($orer_view['progress_percentage']) && !empty($orer_view['progress_percentage'])) {
                $each_student['progress'] = $orer_view['progress_percentage'];
            }
            //}

            if (!empty($each_student)) {
                $student_data[] = $each_student;
            }
        }

        $total_std = isset($student_data) && !empty($student_data) && is_array($student_data) && sizeof($student_data) > 0 ? count($student_data) : 0;

        if ($total_std > 0) {

            $student_course_progress = array(
                'completed' => round($complete_count / $total_std * 100, 0),
                'in_progress' => round($inprogress_count / $total_std * 100, 0),
                'not_started' => round($notstarted_count / $total_std * 100, 0),
                'total' => $total_std,
            );
        }
    }

    return array(
        'student_data' => $student_data,
        'progress_data' => $student_course_progress,

    );
}


/**
 * Student record table data
 */

function compareByPercentageAsc($a, $b)
{
    return $a['progress'] - $b['progress'];
}

function compareByPercentageDesc($a, $b)
{
    return $b['progress'] - $a['progress'];
}


function aloa_students_record_table_data($student_data, $num_of_record, $current_page, $std_sort = 'desc')
{


    $sort_icon = '';
    if ($std_sort == 'asc' && !empty($student_data)) {

        usort($student_data, 'compareByPercentageAsc');
        $sort_icon = '<i aria-hidden="true" class="fas fa-long-arrow-alt-down"></i>';
    } else {

        if (!empty($student_data)) {
            usort($student_data, 'compareByPercentageDesc');
        }

        $sort_icon = '<i aria-hidden="true" class="fas fa-long-arrow-alt-up"></i>';
    }


    $std_table_data = '';

    ob_start();

    $perPage = $num_of_record;
    $page = $current_page;

    $offset = ($page - 1) * $perPage;

    $totalPages = 1;

    if (!empty($student_data)) {
        $totalPages = ceil(count($student_data) / $perPage);

        $student_data = array_slice($student_data, $offset, $perPage);
    }

?>
    <table class="styled-table">
        <thead>
            <tr>
                <th><?php _e('Name', 'aloa-agency-learndash-reporting-tool') ?></th>
                <th class="aloa-progress-sort" data-sort-val="<?php echo $std_sort; ?>">
                    <?php _e('Progress', 'aloa-agency-learndash-reporting-tool') ?>
                    <span><?php echo $sort_icon; ?></span>
                </th>
                <th><?php _e('Responses', 'aloa-agency-learndash-reporting-tool') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($student_data)) {
                foreach ($student_data as $each_std) {
            ?>
                    <tr>
                        <td><?php echo esc_html($each_std['name']) ?></td>
                        <td><?php echo esc_html($each_std['progress']) . '%'; ?></td>
                        <td class="aloa-report-link">
                            <?php
                            if (isset($each_std['quizzes']) && !empty($each_std['quizzes'])) {

                                foreach ($each_std['quizzes'] as  $quizz) {

                                    if (isset($quizz['score']) && $quizz['score'] != '') {

                                        echo $quizz['detailed_report'];
                                    }
                                }
                            }
                            ?>
                        </td>
                    </tr>
                <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="3"><?php _e('There are no students enrolled in this course group.', 'aloa-agency-learndash-reporting-tool') ?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>


    <?php

    if (empty($student_data)) {
        $std_table_data = ob_get_clean();
        return $std_table_data;
    }

    ?>

    <div class="aloa-report-paging">

        <div class="aloa-per-page paging-item">
            <span><?php echo __('Show per page', 'aloa-agency-learndash-reporting-tool'); ?></span>
            <select name="aloa_num_of_record" class="aloa-num-of-record">
                <option <?php selected($perPage, 10) ?> value="10">10</option>
                <option <?php selected($perPage, 20) ?> value="20">20</option>
                <option <?php selected($perPage, 50) ?> value="50">50</option>
            </select>
        </div>
        <div class="aloa-paging-list paging-item">
            <ul>
                <?php
                $alo_page_list = range(1, $totalPages, 1);

                foreach ($alo_page_list as $page_count) {
                ?>
                    <li <?php echo isset($page) && $page == $page_count ? 'class="active"' : '';  ?>>
                        <a data-page-num="<?php echo absint($page_count); ?>" href="javascript:void(0)">
                            <?php echo absint($page_count); ?>
                        </a>
                    </li>
                <?php
                }
                ?>
            </ul>
        </div>
        <div class="aloa-next-previous paging-item">
            <?php
            $next_flag = FALSE;
            if ($totalPages > $current_page) {
                $next_flag = TRUE;
            }

            $previous_flag = TRUE;
            if (($current_page - 1) < 1) {
                $previous_flag = FALSE;
            }
            ?>

            <ul>
                <li><a <?php if ($previous_flag) { ?>data-page-num="<?php echo absint($current_page - 1) ?>" <?php } ?> href="javascript:void(0)">
                        < Prev</a>
                </li>

                <li><a <?php if ($next_flag) { ?>data-page-num="<?php echo absint($current_page + 1) ?>" <?php } ?> href="javascript:void(0)">Next ></a></li>

            </ul>
        </div>
    </div>

<?php
    $std_table_data = ob_get_clean();

    return $std_table_data;
}

/**
 * Get only teachers group ids
 */
function aloa_teacher_groups_ids()
{
    $group_ids = array();
    $child_ids = array();

    $user_id = get_current_user_id();

    $user_id = absint($user_id);
    if (!empty($user_id)) {

        $all_user_meta = get_user_meta($user_id);

        if (!empty($all_user_meta)) {
            foreach ($all_user_meta as $meta_key => $meta_set) {
                if ('learndash_group_leaders_' == substr($meta_key, 0, strlen('learndash_group_leaders_'))) {
                    $group_ids = array_merge($group_ids, $meta_set);
                }
            }
        }


        /**
         * filter group that have children (teacher group list)
         */


        if (!empty($group_ids)) {
            $group_ids = array_map('absint', $group_ids);
            $group_ids = array_diff($group_ids, array(0)); // Removes zeros.
            $group_ids = learndash_validate_groups($group_ids);
            if (!empty($group_ids)) {
                if (learndash_is_groups_hierarchical_enabled()) {
                    foreach ($group_ids as $group_id) {
                        $group_children = learndash_get_group_children($group_id);
                        if (!empty($group_children)) {
                            $child_ids = array_merge($child_ids, $group_children);
                        }
                    }
                }
            }
        }
    }

    return $child_ids;
}

/**
 * Overall Course progress 
 */

function aloa_student_progress_data($progress_data, $group_id)
{
    if (empty($progress_data)) {
        return;
    }
    $progress_data_return = '';
    ob_start();
?>
    <div class="aloa-progress-head">
        <h4><?php echo get_the_title($group_id); ?> Progress</h4>
    </div>
    <div class="aloa-progress-content">
        <div class="aloa-progress-item">
            <div class="aloa-prog-text">
                <h6>Not Started</h6>
                <h3><?php echo $progress_data['not_started']; ?>%</h3>
            </div>
            <div class="aloa-prog-donut">
                <div style="background:#fceddf;" data-progressBarColor="#fa981d" data-percent="<?php echo $progress_data['not_started']; ?>" class="aloa-std-progress"></div>
            </div>
        </div>
        <div class="aloa-progress-item">
            <div class="aloa-prog-text">
                <h6>In Progress</h6>
                <h3><?php echo $progress_data['in_progress']; ?>%</h3>
            </div>
            <div class="aloa-prog-donut">
                <div style="background:#fce8f2;" data-progressBarColor="#ed0c8f" data-percent="<?php echo $progress_data['in_progress']; ?>" class="aloa-std-progress"></div>
            </div>
        </div>

        <div class="aloa-progress-item">
            <div class="aloa-prog-text">
                <h6>Completed</h6>
                <h3><?php echo $progress_data['completed']; ?>%</h3>
            </div>
            <div class="aloa-prog-donut">
                <div style="background:#d4effd;" data-progressBarColor="#32adf0" data-percent="<?php echo $progress_data['completed']; ?>" class="aloa-std-progress"></div>
            </div>
        </div>
    </div>
    <?php

    $progress_data_return =  ob_get_clean();

    return $progress_data_return;
}

/**
 * Page redirection in case of non-loggedin users
 */

function contains_allowed_link($allowed_links)
{
    $current_url = $_SERVER['REQUEST_URI'];

    foreach ($allowed_links as $allowed_link) {
        if (strpos($current_url, $allowed_link) !== false) {
            return true;
        }
    }

    return false;
}


function aloa_redirect_non_logged_in_users()
{
    global $aloa_options;
    $aloa_options = get_option('aloa_options');

    /**
     * Page Redirection
     */

    $allowed_pages = isset($aloa_options['loggedin-allowed-pages']) && !empty($aloa_options['loggedin-allowed-pages']) ? $aloa_options['loggedin-allowed-pages'] : array();

    if (!empty($allowed_pages)) {
        if (!is_user_logged_in() && is_page($allowed_pages)) {
            wp_redirect(home_url());
            exit();
        }
    }

    /**
     * Educator-Survey Redirection
     */

    if (!is_user_logged_in() && is_page('educator-survey')) {
        wp_redirect(home_url('/login'));
        exit();
    }


    /**
     * Challenge Redirection
     */

    $ld_post_types = array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz');
    if ((is_singular($ld_post_types))) {
        if (!is_user_logged_in()) {
            wp_redirect(home_url());
            exit();
        }
    }

    /**
     * Loggedin redirection
     */

    if (is_user_logged_in()) {

        /**
         * Group Leader redirection
         */

        if (current_user_can('group_leader')) {
            if (is_page('my-account')) {
                wp_redirect(home_url('/toolkit'));
                exit();
            }
        }

        /**
         * Student redirection
         */

        if (current_user_can('student')) {

            $std_restrict_pages = isset($aloa_options['student-restrict-pages']) && !empty($aloa_options['student-restrict-pages']) ? $aloa_options['student-restrict-pages'] : array();


            /**
             * If a student has finished the course and received the badge.
             */

            // if (is_page('my-account') && current_user_can('student') && current_user_can('completed_fambiz')) {
            //     wp_redirect(home_url('/fambiz-survey'));
            //     exit();
            // }

            /**
             * If a user is a student and has access to the teacher specific pages.
             */

            if (current_user_can('student') && is_page($std_restrict_pages)) {
                wp_redirect(home_url('/my-account'));
                exit();
            }
        }
    }
}

add_action('template_redirect', 'aloa_redirect_non_logged_in_users');


/**
 * BadgeOS Confiction
 */
function aloa_remove_existing_badgeos_action()
{
    if (class_exists('BOS_SOCIAL_SHARING')) {

        remove_action('wp_head', 'badgeos_social_share_open_graph_metas', -99);

        add_action('wp_head', 'badgeos_social_share_open_graph_metas_');
        function badgeos_social_share_open_graph_metas_()
        {
            global $post;
            $badgeos_evidence_page_id   = get_option('badgeos_evidence_url');
            $post_id = $post->ID;

            $achievement_types = badgeos_get_achievement_types_slugs();
            $current_post_type = get_post_type($post_id);

            // add meta tags only on evidence and achievement_types page.

            // commented the if statement for adding meta tags to both badges and ranks

            //if ($post_id == $badgeos_evidence_page_id || in_array($current_post_type, $achievement_types)) {
            if (isset($_GET['bg'])) {
                $post_id = $_GET['bg'];
            }
            $post_url     = get_the_permalink($post_id);
            $post_title = sanitize_text_field(get_the_title($post_id));
            $post_image = get_the_post_thumbnail_url($post_id, 'full');
            $post_description = sanitize_text_field(get_post_content_of_achievement($post_id));
         ?>
            <!-- Start of Badgeos Social Sharing Meta tags -->
            <meta property="og:type" content="article" />
            <meta property="og:url" content="<?php echo esc_url($post_url); ?>" />
            <meta property="og:title" content="<?php echo esc_attr($post_title); ?>" />
            <meta property="og:description" content="<?php echo esc_attr($post_description); ?>" />
            <meta property="og:image" content="<?php echo esc_url($post_image); ?>" />
            <!-- End of Badgeos Social Sharing Meta tags -->
         <?php
            //}
        }
    }
}

add_action('plugins_loaded', 'aloa_remove_existing_badgeos_action', 99);