<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Opening Details page — shows full Job Description and apply button.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\opening_manager;
use local_finplacements\candidate_manager;
use local_finplacements\application_manager;

require_login();

$context = context_system::instance();
require_capability('local/finplacements:candidate', $context);

$id = required_param('id', PARAM_INT);
$opening = opening_manager::get_by_id($id);

if (!$opening) {
    throw new moodle_exception('error_notfound', 'local_finplacements');
}

// Only admins can view non-active openings.
$is_admin = has_capability('local/finplacements:admin', $context);
if ($opening->status !== 'active' && !$is_admin) {
    redirect(new moodle_url('/local/finplacements/jobs.php'), 'This opening is no longer active.', null, \core\output\notification::NOTIFY_WARNING);
}

$candidate = candidate_manager::get_by_userid($USER->id);
$is_applied = false;

if ($candidate) {
    $apps = application_manager::get_for_candidate($candidate->id);
    foreach ($apps as $app) {
        if ((int)$app->openingid === (int)$opening->id) {
            $is_applied = true;
            break;
        }
    }
}

$now = time();
$deadline_passed = ($opening->deadline < $now);

$PAGE->set_url(new moodle_url('/local/finplacements/opening_detail.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(s($opening->title) . ' — FinTree Placements');
$PAGE->set_heading(get_string('board_heading', 'local_finplacements'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

echo $OUTPUT->header();
echo local_finplacements_render_nav('jobs');

$data = new stdClass();

// URLs
$data->board_url   = (new moodle_url('/local/finplacements/jobs.php'))->out(false);
$data->tracker_url = (new moodle_url('/local/finplacements/tracker.php'))->out(false);
$data->apply_url   = (new moodle_url('/local/finplacements/apply.php', ['openingid' => $opening->id]))->out(false);

// Core Opening Map
$data->opening = new stdClass();
$data->opening->title                 = $opening->title;
$data->opening->jobid                 = $opening->jobid;
$data->opening->company               = $opening->company;
$data->opening->location              = $opening->location;
$data->opening->employment_type       = ucfirst($opening->employment_type);

// Meta logic
$exp_bands = local_finplacements_get_experience_bands_with_any();
$data->opening->experience_required   = $exp_bands[$opening->experience_required] ?? $opening->experience_required;

$cfa_opts = local_finplacements_get_cfa_levels();
if (!empty($opening->cfa_required) && $opening->cfa_required !== 'none') {
    $data->opening->cfa_required = $cfa_opts[$opening->cfa_required] ?? $opening->cfa_required;
}

$frm_opts = local_finplacements_get_frm_levels();
if (!empty($opening->frm_required) && $opening->frm_required !== 'none') {
    $data->opening->frm_required = $frm_opts[$opening->frm_required] ?? $opening->frm_required;
}

if ($opening->employment_type === 'fulltime' && !empty($opening->min_ctc)) {
    $data->opening->show_ctc = true;
    $data->opening->min_ctc  = $opening->min_ctc;
    $data->opening->max_ctc  = $opening->max_ctc ?? false;
}

if ($opening->employment_type === 'internship' && !empty($opening->stipend)) {
    $data->opening->show_stipend = true;
    $data->opening->stipend = $opening->stipend;
}

// Deadline + Application Logic
$data->deadline_formatted = userdate($opening->deadline, get_string('strftimedate', 'langconfig'));
$data->deadline_passed    = $deadline_passed;
$data->is_applied         = $is_applied;

// HTML Content blocks (Using format_text logic)
$data->has_overview        = !empty($opening->role_overview);
$data->overview_html       = $data->has_overview ? format_text($opening->role_overview, FORMAT_HTML) : '';

$data->has_jd              = !empty($opening->jd_text);
$data->jd_html             = $data->has_jd ? format_text($opening->jd_text, FORMAT_HTML) : '';

$data->has_qualifications  = !empty($opening->qualification_notes);
$data->qualifications_html = $data->has_qualifications ? format_text($opening->qualification_notes, FORMAT_HTML) : '';

echo $OUTPUT->render_from_template('local_finplacements/opening_detail', $data);

echo $OUTPUT->footer();
