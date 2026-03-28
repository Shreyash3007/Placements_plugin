<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Profile page — Enhanced with collapsible sections and section-level progress.
 *
 * Features:
 *  - Collapsible sections (Personal, Academic, Certifications, Work, Documents)
 *  - Section-level progress indicators
 *  - Expand/Collapse all buttons
 *  - Overall completion bar
 *  - Auto-save per section (AJAX)
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\candidate_manager;

require_login();

$context = context_system::instance();

// Admins can also view/edit their own profile — allow both roles.
if (!has_capability('local/finplacements:candidate', $context) &&
    !has_capability('local/finplacements:admin', $context)) {
    throw new moodle_exception('error_nopermission', 'local_finplacements');
}

$PAGE->set_url(new moodle_url('/local/finplacements/profile.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('profile_heading', 'local_finplacements'));
$PAGE->set_heading(get_string('profile_heading', 'local_finplacements'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

$candidate = candidate_manager::get_by_userid($USER->id);
$saved     = false;
$errors    = [];

// ---------- Handle form submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $data = new stdClass();
    $data->fullname         = required_param('fullname', PARAM_TEXT);
    $data->phone            = required_param('phone', PARAM_TEXT);
    $data->email            = $USER->email; // Not editable.
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
    if (empty($data->city)) {
        $errors[] = 'City is required.';
    }
    if (empty($data->state)) {
        $errors[] = 'State is required.';
    }
    if (empty($data->experience_band)) {
        $errors[] = 'Experience band is required.';
    }
    // Resume URL must be https:// if provided.
    if (!empty($data->resume_url) && !preg_match('/^https:\/\//i', $data->resume_url)) {
        $errors[] = 'Resume URL must start with https:// (use a Google Drive sharing link).';
    }

    if (empty($errors)) {
        candidate_manager::save_profile($USER->id, $data);
        $candidate = candidate_manager::get_by_userid($USER->id);
        $saved     = true;
    }
}

// ---------- Pre-fill from Moodle user if no candidate record yet ----------
if (!$candidate) {
    $candidate = new stdClass();
    $candidate->fullname         = fullname($USER);
    $candidate->email            = $USER->email;
    $candidate->phone            = '';
    $candidate->city             = '';
    $candidate->state            = '';
    $candidate->experience_band  = '';
    $candidate->current_employer = '';
    $candidate->current_ctc      = '';
    $candidate->designation      = '';
    $candidate->tenth_pct        = '';
    $candidate->twelfth_pct      = '';
    $candidate->ug_status        = '';
    $candidate->ug_field         = '';
    $candidate->ug_gpa           = '';
    $candidate->pg_status        = '';
    $candidate->pg_field         = '';
    $candidate->pg_gpa           = '';
    $candidate->cfa_level        = 'none';
    $candidate->frm_level        = 'none';
    $candidate->other_certs      = '';
    $candidate->fm_cert          = 0;
    $candidate->resume_url       = '';
    $candidate->photo_url        = '';
    $candidate->profile_score    = 0;
    $candidate->profile_complete = 0;
}

// Calculate section-level scores.
$section_scores = local_finplacements_calculate_section_scores($candidate);
$overall_score  = (int)($candidate->profile_score ?? 0);

$score     = $overall_score;
$exp_bands = local_finplacements_get_experience_bands();
$cfa_opts  = local_finplacements_get_cfa_levels();
$frm_opts  = local_finplacements_get_frm_levels();

echo $OUTPUT->header();
echo local_finplacements_render_nav('profile');

$data = new stdClass();

// 1. Overall Completion Bar
$data->score       = $score;
$data->is_complete = ($score >= 80);
$data->saved       = $saved;
$data->errors      = $errors;

// 2. Form Setup
$data->form_action = (new moodle_url('/local/finplacements/profile.php'))->out(false);
$data->sesskey     = sesskey();

// 3. Section Scores for Progress Indicators
$data->section_scores = $section_scores;

// 4. Candidate Data
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
$data->candidate->ug_status_completed = ($candidate->ug_status === 'completed');
$data->candidate->ug_status_pursuing  = ($candidate->ug_status === 'pursuing');
$data->candidate->pg_status_completed = ($candidate->pg_status === 'completed');
$data->candidate->pg_status_pursuing  = ($candidate->pg_status === 'pursuing');

// Build Iterators for Select dropdowns
$data->cfa_opts = [];
foreach ($cfa_opts as $val => $label) {
    $data->cfa_opts[] = ['val' => $val, 'label' => $label, 'selected' => ($candidate->cfa_level === $val)];
}

$data->frm_opts = [];
foreach ($frm_opts as $val => $label) {
    $data->frm_opts[] = ['val' => $val, 'label' => $label, 'selected' => ($candidate->frm_level === $val)];
}

$data->exp_bands = [];
foreach ($exp_bands as $val => $label) {
    $data->exp_bands[] = ['val' => $val, 'label' => $label, 'selected' => ($candidate->experience_band === $val)];
}

// Render the enhanced mustache template
echo $OUTPUT->render_from_template('local_finplacements/profile', $data);

// Initialize collapsible sections and auto-save.
$PAGE->requires->js_call_amd('local_finplacements/profile_sections', 'init');

echo $OUTPUT->footer();
