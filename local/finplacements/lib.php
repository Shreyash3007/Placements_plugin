<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Library functions for local_finplacements.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the global navigation tree to add FinTree Placements links.
 *
 * @param global_navigation $nav The global navigation object.
 */
function local_finplacements_extend_navigation(global_navigation $nav) {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $context = context_system::instance();

    $node = $nav->add(
        get_string('pluginname', 'local_finplacements'),
        null,
        navigation_node::TYPE_ROOTNODE,
        null,
        'local_finplacements'
    );

    if (has_capability('local/finplacements:candidate', $context)) {
        $node->add(
            get_string('myprofile', 'local_finplacements'),
            new moodle_url('/local/finplacements/profile.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_profile'
        );
        $node->add(
            get_string('jobboard', 'local_finplacements'),
            new moodle_url('/local/finplacements/jobs.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_jobs'
        );
        $node->add(
            get_string('myapplications', 'local_finplacements'),
            new moodle_url('/local/finplacements/tracker.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_tracker'
        );
    }

    if (has_capability('local/finplacements:admin', $context)) {
        $node->add(
            get_string('manageopenings', 'local_finplacements'),
            new moodle_url('/local/finplacements/admin/openings.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_admin'
        );
    }
}

/**
 * Adds FinTree Placements links to Moodle's primary navigation bar (Boost theme).
 * This makes the links visible in the top horizontal nav, not just the left drawer.
 *
 * @param \core\navigation\views\primary $primarynav The primary nav object.
 */
function local_finplacements_extend_primary_navigation(\core\navigation\views\primary $primarynav) {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Create the "Placements" parent node in the primary top nav.
    $context = context_system::instance();

    $placementsnode = $primarynav->add(
        get_string('pluginname', 'local_finplacements'),
        new moodle_url('/local/finplacements/jobs.php'),
        navigation_node::TYPE_CUSTOM,
        get_string('pluginname', 'local_finplacements'),
        'local_finplacements_primary'
    );

    if (has_capability('local/finplacements:candidate', $context)) {
        $placementsnode->add(
            get_string('myprofile', 'local_finplacements'),
            new moodle_url('/local/finplacements/profile.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_profile_primary'
        );
        $placementsnode->add(
            get_string('jobboard', 'local_finplacements'),
            new moodle_url('/local/finplacements/jobs.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_jobs_primary'
        );
        $placementsnode->add(
            get_string('myapplications', 'local_finplacements'),
            new moodle_url('/local/finplacements/tracker.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_tracker_primary'
        );
    }

    if (has_capability('local/finplacements:admin', $context)) {
        $placementsnode->add(
            get_string('manageopenings', 'local_finplacements'),
            new moodle_url('/local/finplacements/admin/openings.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_admin_primary'
        );
        $placementsnode->add(
            get_string('allapplications', 'local_finplacements'),
            new moodle_url('/local/finplacements/admin/applications.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'finplacements_apps_primary'
        );
    }
}

/**
 * Renders the plugin's own internal navigation bar HTML.
 *
 * Call this at the top of every plugin page, right after $OUTPUT->header().
 * It renders a prominent nav strip visible on every page regardless of theme settings.
 *
 * @param string $active Key of the active nav item ('profile','jobs','tracker','openings','applications').
 * @return string HTML for the nav bar.
 */
function local_finplacements_render_nav(string $active = ''): string {
    global $USER, $CFG;

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $context = context_system::instance();
    $is_admin = has_capability('local/finplacements:admin', $context);
    $is_candidate = has_capability('local/finplacements:candidate', $context);

    // Build the master data object
    $data = new stdClass();
    $data->links = [];

    if ($is_candidate) {
        $data->links[] = [
            'href'   => (new moodle_url('/local/finplacements/jobs.php'))->out(false),
            'label'  => '💼 Job Board',
            'is_active' => ($active === 'jobs'),
        ];
        $data->links[] = [
            'href'   => (new moodle_url('/local/finplacements/profile.php'))->out(false),
            'label'  => '👤 My Profile',
            'is_active' => ($active === 'profile'),
        ];
        $data->links[] = [
            'href'   => (new moodle_url('/local/finplacements/tracker.php'))->out(false),
            'label'  => '📊 My Applications',
            'is_active' => ($active === 'tracker'),
        ];
    }

    if ($is_admin) {
        $data->links[] = [
            'href'   => (new moodle_url('/local/finplacements/admin/openings.php'))->out(false),
            'label'  => '⚙️ Manage Openings',
            'is_active' => ($active === 'openings'),
        ];
        $data->links[] = [
            'href'   => (new moodle_url('/local/finplacements/admin/applications.php'))->out(false),
            'label'  => '📋 All Applications',
            'is_active' => ($active === 'applications'),
        ];
    }

    if (empty($data->links)) {
        return '';
    }

    global $OUTPUT;
    return $OUTPUT->render_from_template('local_finplacements/nav', $data);
}

/**
 * Extends the settings navigation for the plugin.
 * Called by Moodle core when building site admin navigation.
 *
 * @param settings_navigation $nav The settings navigation object.
 * @param context $context The current context.
 */
function local_finplacements_extend_settings_navigation(settings_navigation $nav, context $context) {
    // Reserved for future admin settings page integration.
}

/**
 * Returns available experience band options.
 *
 * @return array Associative array of band_key => display_label.
 */
function local_finplacements_get_experience_bands() {
    return [
        'fresher' => get_string('exp_fresher', 'local_finplacements'),
        '1-3'     => get_string('exp_1_3', 'local_finplacements'),
        '3-5'     => get_string('exp_3_5', 'local_finplacements'),
        '5-10'    => get_string('exp_5_10', 'local_finplacements'),
        '10+'     => get_string('exp_10plus', 'local_finplacements'),
    ];
}

/**
 * Returns available experience band options including 'any' (for openings).
 *
 * @return array Associative array including 'any'.
 */
function local_finplacements_get_experience_bands_with_any() {
    return array_merge(
        ['any' => get_string('exp_any', 'local_finplacements')],
        local_finplacements_get_experience_bands()
    );
}

/**
 * Returns available CFA level options.
 *
 * @return array Associative array of level_key => display_label.
 */
function local_finplacements_get_cfa_levels() {
    return [
        'none'    => get_string('cfa_none', 'local_finplacements'),
        'level1'  => get_string('cfa_level1', 'local_finplacements'),
        'level2'  => get_string('cfa_level2', 'local_finplacements'),
        'level3'  => get_string('cfa_level3', 'local_finplacements'),
        'charter' => get_string('cfa_charter', 'local_finplacements'),
    ];
}

/**
 * Returns available FRM level options.
 *
 * @return array Associative array of level_key => display_label.
 */
function local_finplacements_get_frm_levels() {
    return [
        'none'    => get_string('frm_none', 'local_finplacements'),
        'part1'   => get_string('frm_part1', 'local_finplacements'),
        'part2'   => get_string('frm_part2', 'local_finplacements'),
        'charter' => get_string('frm_charter', 'local_finplacements'),
    ];
}

/**
 * Returns status badge HTML for application status.
 *
 * @param string $status Application status value.
 * @return string HTML badge.
 */
function local_finplacements_status_badge(string $status): string {
    $labels = [
        'applied'     => ['Applied',     'badge-applied'],
        'screened'    => ['Screened',    'badge-screened'],
        'shortlisted' => ['Shortlisted', 'badge-shortlisted'],
        'forwarded'   => ['Forwarded',   'badge-forwarded'],
        'placed'      => ['Placed',      'badge-placed'],
        'rejected'    => ['Rejected',    'badge-rejected'],
    ];
    $data   = $labels[$status] ?? [$status, 'badge-applied'];
    return '<span class="fp-badge ' . s($data[1]) . '">' . s($data[0]) . '</span>';
}

/**
 * Returns coloured HTML badge for auto-score.
 *
 * @param int $score Auto-score value 0-100.
 * @return string HTML badge.
 */
function local_finplacements_score_badge(int $score): string {
    if ($score >= 70) {
        $cls = 'score-green';
    } else if ($score >= 40) {
        $cls = 'score-amber';
    } else {
        $cls = 'score-gray';
    }
    return '<span class="fp-score-badge ' . $cls . '">' . (int)$score . '</span>';
}

/**
 * Returns coloured HTML badge for reliability score.
 *
 * @param int $score Reliability score 0-100.
 * @return string HTML badge.
 */
function local_finplacements_reliability_badge(int $score): string {
    if ($score >= 70) {
        $cls = 'rel-green';
    } else if ($score >= 40) {
        $cls = 'rel-amber';
    } else {
        $cls = 'rel-red';
    }
    return '<span class="fp-reliability-badge ' . $cls . '">' . (int)$score . '</span>';
}

/**
 * Calculates the profile completeness score for a candidate record.
 *
 * Fields are weighted: required fields count more toward the score.
 * Returns an integer 0-100.
 *
 * @param stdClass $candidate The candidate DB record.
 * @return int Score 0-100.
 */
function local_finplacements_calculate_profile_score(stdClass $candidate): int {
    // Fields that use a plain empty-check (total weight without cfa/frm = 85 points).
    $fields = [
        'phone'            => 10,
        'city'             => 5,
        'state'            => 5,
        'experience_band'  => 10,
        'tenth_pct'        => 5,
        'twelfth_pct'      => 5,
        'ug_status'        => 5,
        'ug_field'         => 5,
        'resume_url'       => 20,
        'current_ctc'      => 5,
        'designation'      => 5,
        'current_employer' => 5,
    ];

    $total = 0;
    foreach ($fields as $field => $weight) {
        if (!empty($candidate->$field)) {
            $total += $weight;
        }
    }

    // CFA level (+10): must be set AND not 'none' (sentinel value).
    if (!empty($candidate->cfa_level) && $candidate->cfa_level !== 'none') {
        $total += 10;
    }

    // FRM level (+5): must be set AND not 'none' (sentinel value).
    if (!empty($candidate->frm_level) && $candidate->frm_level !== 'none') {
        $total += 5;
    }

    return min(100, $total);
}

/**
 * Calculates section-level completion scores for the profile.
 *
 * Returns an array of section scores (0-100) for each section:
 *  - personal: Personal Information
 *  - academic: Academic Background
 *  - certifications: Professional Certifications
 *  - work: Work Experience
 *  - documents: Documents
 *
 * @param stdClass $candidate The candidate DB record.
 * @return array Associative array of section_key => ['score' => int, 'total' => int, 'filled' => int].
 */
function local_finplacements_calculate_section_scores(stdClass $candidate): array {
    $sections = [];

    // Personal Information (4 fields, 25 points each = 100 total).
    $personal_fields = ['phone', 'city', 'state', 'experience_band'];
    $personal_filled = 0;
    foreach ($personal_fields as $field) {
        if (!empty($candidate->$field)) {
            $personal_filled++;
        }
    }
    $sections['personal'] = [
        'score' => (int)(($personal_filled / count($personal_fields)) * 100),
        'total' => count($personal_fields),
        'filled' => $personal_filled,
        'label' => 'Personal Information',
    ];

    // Academic Background (8 fields, 12.5 points each = 100 total).
    $academic_fields = ['tenth_pct', 'twelfth_pct', 'ug_status', 'ug_field', 'ug_gpa', 'pg_status', 'pg_field', 'pg_gpa'];
    $academic_filled = 0;
    foreach ($academic_fields as $field) {
        if (!empty($candidate->$field)) {
            $academic_filled++;
        }
    }
    $sections['academic'] = [
        'score' => (int)(($academic_filled / count($academic_fields)) * 100),
        'total' => count($academic_fields),
        'filled' => $academic_filled,
        'label' => 'Academic Background',
    ];

    // Professional Certifications (5 fields, 20 points each = 100 total).
    $certs_fields = ['cfa_level', 'frm_level', 'other_certs', 'fm_cert', 'courses_done'];
    $certs_filled = 0;
    foreach ($certs_fields as $field) {
        if (!empty($candidate->$field) && $candidate->$field !== 'none') {
            $certs_filled++;
        }
    }
    $sections['certifications'] = [
        'score' => (int)(($certs_filled / count($certs_fields)) * 100),
        'total' => count($certs_fields),
        'filled' => $certs_filled,
        'label' => 'Professional Certifications',
    ];

    // Work Experience (4 fields, 25 points each = 100 total).
    $work_fields = ['current_employer', 'current_ctc', 'designation'];
    $work_filled = 0;
    foreach ($work_fields as $field) {
        if (!empty($candidate->$field)) {
            $work_filled++;
        }
    }
    $sections['work'] = [
        'score' => (int)(($work_filled / count($work_fields)) * 100),
        'total' => count($work_fields),
        'filled' => $work_filled,
        'label' => 'Work Experience',
    ];

    // Documents (2 fields, 50 points each = 100 total).
    $docs_fields = ['resume_url', 'photo_url'];
    $docs_filled = 0;
    foreach ($docs_fields as $field) {
        if (!empty($candidate->$field)) {
            $docs_filled++;
        }
    }
    $sections['documents'] = [
        'score' => (int)(($docs_filled / count($docs_fields)) * 100),
        'total' => count($docs_fields),
        'filled' => $docs_filled,
        'label' => 'Documents',
    ];

    return $sections;
}
