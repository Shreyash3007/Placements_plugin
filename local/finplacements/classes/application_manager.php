<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Application manager — handles candidate applications and auto-scoring.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages application creation, retrieval, status updates, and auto-scoring.
 */
class application_manager {

    /**
     * Calculates the fit score between a candidate and an opening.
     *
     * Score breakdown (total 100 points):
     *   Location match:         +25
     *   Experience band match:  +25
     *   CFA level match:        +20
     *   FRM level match:        +10
     *   CTC within range:       +10
     *   Profile completeness:   +10
     *
     * @param stdClass $candidate Candidate record.
     * @param stdClass $opening   Opening record.
     * @return int Score 0-100.
     */
    public static function calculate_fit_score(\stdClass $candidate, \stdClass $opening): int {
        $score = 0;

        // Location match (+25).
        if (!empty($opening->location) && !empty($candidate->city)) {
            if (stripos($opening->location, $candidate->city) !== false ||
                stripos($candidate->city, $opening->location) !== false) {
                $score += 25;
            }
        }

        // Experience band match (+25).
        if ($opening->experience_required === 'any' ||
            $opening->experience_required === $candidate->experience_band) {
            $score += 25;
        }

        // CFA level match (+20).
        if (!empty($opening->cfa_required) && $opening->cfa_required !== 'none') {
            $cfa_levels = ['none' => 0, 'level1' => 1, 'level2' => 2, 'level3' => 3, 'charter' => 4];
            $required   = $cfa_levels[$opening->cfa_required] ?? 0;
            $has        = $cfa_levels[$candidate->cfa_level] ?? 0;
            if ($has >= $required) {
                $score += 20;
            }
        } else {
            $score += 20; // No CFA required = full points.
        }

        // FRM level match (+10).
        if (!empty($opening->frm_required) && $opening->frm_required !== 'none') {
            $frm_levels = ['none' => 0, 'part1' => 1, 'part2' => 2, 'charter' => 3];
            $required   = $frm_levels[$opening->frm_required] ?? 0;
            $has        = $frm_levels[$candidate->frm_level] ?? 0;
            if ($has >= $required) {
                $score += 10;
            }
        } else {
            $score += 10; // No FRM required = full points.
        }

        // CTC match (+10) — skip for internships.
        if ($opening->employment_type === 'fulltime' &&
            !empty($opening->min_ctc) && !empty($candidate->current_ctc)) {
            $candidate_ctc = (int) preg_replace('/[^0-9]/', '', $candidate->current_ctc);
            $min_ctc       = (int) preg_replace('/[^0-9]/', '', $opening->min_ctc);
            if ($min_ctc > 0 && $candidate_ctc <= $min_ctc * 1.3) {
                $score += 10;
            }
        } else {
            $score += 10; // Internship or no CTC data = full points.
        }

        // Profile completeness (+10).
        if ((int)($candidate->profile_score ?? 0) >= 80) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Creates a new application record.
     *
     * Checks for duplicates before inserting, calculates auto-score,
     * and initialises the reliability record if needed.
     *
     * @param int $candidateid Candidate record ID.
     * @param int $openingid   Opening record ID.
     * @return int|false New application ID, or false if duplicate / validation fails.
     */
    public static function apply(int $candidateid, int $openingid) {
        global $DB;

        // Duplicate check.
        if (self::has_applied($candidateid, $openingid)) {
            return false;
        }

        $candidate = $DB->get_record('finplacements_candidates', ['id' => $candidateid]);
        $opening   = $DB->get_record('finplacements_openings',   ['id' => $openingid]);

        if (!$candidate || !$opening) {
            return false;
        }

        $score = self::calculate_fit_score($candidate, $opening);

        $record                = new \stdClass();
        $record->candidateid   = $candidateid;
        $record->openingid     = $openingid;
        $record->applied_date  = time();
        $record->status        = 'applied';
        $record->auto_score    = $score;
        $record->wa_sent       = 0;
        $record->email_sent    = 0;

        $appid = $DB->insert_record('finplacements_applications', $record);

        // Initialise or update the reliability counter.
        reliability_manager::ensure_record($candidateid);
        reliability_manager::increment_applied($candidateid);

        // --- INSTITUTIONAL UPGRADE: Trigger Native Moodle Event ---
        $event = \local_finplacements\event\application_submitted::create([
            'objectid' => $appid,
            'context'  => \context_system::instance(),
            'other'    => [
                'openingid'   => $openingid,
                'candidateid' => $candidateid
            ]
        ]);
        $event->trigger();

        return $appid;
    }

    /**
     * Checks whether a candidate has already applied to an opening.
     *
     * @param int $candidateid Candidate record ID.
     * @param int $openingid   Opening record ID.
     * @return bool
     */
    public static function has_applied(int $candidateid, int $openingid): bool {
        global $DB;
        return $DB->record_exists('finplacements_applications', [
            'candidateid' => $candidateid,
            'openingid'   => $openingid,
        ]);
    }

    /**
     * Returns all applications for a given opening, joined with candidate data.
     *
     * Supports optional SQL-level filters (city, experience_band, min_score)
     * and sortable column to avoid PHP-level array_filter on large datasets.
     *
     * @param int    $openingid Opening record ID.
     * @param string $city      Optional city substring filter.
     * @param string $exp       Optional experience_band exact match.
     * @param int    $min_score Optional minimum auto_score.
     * @param string $sort      Sort column: auto_score|reliability_score|fullname|applied_date.
     * @param string $dir       Sort direction: DESC|ASC.
     * @return array Array of objects with merged application + candidate fields.
     */
    public static function get_for_opening(
        int $openingid,
        string $city = '',
        string $exp = '',
        int $min_score = 0,
        string $sort = 'auto_score',
        string $dir = 'DESC'
    ): array {
        global $DB;

        // Whitelist sort columns to prevent SQL injection.
        $valid_sorts = ['auto_score', 'reliability_score', 'fullname', 'applied_date'];
        if (!in_array($sort, $valid_sorts)) {
            $sort = 'auto_score';
        }
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $where  = ['a.openingid = :openingid'];
        $params = ['openingid' => $openingid];

        if (!empty($city)) {
            $where[]       = $DB->sql_like('c.city', ':city', false);
            $params['city'] = '%' . $DB->sql_like_escape($city) . '%';
        }

        if (!empty($exp)) {
            $where[]      = 'c.experience_band = :exp';
            $params['exp'] = $exp;
        }

        if ($min_score > 0) {
            $where[]           = 'a.auto_score >= :min_score';
            $params['min_score'] = $min_score;
        }

        // Map reliability_score to the actual alias for ORDER BY.
        $order_by = ($sort === 'reliability_score') ? 'r.score' : "a.{$sort}";
        if ($sort === 'fullname') {
            $order_by = 'c.fullname';
        }

        $sql = "SELECT a.*, c.fullname, c.phone, c.email, c.city, c.state,
                       c.experience_band, c.cfa_level, c.frm_level, c.current_ctc,
                       c.resume_url, c.profile_score, c.designation,
                       r.score AS reliability_score
                  FROM {finplacements_applications} a
                  JOIN {finplacements_candidates}   c ON c.id = a.candidateid
             LEFT JOIN {finplacements_reliability}  r ON r.candidateid = c.id
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY {$order_by} {$dir}, a.applied_date ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns all applications for a given candidate (tracker view).
     *
     * @param int    $candidateid Candidate record ID.
     * @param string $status      Optional status filter.
     * @param string $search      Optional search keyword (company/title).
     * @param string $sort        Sort order: 'date', 'status', 'deadline'.
     * @return array
     */
    public static function get_for_candidate(
        int $candidateid,
        string $status = '',
        string $search = '',
        string $sort = 'date'
    ): array {
        global $DB;

        $where = ['a.candidateid = :candidateid'];
        $params = ['candidateid' => $candidateid];

        // Status filter.
        if (!empty($status) && $status !== 'all') {
            $where[] = 'a.status = :status';
            $params['status'] = $status;
        }

        // Search filter (company or title).
        if (!empty($search)) {
            $kw = '%' . $DB->sql_like_escape($search) . '%';
            $where[] = '(' . $DB->sql_like('o.company', ':kw1', false)
                     . ' OR ' . $DB->sql_like('o.title', ':kw2', false) . ')';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
        }

        $where_sql = implode(' AND ', $where);

        // Build ORDER BY clause based on sort option.
        $orderby = match($sort) {
            'status' => 'a.status ASC, a.applied_date DESC',
            'deadline' => 'o.deadline ASC',
            default => 'a.applied_date DESC', // date
        };

        $sql = "SELECT a.*, o.title, o.company, o.location, o.deadline, o.jobid,
                       a.shortlisted_date, a.timemodified
                  FROM {finplacements_applications} a
                  JOIN {finplacements_openings} o ON o.id = a.openingid
                 WHERE $where_sql
                 ORDER BY $orderby";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Updates the status of an application.
     *
     * Also triggers reliability score updates for relevant events.
     *
     * @param int    $appid  Application record ID.
     * @param string $status New status.
     * @param int    $byuser Moodle user ID performing the action.
     * @return void
     */
    public static function update_status(int $appid, string $status, int $byuser): void {
        global $DB;

        $app = $DB->get_record('finplacements_applications', ['id' => $appid]);
        if (!$app) {
            return;
        }

        $update         = new \stdClass();
        $update->id     = $appid;
        $update->status = $status;

        if ($status === 'shortlisted') {
            $update->shortlisted_by   = $byuser;
            $update->shortlisted_date = time();
        }

        $DB->update_record('finplacements_applications', $update);

        // Update reliability score for key events.
        $reliability_events = [
            'shortlisted' => 'shortlisted',
            'forwarded'   => 'forwarded',
            'placed'      => 'placed',
        ];

        if (isset($reliability_events[$status])) {
            reliability_manager::update_score($app->candidateid, $reliability_events[$status]);
        }

        // --- INSTITUTIONAL UPGRADE: Trigger Native Moodle Event for Shortlisting ---
        if ($status === 'shortlisted') {
            $event = \local_finplacements\event\candidate_shortlisted::create([
                'objectid' => $appid,
                'context'  => \context_system::instance(),
                'other'    => [
                    'openingid'   => $app->openingid,
                    'candidateid' => $app->candidateid
                ]
            ]);
            $event->trigger();
        }
    }

    /**
     * Bulk-updates a list of applications to 'shortlisted'.
     *
     * @param array $appids Array of application IDs.
     * @param int   $byuser Moodle user ID performing the action.
     * @return void
     */
    public static function bulk_shortlist(array $appids, int $byuser): void {
        foreach ($appids as $appid) {
            self::update_status((int)$appid, 'shortlisted', $byuser);
        }
    }

    /**
     * Bulk-rejects a list of applications.
     *
     * @param array $appids Array of application IDs.
     * @param int   $byuser Moodle user ID performing the action.
     * @return void
     */
    public static function bulk_reject(array $appids, int $byuser): void {
        foreach ($appids as $appid) {
            self::update_status((int)$appid, 'rejected', $byuser);
        }
    }

    /**
     * Returns a single application record by ID.
     *
     * @param int $appid Application record ID.
     * @return stdClass|false
     */
    public static function get_by_id(int $appid) {
        global $DB;
        return $DB->get_record('finplacements_applications', ['id' => $appid]);
    }

    /**
     * Returns ALL applications across all openings (admin overview).
     *
     * @return array Array of objects with merged application + candidate + opening fields.
     */
    public static function get_all_applications(): array {
        global $DB;

        $sql = "SELECT a.*, c.fullname, c.phone, c.email, c.city, c.state,
                       c.experience_band, c.cfa_level, c.frm_level, c.current_ctc,
                       c.resume_url, c.profile_score, c.designation,
                       o.title AS opening_title, o.company, o.jobid,
                       r.score AS reliability_score
                  FROM {finplacements_applications} a
                  JOIN {finplacements_candidates}   c ON c.id = a.candidateid
                  JOIN {finplacements_openings}     o ON o.id = a.openingid
             LEFT JOIN {finplacements_reliability}  r ON r.candidateid = c.id
              ORDER BY a.auto_score DESC, a.applied_date ASC";

        return $DB->get_records_sql($sql, []);
    }

    /**
     * Returns count of applications for a given opening.
     *
     * @param int $openingid Opening ID.
     * @return int
     */
    public static function count_for_opening(int $openingid): int {
        global $DB;
        return (int)$DB->count_records('finplacements_applications', ['openingid' => $openingid]);
    }
}
