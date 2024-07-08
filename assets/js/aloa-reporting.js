jQuery(function ($) {

    /**
 * Responsible for reporting scripts
 */



    $(document).ready(function () {

        /**
         * ProgressBar
         */

        if (jQuery(".aloa-std-progress").length > 0) {

            jQuery(".aloa-std-progress").percircle();
        }



        /**
         * Courses Dropdown Select2
         */

        if (jQuery('.aloa-course-dropdown').length > 0) {
            var abc = jQuery('.aloa-course-dropdown').select2({
                placeholder: "Select course",
                allowClear: true,
                //minimumInputLength: 2,
                dropdownCssClass: "alao-select2-course",
                width: "40%",
                theme: "classic",
            });

            console.log(abc);

        }

        /**
         * Get Student Data by courses
         */

        $(document).on('change', '.aloa-course-dropdown', function (e) {

            let course_id = jQuery(this).val();
            let group_id = jQuery(this).attr('data-group-id');

            jQuery('.aloa-reporting-loader-overlay').css('display', 'flex');

            console.log(course_id);
            console.log(group_id);

            $.ajax({
                type: 'POST',
                data: {
                    nonce: aloa_globals.ajax_nonce,
                    action: 'aloa_load_group_courses_students',
                    group_id: group_id,
                    course_id: course_id,
                },
                dataType: 'json',
                url: aloa_globals.ajax_url,
                crossDomain: true,
                cache: false,
                async: true,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aloa_globals.ajax_nonce);
                },
            }).done(function (response) {

                jQuery('.aloa-reporting-loader-overlay').css('display', 'none');


                if (response.success !== undefined && response.success) {

                    jQuery('.famb-group-std-data').html(response.data);
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html(response.course_title);

                    if (response.progress != null) {
                        jQuery('.aloa-std-progress-wrap').show();
                        jQuery('.aloa-std-progress-wrap').html(response.progress);
                        $(".aloa-std-progress").percircle();
                    } else {
                        jQuery('.aloa-std-progress-wrap').hide();
                    }



                } else {

                    jQuery('.famb-group-std-data tbody').html('<tr><td colspan="3">There are no students enrolled in this course group</td></tr>');
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html('');

                    alert(response.message);

                }

            });

        });

        /**
         * Get group Courses
         */

        $(document).on('click', 'a.aloa-load-group-course', function (e) {

            e.preventDefault();

            let group_id = jQuery(this).attr('data-group-id');

            jQuery('.aloa-reporting-loader-overlay').css('display', 'flex');

            jQuery(this).closest('ul').children().removeClass('active');
            jQuery(this).parent().addClass('active');

            $.ajax({
                type: 'POST',
                data: {
                    nonce: aloa_globals.ajax_nonce,
                    action: 'aloa_load_group_courses',
                    group_id: group_id,
                },
                dataType: 'json',
                url: aloa_globals.ajax_url,
                crossDomain: true,
                cache: false,
                async: true,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aloa_globals.ajax_nonce);
                },
            }).done(function (response) {

                jQuery('.aloa-reporting-loader-overlay').css('display', 'none');

                if (response.success !== undefined && response.success) {

                    let aloa_course_dp = jQuery('.aloa-course-dropdown').select2({
                        placeholder: "Select course",
                        allowClear: true,
                        dropdownCssClass: "alao-select2-course",
                        width: "40%",
                        data: response.data,
                        theme: 'classic',
                    });

                    jQuery('.aloa-course-dropdown').attr('data-group-id', group_id);
                    window.history.pushState(null, null, decodeURIComponent(response.link));
                    jQuery('.aloa-head-group').html(response.group_title);


                    aloa_course_dp.select2("trigger", "select", {
                        data: { id: response.auto_select }
                    });

                    //aloa_course_dp.select2("open");

                } else {

                    alert(response.message);

                }

            });

        });

        /**
         * Group list toggle
         */

        if (jQuery('#aloa-teacher-groups').length > 0) {

            var groupsMenu = document.getElementById('aloa-teacher-groups');

            groupsMenu.addEventListener('click', function (e) {

                e.preventDefault();

                var childMenu = groupsMenu.querySelector('ul');
                childMenu.style.display = (childMenu.style.display === 'none' || childMenu.style.display === '') ? 'block' : 'none';

                var arrow = groupsMenu.querySelector('.arrow i');
                arrow.classList.toggle('down');

            });

        }

        /**
         * Student report Data
         */

        $(document).on('click', 'a.user_statistic', function (e) {

            var refId = jQuery(this).attr('data-ref_id');
            var quizId = jQuery(this).attr('data-quiz_id');
            var userId = jQuery(this).attr('data-user_id');
            var report_title = jQuery(this).attr('title');

            var statistic_nonce = jQuery(this).attr('data-statistic_nonce');
            var post_data = {
                action: 'wp_pro_quiz_admin_ajax_statistic_load_user',
                func: 'statisticLoadUser',
                data: {
                    quizId,
                    userId,
                    refId,
                    statistic_nonce,
                    avg: 0,
                },
            };


            jQuery('#wpProQuiz_user_overlay, #wpProQuiz_loadUserData').show();
            var content = jQuery('#wpProQuiz_user_content').hide();

            jQuery.ajax({
                type: 'POST',
                url: ldVars.ajaxurl,
                dataType: 'json',
                cache: false,
                data: post_data,
                error(jqXHR, textStatus, errorThrown) { },
                success(reply_data) {

                    if ('undefined' !== typeof reply_data.html) {


                        var embedData = famb_convert_table_toDiv(reply_data.html, report_title);

                        //var embedData = reply_data.html;


                        content.html(embedData);

                        jQuery('#wpProQuiz_user_content').show();

                        // jQuery('body').trigger(
                        //     'learndash-statistics-contentchanged'
                        // );

                        jQuery('#wpProQuiz_loadUserData').hide();

                    }
                },
            });

            jQuery('#wpProQuiz_overlay_close').on('click', function () {
                jQuery('#wpProQuiz_user_overlay').hide();
            });

        });

        /**
         * Copy Group link to clipboard
         */

        $(document).on('click', 'a.famb-group-copy', function (e) {

            var codeOrLink = jQuery(this).attr('data-copy');

            codeOrLink = codeOrLink.replace(/<br>/g, "\n");

            var textarea = document.createElement('textarea');

            textarea.value = codeOrLink;

            document.body.appendChild(textarea);

            textarea.select();

            document.execCommand('copy');

            document.body.removeChild(textarea);

            alert('Code or link copied to clipboard!');

        });

        /**
         * Students per page
         */

        $(document).on('change', '.aloa-num-of-record', function (e) {

            let num_of_records = jQuery(this).val();
            let group_id = jQuery('.aloa-course-dropdown').attr('data-group-id');
            let course_id = jQuery('.aloa-course-dropdown').val();

            jQuery('.aloa-reporting-loader-overlay').css('display', 'flex');

            console.log(course_id);
            console.log(group_id);
            console.log(num_of_records);


            $.ajax({
                type: 'POST',
                data: {
                    nonce: aloa_globals.ajax_nonce,
                    action: 'aloa_load_group_courses_students',
                    group_id: group_id,
                    course_id: course_id,
                    num_of_record: num_of_records,
                },
                dataType: 'json',
                url: aloa_globals.ajax_url,
                crossDomain: true,
                cache: false,
                async: true,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aloa_globals.ajax_nonce);
                },
            }).done(function (response) {

                jQuery('.aloa-reporting-loader-overlay').css('display', 'none');


                if (response.success !== undefined && response.success) {

                    jQuery('.famb-group-std-data').html(response.data);
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html(response.course_title);

                } else {

                    jQuery('.famb-group-std-data tbody').html('<tr><td colspan="3">There are no students enrolled in this course group</td></tr>');
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html('');

                    alert(response.message);

                }

            });

        });


        /**
         * Students pagination
         */

        $(document).on('click', '.aloa-paging-list li a, .aloa-next-previous li a', function (e) {

            let page = jQuery(this).attr('data-page-num');

            if (page === 'undefined' || page == null || page == '') {
                return false;
            }

            let num_of_records = jQuery('.aloa-num-of-record').val();
            let group_id = jQuery('.aloa-course-dropdown').attr('data-group-id');
            let course_id = jQuery('.aloa-course-dropdown').val();

            jQuery('.aloa-reporting-loader-overlay').css('display', 'flex');

            $.ajax({
                type: 'POST',
                data: {
                    nonce: aloa_globals.ajax_nonce,
                    action: 'aloa_load_group_courses_students',
                    group_id: group_id,
                    course_id: course_id,
                    num_of_record: num_of_records,
                    page: page,
                },
                dataType: 'json',
                url: aloa_globals.ajax_url,
                crossDomain: true,
                cache: false,
                async: true,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aloa_globals.ajax_nonce);
                },
            }).done(function (response) {

                jQuery('.aloa-reporting-loader-overlay').css('display', 'none');


                if (response.success !== undefined && response.success) {

                    jQuery('.famb-group-std-data').html(response.data);
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html(response.course_title);

                } else {

                    jQuery('.famb-group-std-data tbody').html('<tr><td colspan="3">There are no students enrolled in this course group</td></tr>');
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html('');

                    alert(response.message);

                }

            });

        });

        $(document).on('click', 'th.aloa-progress-sort', function (e) {


            let page = jQuery('.aloa-paging-list li.active a').attr('data-page-num');

            if (page === 'undefined' || page == null || page == '') {
                page = 1;
            }

            let sort_val = jQuery(this).attr('data-sort-val');

            let num_of_records = jQuery('.aloa-num-of-record').val();
            let group_id = jQuery('.aloa-course-dropdown').attr('data-group-id');
            let course_id = jQuery('.aloa-course-dropdown').val();

            jQuery('.aloa-reporting-loader-overlay').css('display', 'flex');


            sort_val = sort_val == 'asc' ? 'desc' : 'asc';

            $.ajax({
                type: 'POST',
                data: {
                    nonce: aloa_globals.ajax_nonce,
                    action: 'aloa_load_group_courses_students',
                    group_id: group_id,
                    course_id: course_id,
                    num_of_record: num_of_records,
                    sort: sort_val,
                    page: page,
                },
                dataType: 'json',
                url: aloa_globals.ajax_url,
                crossDomain: true,
                cache: false,
                async: true,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aloa_globals.ajax_nonce);
                },
            }).done(function (response) {

                jQuery('.aloa-reporting-loader-overlay').css('display', 'none');


                if (response.success !== undefined && response.success) {

                    jQuery('.famb-group-std-data').html(response.data);
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html(response.course_title);

                } else {

                    jQuery('.famb-group-std-data tbody').html('<tr><td colspan="3">There are no students enrolled in this course group</td></tr>');
                    window.history.pushState(null, null, response.link);
                    jQuery('.famb-group-name h1').html('');
                    alert(response.message);
                }

            });

        });

    });

    /**
     * Convert Table response report to divs
     */

    function famb_convert_table_toDiv(tableHTML, report_title) {

        var tempElement = document.createElement('div');

        tempElement.innerHTML = tableHTML;

        var nameData = tempElement.querySelectorAll('h2');

        var fambHead = nameData[0].innerHTML.replace("User statistics:", "Responses / ");
        var fambHeadDate = nameData[1].innerHTML.replace("User statistics:", "Responses / ");

        var tableElement = tempElement.querySelector('table');

        var thead = tableElement.querySelector('thead');
        var tfoot = tableElement.querySelector('tfoot');
        var tbody = tableElement.querySelector('tbody');

        var subtotal = tbody.querySelector('tr[id^="wpProQuiz_ctr_"]');

        if (thead) {
            tableElement.removeChild(thead);
        }

        if (tfoot) {
            tableElement.removeChild(tfoot);
        }

        if (subtotal) {

            tbody.querySelectorAll('tr[id^="wpProQuiz_ctr_"]').forEach(function (subTotalTr) {
                subTotalTr.remove();
            });

        }

        var otherr = tbody.querySelectorAll('tr:not(.categoryTr)');

        tbody.querySelectorAll('tr:not(.categoryTr)').forEach(function (otherRow, otherRowIndex) {

            var tdElements = otherRow.querySelectorAll('th, td');

            if (tdElements.length > 1) {

                otherRow.querySelectorAll('th, td').forEach(function (innerThTd, innerThTdIndex) {

                    if (innerThTdIndex !== 'undefined' && innerThTdIndex != 1) {

                        innerThTd.remove();
                    }

                });

            } else {

                otherRow.querySelectorAll('th, td').forEach(function (innerThTd, innerThTdIndex) {

                    if (innerThTd.textContent.trim() === '') {

                        jQuery(innerThTd).parent().remove();
                    }

                });

            }

        });

        //var styleData = tempElement.querySelector('style');

        var divContainer = '<div class="famb-quizz-wrap">';
        var itemContainer = '';
        var divRow = '';

        var tr_length = tableElement.querySelectorAll('tr').length;

        tableElement.querySelectorAll('tr').forEach(function (row, rowIndex) {

            if (row.classList.contains('categoryTr')) {

                if (itemContainer != '') {
                    divRow += '</div>';
                    itemContainer += divRow;
                    itemContainer += '</div>';

                    divRowChildren = divRow.children;
                }

                divRow = '<div class="famb-div-row">';
                itemContainer += '<div class="famb-item-wrap">';
            }

            row.querySelectorAll('td, th').forEach(function (cell, cellIndex) {
                divRow += cell.innerHTML;
            });

            if (tr_length == (rowIndex + 1)) {
                divRow += '</div>';
                itemContainer += divRow;
                itemContainer += '</div>';
            }

        });

        divContainer += '<div class="famb-quiz-head"><h2>' + fambHead + '</h2> <time>' + fambHeadDate + '</time></div><div class="famb-que-name"><h2>' + report_title + '</h2></div>';

        divContainer += itemContainer;
        divContainer += '</div>';

        return divContainer;

    }





}(jQuery));