<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Web service definitions for local_finplacements.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_finplacements_get_filtered_jobs' => [
        'classname'   => 'local_finplacements\external',
        'methodname'  => 'get_filtered_jobs',
        'classpath'   => '',
        'description' => 'Get filtered job openings based on search criteria',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE => false],
    ],
    'local_finplacements_update_application_status' => [
        'classname'   => 'local_finplacements\external',
        'methodname'  => 'update_application_status',
        'classpath'   => '',
        'description' => 'Update application status (admin only)',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE => false],
    ],
    'local_finplacements_get_tracker_applications' => [
        'classname'   => 'local_finplacements\external',
        'methodname'  => 'get_tracker_applications',
        'classpath'   => '',
        'description' => 'Get filtered applications for candidate tracker',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE => false],
    ],
];

$services = [];
