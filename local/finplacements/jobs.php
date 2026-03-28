<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Job board — candidate-facing list of active openings.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\candidate_manager;
use local_finplacements\opening_manager;
use local_finplacements\application_manager;

require_login();

$context = context_system::instance();
require_capability('local/finplacements:candidate', $context);

$PAGE->set_url(new moodle_url('/local/finplacements/jobs.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('jobboard_heading', 'local_finplacements'));
$PAGE->set_heading(get_string('jobboard_heading', 'local_finplacements'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

// --- Filters from GET ---
$filter_keyword  = optional_param('keyword', '', PARAM_TEXT);
$filter_location = optional_param('location', '', PARAM_TEXT);
$filter_type     = optional_param('employment_type', '', PARAM_ALPHA);
$filter_exp      = optional_param('exp_band', '', PARAM_ALPHANUMEXT);
$filter_cfa_only = optional_param('cfa_only', 0, PARAM_BOOL);
$filter_frm_only = optional_param('frm_only', 0, PARAM_BOOL);
$filter_sort     = optional_param('sort', 'recent', PARAM_ALPHA);
$page            = optional_param('page', 0, PARAM_INT);

// Validate sort option.
$allowed_sorts = ['recent', 'deadline', 'company', 'title', 'relevance'];
if (!in_array($filter_sort, $allowed_sorts)) {
    $filter_sort = 'recent';
}

// --- Candidate profile ---
$candidate = candidate_manager::get_by_userid($USER->id);

// --- Pagination settings ---
$perpage = 10;

// --- Openings with filters and pagination ---
$openings = opening_manager::get_active(
    $filter_location,
    $filter_type,
    $filter_keyword,
    $filter_sort,
    $filter_exp,
    $filter_cfa_only,
    $filter_frm_only,
    $page,
    $perpage
);

// --- Total count for pagination ---
$total = opening_manager::count_active(
    $filter_location,
    $filter_type,
    $filter_keyword,
    $filter_exp,
    $filter_cfa_only,
    $filter_frm_only
);
$totalpages = ceil($total / $perpage);

// --- Collect applied opening IDs for this candidate ---
$applied_ids = [];
if ($candidate) {
    $apps = application_manager::get_for_candidate($candidate->id);
    foreach ($apps as $app) {
        $applied_ids[] = (int)$app->openingid;
    }
}

$now = time();

echo $OUTPUT->header();
echo local_finplacements_render_nav('jobs');

$data = new stdClass();

// 1. Profile Banner Data
$data->profile_incomplete = false;
if (!$candidate || (int)($candidate->profile_score ?? 0) < 80) {
    $data->profile_incomplete = true;
    $data->profile_score      = $candidate ? (int)($candidate->profile_score ?? 0) : 0;
    $data->profile_url        = (new moodle_url('/local/finplacements/profile.php'))->out(false);
}

// 2. Filter Form Data
$data->filter_keyword   = $filter_keyword;
$data->filter_location  = $filter_location;
$data->filter_exp       = $filter_exp;
$data->filter_cfa_only  = $filter_cfa_only;
$data->filter_frm_only  = $filter_frm_only;
$data->is_fulltime      = ($filter_type === 'fulltime');
$data->is_internship    = ($filter_type === 'internship');
$data->clear_url        = (new moodle_url('/local/finplacements/jobs.php'))->out(false);
$data->current_sort     = $filter_sort;

// 3. Pagination Data
$data->total = $total;
$data->current_page = $page + 1; // 1-based for display.
$data->per_page = $perpage;
$data->total_pages = $totalpages;
$data->has_prev = $page > 0;
$data->has_next = $page < $totalpages - 1;
$data->prev_page = $page > 0 ? $page - 1 : 0;
$data->next_page = $page < $totalpages - 1 ? $page + 1 : $page;

// 4. Openings Loop Data
$data->has_openings = !empty($openings);
$data->openings     = [];

if ($data->has_openings) {
    $bands    = local_finplacements_get_experience_bands_with_any();
    $cfa_opts = local_finplacements_get_cfa_levels();
    $frm_opts = local_finplacements_get_frm_levels();

    foreach ($openings as $opening) {
        $op = new stdClass();
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
        $deadline_soon          = (!$deadline_passed && ($opening->deadline - $now) < 172800); // 48 hrs

        $op->deadline_formatted = userdate($opening->deadline, get_string('strftimedate', 'langconfig'));
        $op->deadline_soon      = $deadline_soon;
        $op->deadline_soon_warning = $deadline_soon && !$deadline_passed;
        $op->deadline_passed    = $deadline_passed;

        $op->is_applied         = in_array($op->id, $applied_ids);
        $op->view_details_url   = (new moodle_url('/local/finplacements/opening_detail.php', ['id' => $op->id]))->out(false);
        
        // New fields for enhanced UX.
        $op->posted_date        = $opening->timecreated;
        $op->formatted_posted_date = userdate($opening->timecreated, get_string('strftimerecentfull', 'langconfig'));
        // Use applicant_count from JOIN query (eliminates N+1 query).
        $op->applicant_count    = $opening->applicant_count ?? 0;

        $data->openings[] = $op;
    }
}

// Sort options for dropdown.
$data->sort_options = [
    'recent' => ['label' => 'Most Recent', 'selected' => ($filter_sort === 'recent')],
    'deadline' => ['label' => 'Deadline (Soonest)', 'selected' => ($filter_sort === 'deadline')],
    'company' => ['label' => 'Company Name (A-Z)', 'selected' => ($filter_sort === 'company')],
    'title' => ['label' => 'Job Title (A-Z)', 'selected' => ($filter_sort === 'title')],
    'relevance' => ['label' => 'Relevance', 'selected' => ($filter_sort === 'relevance')],
];

// Experience bands for filter dropdown.
$data->exp_band_options = [];
foreach (local_finplacements_get_experience_bands_with_any() as $val => $label) {
    $data->exp_band_options[] = [
        'val' => $val,
        'label' => $label,
        'selected' => ($filter_exp === $val)
    ];
}

// Render using the new Mustache Engine
echo $OUTPUT->render_from_template('local_finplacements/job_board', $data);

// Initialize AJAX filtering.
$PAGE->requires->js_call_amd('local_finplacements/jobboard_filter', 'init');

echo $OUTPUT->footer();
