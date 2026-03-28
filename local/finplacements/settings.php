<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin settings page for local_finplacements.
 *
 * Accessible via: Site Administration → Plugins → Local plugins → FinTree Placements
 *
 * Settings defined here:
 *   - whatsapp_api_url   : WhatsApp Business API endpoint (leave blank to stay in mock mode)
 *   - sheets_webhook_url : Google Apps Script webhook URL (leave blank to stay in mock mode)
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a settings page under Plugins → Local plugins.
    $settings = new admin_settingpage(
        'local_finplacements',
        get_string('pluginname', 'local_finplacements')
    );

    // -----------------------------------------------------------------------
    // Section: WhatsApp Integration
    // -----------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'local_finplacements/wa_heading',
        'WhatsApp Integration',
        'Leave blank to keep mock mode active. When a URL is provided, messages are sent live.'
    ));

    $settings->add(new admin_setting_configtext(
        'local_finplacements/whatsapp_api_url',
        'WhatsApp API Endpoint URL',
        'The full URL of your WhatsApp Business API endpoint (e.g. https://api.yourprovider.com/send). '
        . 'Leave empty to use mock mode (messages logged to DB only).',
        '',          // default value
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_finplacements/whatsapp_api_token',
        'WhatsApp API Token / Key',
        'Bearer token or API key required by your WhatsApp API provider. Keep this secret.',
        '',
        PARAM_RAW_TRIMMED
    ));

    // -----------------------------------------------------------------------
    // Section: Google Sheets Integration
    // -----------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'local_finplacements/sheets_heading',
        'Google Sheets Integration',
        'Leave blank to keep mock mode active. When a URL is provided, shortlisted rows are synced live.'
    ));

    $settings->add(new admin_setting_configtext(
        'local_finplacements/sheets_webhook_url',
        'Google Sheets Webhook URL',
        'The Apps Script web app URL that accepts POST requests and appends rows to the recruiter sheet. '
        . 'Leave empty to use mock mode (rows logged to DB only).',
        '',
        PARAM_URL
    ));

    // -----------------------------------------------------------------------
    // Section: Behaviour
    // -----------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'local_finplacements/behaviour_heading',
        'Behaviour Settings',
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_finplacements/min_profile_score_to_apply',
        'Minimum Profile Score to Apply',
        'Candidates need at least this profile completeness score (0–100) to apply. Default: 50.',
        '50',
        PARAM_INT
    ));

    // Register the settings page under the Local plugins category.
    $ADMIN->add('localplugins', $settings);
}
