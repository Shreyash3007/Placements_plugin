<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin — update a single application's status (Forwarded / Placed / No-Show / Backed Out).
 *
 * POST-only controller. Validates capability, sesskey, then updates status
 * and triggers reliability score events before redirecting back.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\application_manager;
use local_finplacements\reliability_manager;

require_login();

$context = context_system::instance();
require_capability('local/finplacements:admin', $context);

// POST only — reject GET requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(new moodle_url('/local/finplacements/admin/openings.php'));
}

require_sesskey();

$appid      = required_param('appid',      PARAM_INT);
$new_status = required_param('new_status', PARAM_ALPHA);
$openingid  = required_param('openingid',  PARAM_INT);

$back_url = new moodle_url('/local/finplacements/admin/applications.php', ['openingid' => $openingid]);

// Whitelist of statuses this controller may set.
$allowed_statuses = ['forwarded', 'placed', 'no_show', 'backed_out'];

if (!in_array($new_status, $allowed_statuses)) {
    redirect($back_url, 'Invalid status transition.', null, \core\output\notification::NOTIFY_WARNING);
}

// Security: ensure the application belongs to the posted openingid.
$app = application_manager::get_by_id($appid);
if (!$app || (int)$app->openingid !== $openingid) {
    redirect($back_url, get_string('error_notfound', 'local_finplacements'),
             null, \core\output\notification::NOTIFY_ERROR);
}

// Map no_show / backed_out directly to reliability events (they don't change application status).
if ($new_status === 'no_show') {
    reliability_manager::update_score($app->candidateid, 'no_show');
    redirect($back_url,
        'Candidate marked as no-show. Reliability score updated (-10).',
        null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($new_status === 'backed_out') {
    reliability_manager::update_score($app->candidateid, 'backed_out');
    redirect($back_url,
        'Candidate marked as backed out. Reliability score updated (-20).',
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// For forwarded / placed: update the application status (which also triggers reliability via update_status).
application_manager::update_status($appid, $new_status, $USER->id);

$label = ucfirst($new_status);
redirect($back_url,
    "Application status updated to {$label}.",
    null, \core\output\notification::NOTIFY_SUCCESS);
