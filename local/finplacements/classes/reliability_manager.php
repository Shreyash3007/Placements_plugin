<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Reliability manager — tracks and updates candidate reliability scores.
 *
 * Score rules:
 *   +5  Shortlisted
 *   +10 Forwarded to company
 *   +15 Placed (got the job)
 *   -20 Offer backed out
 *   -10 No-show to interview
 *   Floor: 0, Ceiling: 100, Default: 70
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages reliability scores for candidates.
 */
class reliability_manager {

    /** @var array Score change per event. */
    private static $changes = [
        'shortlisted' => +5,
        'forwarded'   => +10,
        'placed'      => +15,
        'backed_out'  => -20,
        'no_show'     => -10,
    ];

    /**
     * Ensures a reliability record exists for the given candidate.
     * Creates one with the default score of 70 if missing.
     *
     * @param int $candidateid Candidate record ID.
     * @return stdClass The reliability record.
     */
    public static function ensure_record(int $candidateid): \stdClass {
        global $DB;

        $existing = $DB->get_record('finplacements_reliability', ['candidateid' => $candidateid]);
        if ($existing) {
            return $existing;
        }

        $record                    = new \stdClass();
        $record->candidateid       = $candidateid;
        $record->score             = 70; // Default score.
        $record->times_applied     = 0;
        $record->times_shortlisted = 0;
        $record->times_forwarded   = 0;
        $record->times_placed      = 0;
        $record->offers_backed_out = 0;
        $record->no_shows          = 0;
        $record->last_updated      = time();

        $id = $DB->insert_record('finplacements_reliability', $record);
        return $DB->get_record('finplacements_reliability', ['id' => $id]);
    }

    /**
     * Retrieves the reliability record for a candidate.
     *
     * @param int $candidateid Candidate record ID.
     * @return stdClass|false
     */
    public static function get_by_candidateid(int $candidateid) {
        global $DB;
        return $DB->get_record('finplacements_reliability', ['candidateid' => $candidateid]);
    }

    /**
     * Increments the 'times_applied' counter.
     *
     * @param int $candidateid Candidate record ID.
     * @return void
     */
    public static function increment_applied(int $candidateid): void {
        global $DB;
        $record = self::ensure_record($candidateid);
        $record->times_applied = (int)$record->times_applied + 1;
        $record->last_updated  = time();
        $DB->update_record('finplacements_reliability', $record);
    }

    /**
     * Updates the reliability score based on a key event.
     *
     * @param int    $candidateid Candidate record ID.
     * @param string $event       Event key: shortlisted|forwarded|placed|backed_out|no_show
     * @return void
     */
    public static function update_score(int $candidateid, string $event): void {
        global $DB;

        if (!array_key_exists($event, self::$changes)) {
            return; // Unknown event — do nothing.
        }

        $record = self::ensure_record($candidateid);

        $change        = self::$changes[$event];
        $new_score     = (int)$record->score + $change;
        $new_score     = max(0, min(100, $new_score)); // Clamp 0-100.

        $record->score        = $new_score;
        $record->last_updated = time();

        // Update event counters.
        switch ($event) {
            case 'shortlisted':
                $record->times_shortlisted = (int)$record->times_shortlisted + 1;
                break;
            case 'forwarded':
                $record->times_forwarded = (int)$record->times_forwarded + 1;
                break;
            case 'placed':
                $record->times_placed = (int)$record->times_placed + 1;
                break;
            case 'backed_out':
                $record->offers_backed_out = (int)$record->offers_backed_out + 1;
                break;
            case 'no_show':
                $record->no_shows = (int)$record->no_shows + 1;
                break;
        }

        $DB->update_record('finplacements_reliability', $record);
    }

    /**
     * Returns the current score for a candidate, or 70 if no record exists.
     *
     * @param int $candidateid Candidate record ID.
     * @return int Score 0-100.
     */
    public static function get_score(int $candidateid): int {
        global $DB;
        $record = $DB->get_record('finplacements_reliability', ['candidateid' => $candidateid]);
        return $record ? (int)$record->score : 70;
    }
}
