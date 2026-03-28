<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Candidate manager — CRUD operations for candidate profiles.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages candidate profile creation, retrieval, and updates.
 */
class candidate_manager {

    /**
     * Retrieves a candidate profile by Moodle user ID.
     *
     * @param int $userid Moodle user ID.
     * @return stdClass|false The candidate record, or false if none exists.
     */
    public static function get_by_userid(int $userid) {
        global $DB;
        return $DB->get_record('finplacements_candidates', ['userid' => $userid]);
    }

    /**
     * Retrieves a candidate profile by its own record ID.
     *
     * @param int $id Candidate record ID.
     * @return stdClass|false
     */
    public static function get_by_id(int $id) {
        global $DB;
        return $DB->get_record('finplacements_candidates', ['id' => $id]);
    }

    /**
     * Creates or updates a candidate profile from form data.
     *
     * @param int       $userid Moodle user ID.
     * @param stdClass  $data   Form data object.
     * @return int The candidate record ID.
     */
    public static function save_profile(int $userid, \stdClass $data): int {
        global $DB, $USER;

        $existing = self::get_by_userid($userid);

        $record = new \stdClass();
        $record->userid           = $userid;
        $record->fullname         = $data->fullname ?? fullname($USER);
        $record->phone            = $data->phone ?? '';
        $record->email            = $data->email ?? $USER->email;
        $record->city             = $data->city ?? '';
        $record->state            = $data->state ?? '';
        $record->experience_band  = $data->experience_band ?? '';
        $record->current_employer = $data->current_employer ?? null;
        $record->current_ctc      = $data->current_ctc ?? null;
        $record->designation      = $data->designation ?? null;
        $record->tenth_pct        = $data->tenth_pct ?? null;
        $record->twelfth_pct      = $data->twelfth_pct ?? null;
        $record->ug_status        = $data->ug_status ?? null;
        $record->ug_field         = $data->ug_field ?? null;
        $record->ug_gpa           = $data->ug_gpa ?? null;
        $record->pg_status        = $data->pg_status ?? null;
        $record->pg_field         = $data->pg_field ?? null;
        $record->pg_gpa           = $data->pg_gpa ?? null;
        $record->cfa_level        = $data->cfa_level ?? null;
        $record->frm_level        = $data->frm_level ?? null;
        $record->other_certs      = $data->other_certs ?? null;
        $record->fm_cert          = isset($data->fm_cert) ? (int)$data->fm_cert : 0;
        $record->courses_done     = $data->courses_done ?? null;
        $record->resume_url       = $data->resume_url ?? null;
        $record->photo_url        = $data->photo_url ?? null;
        $record->timemodified     = time();

        // Calculate profile completeness score.
        $score = \local_finplacements_calculate_profile_score($record);
        $record->profile_score    = $score;
        $record->profile_complete = ($score >= 80) ? 1 : 0;

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('finplacements_candidates', $record);
            return $existing->id;
        } else {
            $record->timecreated = time();
            return $DB->insert_record('finplacements_candidates', $record);
        }
    }

    /**
     * Returns a list of all candidates (for admin use).
     *
     * @return array Array of candidate stdClass records.
     */
    public static function get_all(): array {
        global $DB;
        return $DB->get_records('finplacements_candidates', null, 'timecreated DESC');
    }
}
