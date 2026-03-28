<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Google Sheets sync mock class.
 *
 * Generates exactly the row that would be written to FinTree's recruiter sheet
 * and logs it to finplacements_sheets_log. When a webhook URL is configured,
 * the same data is POSTed to a Google Apps Script endpoint.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements;

defined('MOODLE_INTERNAL') || die();

/**
 * Google Sheets integration mock layer.
 */
class sheets_sync {

    /**
     * Syncs a shortlisted candidate to the recruiter Google Sheet (or logs mock row).
     *
     * Row format exactly matches FinTree's existing recruiter sheet columns.
     *
     * @param stdClass $candidate   Candidate record.
     * @param stdClass $opening     Opening record.
     * @param stdClass $application Application record.
     * @return array Result array with 'status' and 'row' keys.
     */
    public static function sync_shortlisted_candidate(
        \stdClass $candidate,
        \stdClass $opening,
        \stdClass $application
    ): array {
        global $DB;

        // Build the row exactly matching FinTree's recruiter sheet format.
        $row = [
            'Sr. No.'          => $application->id,
            'Broadcast Date'   => $opening->broadcast_date
                                    ? date('d-m-Y', $opening->broadcast_date)
                                    : '—',
            'Company Name'     => $opening->company,
            'Position'         => $opening->title,
            'Location'         => $opening->location,
            'Form Closure Date'=> date('d-m-Y', $opening->deadline),
            'Candidate Name'   => $candidate->fullname,
            'Candidate Number' => $candidate->phone,
            'Shortlisted Date' => $application->shortlisted_date
                                    ? date('d-m-Y', $application->shortlisted_date)
                                    : date('d-m-Y'),
            'Status'           => ucfirst($application->status),
            'Auto Score'       => $application->auto_score,
        ];

        $webhook_url = get_config('local_finplacements', 'sheets_webhook_url');
        if (!empty($webhook_url)) {
            // REAL: POST to Apps Script webhook.
            // When going live, uncomment:
            // self::post_to_webhook($webhook_url, $row);
        }

        // MOCK: log the row to finplacements_sheets_log.
        $record              = new \stdClass();
        $record->row_data    = json_encode($row);
        $record->timecreated = time();
        $record->status      = 'mock_logged';

        $DB->insert_record('finplacements_sheets_log', $record);

        return [
            'status' => 'mocked',
            'row'    => $row,
        ];
    }

    /**
     * Syncs multiple shortlisted candidates at once.
     *
     * @param array    $candidates   Array of candidate records.
     * @param stdClass $opening      Opening record.
     * @param array    $applications Array of application records, keyed by candidateid.
     * @return array Array of result arrays, one per candidate.
     */
    public static function sync_batch(
        array $candidates,
        \stdClass $opening,
        array $applications
    ): array {
        $results = [];
        foreach ($candidates as $candidate) {
            $app = $applications[$candidate->id] ?? null;
            if ($app) {
                $results[] = self::sync_shortlisted_candidate($candidate, $opening, $app);
            }
        }
        return $results;
    }

    /**
     * POSTs data to a Google Apps Script webhook (used when going live).
     *
     * @param string $url Webhook URL.
     * @param array  $row Row data.
     * @return void
     */
    private static function post_to_webhook(string $url, array $row): void {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($row),
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($curl);
        curl_close($curl);
    }
}
