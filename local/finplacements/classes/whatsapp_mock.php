<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * WhatsApp mock class — generates and logs outbound messages.
 *
 * When a real API URL is configured via plugin settings, this class
 * is a drop-in for making the live API call. Until then, all messages
 * are logged to finplacements_wa_log for review.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements;

defined('MOODLE_INTERNAL') || die();

/**
 * WhatsApp integration mock layer.
 */
class whatsapp_mock {

    /**
     * Generates the exact pre-screening WhatsApp message in FinTree's standard format.
     *
     * @param string $candidate_name Candidate's full name.
     * @param string $company        Company name.
     * @param string $role           Job title / role.
     * @param string $location       Job location.
     * @return string WhatsApp-formatted message text.
     */
    public static function generate_prescreen_message(
        string $candidate_name,
        string $company,
        string $role,
        string $location
    ): string {
        return "*Subject: Application Update – {$role}*\n\n"
             . "Hello {$candidate_name},\n\n"
             . "You have been successfully pre-screened by FinTree, and your resume "
             . "has been forwarded to the hiring company for further consideration.\n\n"
             . "Company Name: *{$company}*\n"
             . "Role: *{$role}*\n"
             . "Location: *{$location}*\n\n"
             . "The company will contact you directly regarding the next steps "
             . "in the selection process.\n\n"
             . "Please ensure that you are available to respond to their communication.\n\n"
             . "Best regards,\n"
             . "*FinTree Career Services Team*";
    }

    /**
     * Sends a WhatsApp message or logs it if no API is configured.
     *
     * To go live: set the 'whatsapp_api_url' plugin config to the real API endpoint.
     * The live send block (currently commented) will then fire automatically.
     *
     * @param string $phone   Recipient phone number with country code.
     * @param string $message Message text.
     * @return array Result array with 'status' and 'message' keys.
     */
    public static function send_or_mock(string $phone, string $message): array {
        global $DB;

        // Check if real API config exists.
        $api_url = get_config('local_finplacements', 'whatsapp_api_url');
        if (!empty($api_url)) {
            // REAL: fire actual API call here when credentials are configured.
            // Uncomment and complete when going live:
            // return self::send_real($api_url, $phone, $message);
        }

        // MOCK: log the message to finplacements_wa_log.
        $record              = new \stdClass();
        $record->phone       = $phone;
        $record->message     = $message;
        $record->timecreated = time();
        $record->status      = 'mock_logged';

        $DB->insert_record('finplacements_wa_log', $record);

        return [
            'status'  => 'mocked',
            'message' => $message,
        ];
    }

    /**
     * Sends WhatsApp messages to all shortlisted candidates for an opening.
     *
     * @param array     $candidates Array of candidate stdClass objects (must have phone, fullname).
     * @param stdClass  $opening    Opening record (must have company, title, location).
     * @return array Array of result arrays, one per candidate.
     */
    public static function send_batch(array $candidates, \stdClass $opening): array {
        $results = [];
        foreach ($candidates as $candidate) {
            $message   = self::generate_prescreen_message(
                $candidate->fullname,
                $opening->company,
                $opening->title,
                $opening->location
            );
            $results[] = self::send_or_mock($candidate->phone, $message);
        }
        return $results;
    }
}
