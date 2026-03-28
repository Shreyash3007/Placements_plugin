<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Application Review page — candidate reviews/updates profile and applies.
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

$openingid = required_param('openingid', PARAM_INT);
$opening = opening_manager::get_by_id($openingid);

if (!$opening || $opening->status !== 'active') {
    redirect(new moodle_url('/local/finplacements/jobs.php'), 'This opening is no longer active.', null, \core\output\notification::NOTIFY_WARNING);
}

// Check deadline
$now = time();
if ($opening->deadline < $now) {
    redirect(new moodle_url('/local/finplacements/opening_detail.php', ['id' => $openingid]), 'The application deadline has passed.', null, \core\output\notification::NOTIFY_WARNING);
}

$candidate = candidate_manager::get_by_userid($USER->id);

// Check if already applied
if ($candidate) {
    if (application_manager::has_applied($candidate->id, $openingid)) {
        redirect(new moodle_url('/local/finplacements/tracker.php'), 'You have already applied for this opening.', null, \core\output\notification::NOTIFY_INFO);
    }
}

$PAGE->set_url(new moodle_url('/local/finplacements/apply.php', ['openingid' => $openingid]));
$PAGE->set_context($context);
$PAGE->set_title('Review Application — ' . s($opening->company));
$PAGE->set_heading(get_string('board_heading', 'local_finplacements'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

$errors = [];

// ---------- Handle form submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $data = new stdClass();
    $data->fullname         = required_param('fullname', PARAM_TEXT);
    $data->phone            = required_param('phone', PARAM_TEXT);
    $data->email            = $USER->email;
    $data->city             = required_param('city', PARAM_TEXT);
    $data->state            = required_param('state', PARAM_TEXT);
    $data->experience_band  = required_param('experience_band', PARAM_ALPHA);
    $data->current_employer = optional_param('current_employer', '', PARAM_TEXT);
    $data->current_ctc      = optional_param('current_ctc', '', PARAM_TEXT);
    $data->designation      = optional_param('designation', '', PARAM_TEXT);
    $data->tenth_pct        = optional_param('tenth_pct', '', PARAM_TEXT);
    $data->twelfth_pct      = optional_param('twelfth_pct', '', PARAM_TEXT);
    $data->ug_status        = optional_param('ug_status', '', PARAM_ALPHA);
    $data->ug_field         = optional_param('ug_field', '', PARAM_TEXT);
    $data->ug_gpa           = optional_param('ug_gpa', '', PARAM_TEXT);
    $data->pg_status        = optional_param('pg_status', '', PARAM_ALPHA);
    $data->pg_field         = optional_param('pg_field', '', PARAM_TEXT);
    $data->pg_gpa           = optional_param('pg_gpa', '', PARAM_TEXT);
    $data->cfa_level        = optional_param('cfa_level', 'none', PARAM_ALPHANUMEXT);
    $data->frm_level        = optional_param('frm_level', 'none', PARAM_ALPHANUMEXT);
    $data->other_certs      = optional_param('other_certs', '', PARAM_TEXT);
    $data->fm_cert          = optional_param('fm_cert', 0, PARAM_INT);
    $data->resume_url       = optional_param('resume_url', '', PARAM_URL);
    $data->photo_url        = optional_param('photo_url', '', PARAM_URL);

    // Required field validation.
    if (empty($data->phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/[\s\-]/', '', $data->phone))) {
        $errors[] = 'Phone number must be 10–15 digits (digits only, optional leading +).';
    }
    if (empty($data->city)) $errors[] = 'City is required.';
    if (empty($data->state)) $errors[] = 'State is required.';
    if (empty($data->experience_band)) $errors[] = 'Experience band is required.';
    if (!empty($data->resume_url) && !preg_match('/^https:\/\//i', $data->resume_url)) {
        $errors[] = 'Resume URL must start with https:// (use a Google Drive sharing link).';
    }

    if (empty($errors)) {
        // Save Profile
        candidate_manager::save_profile($USER->id, $data);
        
        // Fetch updated candidate & calculate score
        $updated_candidate = candidate_manager::get_by_userid($USER->id);
        
        // Hardcoded 50% for now matching profile.php unless admin setting exists.
        $min_score = 50; 
        
        // Score restriction check
        if ((int)$updated_candidate->profile_score < $min_score) {
            $errors[] = "Your profile is incomplete. You need a minimum profile score of {$min_score}% to submit applications. Your current score is {$updated_candidate->profile_score}%.";
        } else {
            // Create Application
            $new_appid = application_manager::apply($updated_candidate->id, $opening->id);
            if ($new_appid) {
                redirect(new moodle_url('/local/finplacements/tracker.php'), 'Application submitted successfully! You can track your status here.', null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                $errors[] = "An error occurred while linking your application. Please try again.";
            }
        }
    }
}

// ---------- Pre-fill from Moodle user if no candidate record yet ----------
if (!$candidate) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $candidate = clone $USER; // Dummy to prefill
        $candidate->fullname = fullname($USER);
        $candidate->phone = ''; $candidate->city = ''; $candidate->state = ''; $candidate->experience_band = '';
        $candidate->current_employer = ''; $candidate->current_ctc = ''; $candidate->designation = '';
        $candidate->tenth_pct = ''; $candidate->twelfth_pct = ''; $candidate->ug_status = ''; $candidate->ug_field = ''; $candidate->ug_gpa = '';
        $candidate->pg_status = ''; $candidate->pg_field = ''; $candidate->pg_gpa = '';
        $candidate->cfa_level = 'none'; $candidate->frm_level = 'none'; $candidate->other_certs = ''; $candidate->fm_cert = 0;
        $candidate->resume_url = ''; $candidate->photo_url = ''; $candidate->profile_score = 0; $candidate->profile_complete = 0;
    } else {
        $candidate = clone $data; // Repopulate form with submitted failing data
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $candidate = clone $candidate; // Use submitted data for repopulation overlay
        foreach ((array)$data as $k => $v) $candidate->$k = $v;
    }
}

$score     = (int)($candidate->profile_score ?? 0);
$exp_bands = local_finplacements_get_experience_bands();
$cfa_opts  = local_finplacements_get_cfa_levels();
$frm_opts  = local_finplacements_get_frm_levels();

echo $OUTPUT->header();
echo local_finplacements_render_nav('jobs');

$data = new stdClass();
// 1. Navigation / Messaging
$data->back_url = (new moodle_url('/local/finplacements/opening_detail.php', ['id' => $opening->id]))->out(false);
$data->opening  = new stdClass();
$data->opening->id      = $opening->id;
$data->opening->title   = $opening->title;
$data->opening->company = $opening->company;

$data->errors = [];
foreach ($errors as $err) {
    $data->errors[] = $err;
}

// 2. Form setup
$data->form_action = (new moodle_url('/local/finplacements/apply.php', ['openingid' => $opening->id]))->out(false);
$data->sesskey     = sesskey();

// 3. Candidate DB Object map
$data->candidate = new stdClass();
$data->candidate->fullname         = $candidate->fullname ?? fullname($USER);
$data->candidate->email            = $USER->email;
$data->candidate->phone            = $candidate->phone ?? '';
$data->candidate->city             = $candidate->city ?? '';
$data->candidate->state            = $candidate->state ?? '';
$data->candidate->tenth_pct        = $candidate->tenth_pct ?? '';
$data->candidate->twelfth_pct      = $candidate->twelfth_pct ?? '';
$data->candidate->ug_field         = $candidate->ug_field ?? '';
$data->candidate->ug_gpa           = $candidate->ug_gpa ?? '';
$data->candidate->pg_field         = $candidate->pg_field ?? '';
$data->candidate->pg_gpa           = $candidate->pg_gpa ?? '';
$data->candidate->other_certs      = $candidate->other_certs ?? '';
$data->candidate->fm_cert          = !empty($candidate->fm_cert);
$data->candidate->current_employer = $candidate->current_employer ?? '';
$data->candidate->designation      = $candidate->designation ?? '';
$data->candidate->current_ctc      = $candidate->current_ctc ?? '';
$data->candidate->resume_url       = $candidate->resume_url ?? '';
$data->candidate->photo_url        = $candidate->photo_url ?? '';

// Specific dropdown pre-selects mappings
$data->candidate->ug_status_completed = (($candidate->ug_status ?? '') === 'completed');
$data->candidate->ug_status_pursuing  = (($candidate->ug_status ?? '') === 'pursuing');
$data->candidate->pg_status_completed = (($candidate->pg_status ?? '') === 'completed');
$data->candidate->pg_status_pursuing  = (($candidate->pg_status ?? '') === 'pursuing');

// Build Iterators for Select dropdowns
$data->cfa_opts = [];
foreach ($cfa_opts as $val => $label) {
    $data->cfa_opts[] = ['val' => $val, 'label' => $label, 'selected' => (($candidate->cfa_level ?? '') === $val)];
}

$data->frm_opts = [];
foreach ($frm_opts as $val => $label) {
    $data->frm_opts[] = ['val' => $val, 'label' => $label, 'selected' => (($candidate->frm_level ?? '') === $val)];
}

$data->exp_bands = [];
foreach ($exp_bands as $val => $label) {
    $data->exp_bands[] = ['val' => $val, 'label' => $label, 'selected' => (($candidate->experience_band ?? '') === $val)];
}

echo $OUTPUT->render_from_template('local_finplacements/apply', $data);

echo $OUTPUT->footer();
