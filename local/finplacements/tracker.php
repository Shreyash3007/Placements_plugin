<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Candidate application tracker page — Enhanced with filters, search, and AJAX.
 *
 * Features:
 *  - Status filter tabs (All, Applied, Screened, Shortlisted, Forwarded, Placed, Rejected)
 *  - Search by company or job title
 *  - Sort options (Date Applied, Status, Deadline)
 *  - Enhanced pipeline view with timestamps
 *  - Last updated display
 *  - AJAX-powered filtering (no page reloads)
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\candidate_manager;
use local_finplacements\application_manager;

require_login();

$context = context_system::instance();
require_capability('local/finplacements:candidate', $context);

$PAGE->set_url(new moodle_url('/local/finplacements/tracker.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('tracker_heading', 'local_finplacements'));
$PAGE->set_heading(get_string('tracker_heading', 'local_finplacements'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

// --- Filter params ---
$filter_status = optional_param('status', 'all', PARAM_ALPHA);
$filter_search = optional_param('search', '', PARAM_TEXT);
$filter_sort   = optional_param('sort', 'date', PARAM_ALPHA);

// Validate sort option.
$allowed_sorts = ['date', 'status', 'deadline'];
if (!in_array($filter_sort, $allowed_sorts)) {
    $filter_sort = 'date';
}

$candidate    = candidate_manager::get_by_userid($USER->id);
$applications = [];

if ($candidate) {
    $applications = application_manager::get_for_candidate(
        $candidate->id,
        $filter_status,
        $filter_search,
        $filter_sort
    );
}

echo $OUTPUT->header();
echo local_finplacements_render_nav('tracker');

$data = new stdClass();
$data->browse_url       = (new moodle_url('/local/finplacements/jobs.php'))->out(false);
$data->has_applications = !empty($applications);
$data->app_count        = count($applications);
$data->applications     = [];
$data->current_status   = $filter_status;
$data->current_search   = $filter_search;
$data->current_sort     = $filter_sort;

// Status tabs configuration.
$data->status_tabs = [];
$status_order = ['all', 'applied', 'screened', 'shortlisted', 'forwarded', 'placed', 'rejected'];
$status_labels = ['All', 'Applied', 'Screened', 'Shortlisted', 'Forwarded', 'Placed', 'Rejected'];

foreach ($status_order as $i => $status) {
    $data->status_tabs[] = [
        'key' => $status,
        'label' => $status_labels[$i],
        'count' => 0,
        'selected' => ($filter_status === $status),
    ];
}

if ($data->has_applications) {
    $now      = time();
    $pipeline = ['applied', 'screened', 'shortlisted', 'forwarded', 'placed'];
    $labels   = ['Applied', 'Screened', 'Shortlisted', 'Forwarded', 'Placed'];
    
    // Count applications per status (only when not filtering).
    if ($filter_status === 'all') {
        foreach ($applications as $app) {
            foreach ($data->status_tabs as &$tab) {
                if ($tab['key'] === $app->status) {
                    $tab['count']++;
                    break;
                }
            }
        }
    } else {
        // When filtering, show count for active filter.
        foreach ($data->status_tabs as &$tab) {
            if ($tab['key'] === $filter_status) {
                $tab['count'] = count($applications);
                break;
            }
        }
    }

    foreach ($applications as $app) {
        $a = new stdClass();
        $a->id                  = (int)$app->id;
        $a->jobid               = $app->jobid;
        $a->title               = $app->title;
        $a->company             = $app->company;
        $a->location            = $app->location;
        $a->applied_formatted   = userdate($app->applied_date, get_string('strftimedate', 'langconfig'));
        $a->applied_relative    = format_time($now - $app->applied_date);
        $a->deadline_passed     = ($app->deadline < $now);
        $a->deadline_formatted  = userdate($app->deadline, get_string('strftimedate', 'langconfig'));
        $a->deadline_relative   = format_time($app->deadline - $now);
        $a->status_badge        = local_finplacements_status_badge($app->status);
        $a->is_rejected         = ($app->status === 'rejected');
        $a->status              = $app->status;
        
        // Last updated timestamp.
        if (!empty($app->timemodified)) {
            $a->updated_formatted = userdate($app->timemodified, get_string('strftimerecentfull', 'langconfig'));
            $a->updated_relative  = format_time($now - $app->timemodified);
        } else {
            $a->updated_formatted = userdate($app->applied_date, get_string('strftimerecentfull', 'langconfig'));
            $a->updated_relative  = format_time($now - $app->applied_date);
        }
        
        // Enhanced pipeline with timestamps.
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

                $pstep = new stdClass();
                $pstep->label      = $labels[$i];
                $pstep->cls        = $cls;
                $pstep->show_arrow = ($i < count($pipeline) - 1);
                $pstep->arrow_done = ($done && $i < $curr);
                
                // Add timestamp for completed steps.
                if ($done && !empty($app->shortlisted_date)) {
                    if ($step === 'shortlisted' && $i <= $curr) {
                        $pstep->timestamp = userdate($app->shortlisted_date, get_string('strftimedate', 'langconfig'));
                    }
                }

                $a->pipeline_steps[] = $pstep;
            }
        }
        $data->applications[] = $a;
    }
}

// Sort options for dropdown.
$data->sort_options = [
    ['key' => 'date', 'label' => 'Date Applied (Newest)', 'selected' => ($filter_sort === 'date')],
    ['key' => 'deadline', 'label' => 'Deadline (Soonest)', 'selected' => ($filter_sort === 'deadline')],
    ['key' => 'status', 'label' => 'Status (A-Z)', 'selected' => ($filter_sort === 'status')],
];

// Render using Mustache template.
echo $OUTPUT->render_from_template('local_finplacements/tracker', $data);

// Initialize AJAX filtering.
$PAGE->requires->js_call_amd('local_finplacements/tracker_filter', 'init');

echo $OUTPUT->footer();
