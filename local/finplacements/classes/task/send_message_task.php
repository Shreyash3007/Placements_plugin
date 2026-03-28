<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Adhoc task to send a WhatsApp/Email message asynchronously.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../lib.php');

/**
 * Task to process background messaging.
 */
class send_message_task extends \core\task\adhoc_task {

    /**
     * Run the message sending logic in the background cron.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        if (!$data) {
            return;
        }

        mtrace("Executing background message task for Application ID " . ($data->appid ?? 'unknown'));

        $appid = $data->appid ?? null;
        if (!$appid) {
            mtrace("Task failed: Missing appid.");
            return;
        }

        $app = $DB->get_record('finplacements_applications', ['id' => $appid]);
        if (!$app) {
            mtrace("Task failed: Application not found.");
            return;
        }

        $candidate = $DB->get_record('finplacements_candidates', ['id' => $app->candidateid]);
        $opening   = $DB->get_record('finplacements_openings',   ['id' => $app->openingid]);

        if (!$candidate || !$opening) {
            mtrace("Task failed: Candidate or Opening not found.");
            return;
        }

        // 1. WhatsApp Delivery
        $phone = $data->phone ?? $candidate->phone;
        $msg   = $data->msg ?? null;
        if ($phone && $msg) {
            \local_finplacements\whatsapp_mock::send_or_mock($phone, $msg);
            $DB->set_field('finplacements_applications', 'wa_sent', 1, ['id' => $appid]);
            mtrace("WhatsApp successfully dispatched/mocked.");
        }

        // 2. Google Sheets Sync
        \local_finplacements\sheets_sync::sync_shortlisted_candidate($candidate, $opening, $app);
        mtrace("Google Sheets sync completed/mocked.");

        // 3. Optional: Trigger and Email flag
        $DB->set_field('finplacements_applications', 'email_sent', 1, ['id' => $appid]);
    }
}
