<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External API for local_finplacements.
 *
 * Provides AJAX endpoints for job board filtering and other dynamic operations.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_multiple_structure;
use core\output\notification;

/**
 * External functions for the FinTree Placements plugin.
 */
class external extends external_api {

    /**
     * Returns description of the get_filtered_jobs function parameters.
     *
     * @return external_function_parameters
     */
    public static function get_filtered_jobs_parameters(): external_function_parameters {
        return new external_function_parameters([
            'keyword'       => new external_value(PARAM_TEXT, 'Search keyword', VALUE_DEFAULT, ''),
            'location'      => new external_value(PARAM_TEXT, 'Location filter', VALUE_DEFAULT, ''),
            'employment_type' => new external_value(PARAM_ALPHA, 'Employment type', VALUE_DEFAULT, ''),
            'exp_band'      => new external_value(PARAM_ALPHANUMEXT, 'Experience band', VALUE_DEFAULT, ''),
            'cfa_only'      => new external_value(PARAM_BOOL, 'CFA required only', VALUE_DEFAULT, false),
            'frm_only'      => new external_value(PARAM_BOOL, 'FRM required only', VALUE_DEFAULT, false),
            'sort'          => new external_value(PARAM_ALPHA, 'Sort order', VALUE_DEFAULT, 'recent'),
            'page'          => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns filtered jobs based on search criteria.
     *
     * @param string $keyword       Search keyword.
     * @param string $location      Location filter.
     * @param string $employment_type Employment type filter.
     * @param string $exp_band      Experience band filter.
     * @param bool   $cfa_only      CFA required only.
     * @param bool   $frm_only      FRM required only.
     * @param string $sort          Sort order.
     * @param int    $page          Page number.
     * @return array Array containing jobs HTML and pagination info.
     */
    public static function get_filtered_jobs(
        string $keyword = '',
        string $location = '',
        string $employment_type = '',
        string $exp_band = '',
        bool $cfa_only = false,
        bool $frm_only = false,
        string $sort = 'recent',
        int $page = 0
    ): array {
        global $OUTPUT, $USER, $CFG;

        // Validate parameters.
        $params = self::validate_parameters(self::get_filtered_jobs_parameters(), [
            'keyword'         => $keyword,
            'location'        => $location,
            'employment_type' => $employment_type,
            'exp_band'        => $exp_band,
            'cfa_only'        => $cfa_only,
            'frm_only'        => $frm_only,
            'sort'            => $sort,
            'page'            => $page,
        ]);

        // Context check - candidate capability required.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/finplacements:candidate', $context);

        $perpage = 10; // Jobs per page.

        // Get filtered openings.
        $openings = opening_manager::get_active(
            $params['location'],
            $params['employment_type'],
            $params['keyword'],
            $params['sort'],
            $params['exp_band'],
            $params['cfa_only'],
            $params['frm_only'],
            $params['page'],
            $perpage
        );

        // Get total count for pagination.
        $total = opening_manager::count_active(
            $params['location'],
            $params['employment_type'],
            $params['keyword'],
            $params['exp_band'],
            $params['cfa_only'],
            $params['frm_only']
        );

        // Get applied opening IDs for this candidate.
        $applied_ids = [];
        $candidate = candidate_manager::get_by_userid($USER->id);
        if ($candidate) {
            $apps = application_manager::get_for_candidate($candidate->id);
            foreach ($apps as $app) {
                $applied_ids[] = (int)$app->openingid;
            }
        }

        // Prepare data for template.
        $data = new \stdClass();
        $data->has_openings = !empty($openings);
        $data->openings = [];

        if ($data->has_openings) {
            $bands    = \local_finplacements_get_experience_bands_with_any();
            $cfa_opts = \local_finplacements_get_cfa_levels();
            $frm_opts = \local_finplacements_get_frm_levels();
            $now = time();

            foreach ($openings as $opening) {
                $op = new \stdClass();
                $op->id                 = (int)$opening->id;
                $op->jobid              = $opening->jobid;
                $op->title              = $opening->title;
                $op->company            = $opening->company;
                $op->location           = $opening->location;
                $op->company_logo_url   = $opening->company_logo_url ?? '';
                $op->is_internship      = ($opening->employment_type === 'internship');
                $op->type_label         = $op->is_internship ? 'Internship' : 'Full-time';
                $op->experience_label   = $bands[$opening->experience_required] ?? $opening->experience_required;
                $op->has_cfa            = !empty($opening->cfa_required) && $opening->cfa_required !== 'none';
                $op->cfa_label          = $op->has_cfa ? ($cfa_opts[$opening->cfa_required] ?? $opening->cfa_required) : '';
                $op->has_frm            = !empty($opening->frm_required) && $opening->frm_required !== 'none';
                $op->frm_label          = $op->has_frm ? ($frm_opts[$opening->frm_required] ?? $opening->frm_required) : '';
                $op->is_fulltime_with_ctc       = (!$op->is_internship && !empty($opening->min_ctc));
                $op->min_ctc                    = $opening->min_ctc;
                $op->has_max_ctc                = !empty($opening->max_ctc);
                $op->max_ctc                    = $opening->max_ctc;
                $op->is_internship_with_stipend = ($op->is_internship && !empty($opening->stipend));
                $op->stipend                    = $opening->stipend;
                $deadline_passed        = ($opening->deadline < $now);
                $deadline_soon          = (!$deadline_passed && ($opening->deadline - $now) < 172800);
                $op->deadline_formatted = \userdate($opening->deadline, \get_string('strftimedate', 'langconfig'));
                $op->deadline_soon      = $deadline_soon;
                $op->deadline_soon_warning = $deadline_soon && !$deadline_passed;
                $op->deadline_passed    = $deadline_passed;
                $op->is_applied         = in_array($op->id, $applied_ids);
                $op->view_details_url   = (new \moodle_url('/local/finplacements/opening_detail.php', ['id' => $op->id]))->out(false);
                $op->posted_date        = $opening->timecreated;
                $op->formatted_posted_date = \userdate($opening->timecreated, \get_string('strftimerecentfull', 'langconfig'));
                // Use applicant_count from JOIN query (eliminates N+1 query).
                $op->applicant_count    = $opening->applicant_count ?? 0;

                $data->openings[] = $op;
            }
        }

        // Render job grid HTML.
        $html = $OUTPUT->render_from_template('local_finplacements/job_grid', $data);

        // Build pagination info.
        $totalpages = $perpage > 0 ? ceil($total / $perpage) : 1;
        $pagination = [
            'total'         => $total,
            'current_page'  => $page + 1, // 1-based for display.
            'per_page'      => $perpage,
            'total_pages'   => $totalpages,
            'has_prev'      => $page > 0,
            'has_next'      => $page < $totalpages - 1,
            'prev_page'     => $page > 0 ? $page - 1 : 0,
            'next_page'     => $page < $totalpages - 1 ? $page + 1 : $page,
        ];

        return [
            'html'       => $html,
            'count'      => count($openings),
            'total'      => $total,
            'pagination' => $pagination,
        ];
    }

    /**
     * Returns description of the update_application_status function parameters.
     *
     * @return external_function_parameters
     */
    public static function update_application_status_parameters(): external_function_parameters {
        return new external_function_parameters([
            'appid'      => new external_value(PARAM_INT, 'Application ID'),
            'newstatus'  => new external_value(PARAM_TEXT, 'New status value'),
        ]);
    }

    /**
     * Update application status (admin only).
     *
     * @param int    $appid      Application ID.
     * @param string $newstatus  New status value.
     * @return array Status result.
     */
    public static function update_application_status(int $appid, string $newstatus): array {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::update_application_status_parameters(), [
            'appid'     => $appid,
            'newstatus' => $newstatus,
        ]);

        // Context check - admin capability required.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/finplacements:admin', $context);

        // Validate status value.
        $allowed_statuses = ['applied', 'screened', 'shortlisted', 'forwarded', 'placed', 'rejected'];
        if (!in_array($params['newstatus'], $allowed_statuses)) {
            throw new \moodle_exception('invalidstatus', 'local_finplacements');
        }

        // Update status.
        \local_finplacements\application_manager::update_status(
            $params['appid'],
            $params['newstatus'],
            $USER->id
        );

        return [
            'success'   => true,
            'appid'     => $params['appid'],
            'newstatus' => $params['newstatus'],
        ];
    }

    /**
     * Returns description of the update_application_status function result.
     *
     * @return external_single_structure
     */
    public static function update_application_status_returns(): external_single_structure {
        return new external_single_structure([
            'success'   => new external_value(PARAM_BOOL, 'Whether update succeeded'),
            'appid'     => new external_value(PARAM_INT, 'Application ID'),
            'newstatus' => new external_value(PARAM_TEXT, 'New status value'),
        ]);
    }

    /**
     * Returns description of the get_tracker_applications function parameters.
     *
     * @return external_function_parameters
     */
    public static function get_tracker_applications_parameters(): external_function_parameters {
        return new external_function_parameters([
            'status' => new external_value(PARAM_ALPHA, 'Status filter', VALUE_DEFAULT, 'all'),
            'search' => new external_value(PARAM_TEXT, 'Search keyword', VALUE_DEFAULT, ''),
            'sort'   => new external_value(PARAM_ALPHA, 'Sort order', VALUE_DEFAULT, 'date'),
        ]);
    }

    /**
     * Returns filtered applications for candidate tracker.
     *
     * @param string $status Status filter.
     * @param string $search Search keyword.
     * @param string $sort   Sort order.
     * @return array Array containing applications HTML and count.
     */
    public static function get_tracker_applications(
        string $status = 'all',
        string $search = '',
        string $sort = 'date'
    ): array {
        global $OUTPUT, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::get_tracker_applications_parameters(), [
            'status' => $status,
            'search' => $search,
            'sort'   => $sort,
        ]);

        // Context check - candidate capability required.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/finplacements:candidate', $context);

        // Get candidate and applications.
        $candidate = candidate_manager::get_by_userid($USER->id);
        if (!$candidate) {
            return ['html' => '', 'count' => 0];
        }

        $applications = application_manager::get_for_candidate(
            $candidate->id,
            $params['status'],
            $params['search'],
            $params['sort']
        );

        // Prepare data for template.
        $data = new \stdClass();
        $data->has_applications = !empty($applications);
        $data->app_count = count($applications);
        $data->applications = [];

        if ($data->has_applications) {
            $now = time();
            $pipeline = ['applied', 'screened', 'shortlisted', 'forwarded', 'placed'];
            $labels = ['Applied', 'Screened', 'Shortlisted', 'Forwarded', 'Placed'];

            foreach ($applications as $app) {
                $a = new \stdClass();
                $a->id = (int)$app->id;
                $a->jobid = $app->jobid;
                $a->title = $app->title;
                $a->company = $app->company;
                $a->location = $app->location;
                $a->applied_formatted = \userdate($app->applied_date, \get_string('strftimedate', 'langconfig'));
                $a->deadline_passed = ($app->deadline < $now);
                $a->deadline_formatted = \userdate($app->deadline, \get_string('strftimedate', 'langconfig'));
                $a->status_badge = \local_finplacements_status_badge($app->status);
                $a->is_rejected = ($app->status === 'rejected');
                $a->status = $app->status;

                // Last updated.
                if (!empty($app->timemodified)) {
                    $a->updated_formatted = \userdate($app->timemodified, \get_string('strftimerecentfull', 'langconfig'));
                } else {
                    $a->updated_formatted = \userdate($app->applied_date, \get_string('strftimerecentfull', 'langconfig'));
                }

                // Pipeline steps.
                $a->pipeline_steps = [];
                $curr = array_search($app->status, $pipeline);

                if (!$a->is_rejected) {
                    foreach ($pipeline as $i => $step) {
                        $done = ($curr !== false && $i <= $curr);
                        $now2 = ($i === $curr);

                        $cls = 'fp-pipeline-step';
                        if ($now2) {
                            $cls .= ' fp-step-current';
                        } elseif ($done) {
                            $cls .= ' fp-step-done';
                        }

                        $pstep = new \stdClass();
                        $pstep->label = $labels[$i];
                        $pstep->cls = $cls;
                        $pstep->show_arrow = ($i < count($pipeline) - 1);
                        $pstep->arrow_done = ($done && $i < $curr);

                        $a->pipeline_steps[] = $pstep;
                    }
                }
                $data->applications[] = $a;
            }
        }

        // Render applications table body.
        $html = $OUTPUT->render_from_template('local_finplacements/tracker_table', $data);

        return [
            'html'  => $html,
            'count' => count($applications),
        ];
    }

    /**
     * Returns description of the get_tracker_applications function result.
     *
     * @return external_single_structure
     */
    public static function get_tracker_applications_returns(): external_single_structure {
        return new external_single_structure([
            'html'  => new external_value(PARAM_RAW, 'Rendered HTML of applications table'),
            'count' => new external_value(PARAM_INT, 'Number of applications'),
        ]);
    }
}
