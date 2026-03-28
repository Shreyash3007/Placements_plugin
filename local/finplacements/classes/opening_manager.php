<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Opening manager — CRUD operations for job openings.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages job opening creation, retrieval, updates, and Job ID generation.
 */
class opening_manager {

    /**
     * Builds the final Job ID string from a known DB row ID and broadcast timestamp.
     *
     * The job ID is derived from the actual auto-increment row ID (guaranteed unique),
     * eliminating any race condition between simultaneous saves.
     *
     * @param int      $row_id              The DB row id of the opening.
     * @param int|null $broadcast_timestamp Unix timestamp for broadcast date.
     * @return string Job ID e.g. "FNTR-260326-A304".
     */
    public static function build_job_id(int $row_id, ?int $broadcast_timestamp = null): string {
        $ts        = $broadcast_timestamp ?? time();
        $date_part = date('ymd', $ts);
        $seq       = $row_id + 303; // +303 offset so row id=1 → A304.
        return 'FNTR-' . $date_part . '-A' . $seq;
    }

    /**
     * Retrieves a single opening by ID.
     *
     * @param int $id Opening ID.
     * @return stdClass|false
     */
    public static function get_by_id(int $id) {
        global $DB;
        return $DB->get_record('finplacements_openings', ['id' => $id]);
    }

    /**
     * Retrieves all openings, optionally filtered by status.
     *
     * @param string|null $status Status filter: 'draft', 'active', 'closed', or null for all.
     * @return array
     */
    public static function get_all(?string $status = null): array {
        global $DB;

        $params = [];
        if ($status !== null) {
            $params['status'] = $status;
        }
        return $DB->get_records('finplacements_openings', $params, 'timecreated DESC');
    }

    /**
     * Returns only active openings visible on the job board.
     *
     * Supports optional SQL-level filters for location, employment_type, keyword (with weighted search),
     * experience band, CFA/FRM requirements, and sorting options.
     *
     * @param string $location       Optional location substring filter.
     * @param string $type           Optional employment type filter ('fulltime'|'internship'|'').
     * @param string $keyword        Optional keyword to search in title, company, or JD.
     * @param string $sort           Sort order: 'recent', 'deadline', 'company', 'title', 'relevance'.
     * @param string $exp_band       Optional experience band filter.
     * @param bool   $cfa_only       If true, only show openings requiring CFA.
     * @param bool   $frm_only       If true, only show openings requiring FRM.
     * @param int    $page           Page number for pagination (0-based).
     * @param int    $perpage        Number of records per page (0 = no limit).
     * @return array Array of opening records.
     */
    public static function get_active(
        string $location = '',
        string $type = '',
        string $keyword = '',
        string $sort = 'recent',
        string $exp_band = '',
        bool $cfa_only = false,
        bool $frm_only = false,
        int $page = 0,
        int $perpage = 0
    ): array {
        global $DB;

        $where  = ['o.status = :status'];
        $params = ['status' => 'active'];

        // Location filter.
        if (!empty($location)) {
            $where[]            = $DB->sql_like('o.location', ':location', false);
            $params['location'] = '%' . $DB->sql_like_escape($location) . '%';
        }

        // Employment type filter.
        if (!empty($type)) {
            $where[]         = 'o.employment_type = :emptype';
            $params['emptype'] = $type;
        }

        // Keyword search with weighted relevance (title > company > JD).
        if (!empty($keyword)) {
            $kw = '%' . $DB->sql_like_escape($keyword) . '%';
            $where[] = '(' . $DB->sql_like('o.title', ':kwtitle', false)
                    . ' OR ' . $DB->sql_like('o.company', ':kwcompany', false)
                    . ' OR ' . $DB->sql_like('o.jd_text', ':kwjd', false) . ')';
            $params['kwtitle']   = $kw;
            $params['kwcompany'] = $kw;
            $params['kwjd']      = $kw;
        }

        // Experience band filter (include 'any' as matching option).
        if (!empty($exp_band)) {
            $where[] = '(o.experience_required = :exp OR o.experience_required = :any)';
            $params['exp'] = $exp_band;
            $params['any'] = 'any';
        }

        // CFA required filter.
        if ($cfa_only) {
            $where[] = 'o.cfa_required IS NOT NULL AND o.cfa_required != :cfa_none';
            $params['cfa_none'] = 'none';
        }

        // FRM required filter.
        if ($frm_only) {
            $where[] = 'o.frm_required IS NOT NULL AND o.frm_required != :frm_none';
            $params['frm_none'] = 'none';
        }

        $where_sql = implode(' AND ', $where);

        // Build ORDER BY clause based on sort option.
        $orderby = match($sort) {
            'deadline' => 'o.deadline ASC',
            'company'  => 'o.company ASC',
            'title'    => 'o.title ASC',
            'relevance' => 'relevance_score DESC, o.timecreated DESC',
            default    => 'o.timecreated DESC', // recent
        };

        // For relevance sorting, we need to calculate the score in SELECT.
        // Also JOIN with applications table to get applicant counts (eliminates N+1 query).
        $select = 'o.*, COUNT(a.id) AS applicant_count';
        if ($sort === 'relevance' && !empty($keyword)) {
            $select .= ', (CASE
                WHEN ' . $DB->sql_like('o.title', ':rkw1') . ' THEN 3
                WHEN ' . $DB->sql_like('o.company', ':rkw2') . ' THEN 2
                WHEN ' . $DB->sql_like('o.jd_text', ':rkw3') . ' THEN 1
                ELSE 0
            END) AS relevance_score';
            $params['rkw1'] = $kw;
            $params['rkw2'] = $kw;
            $params['rkw3'] = $kw;
        }

        $sql = "SELECT $select FROM {finplacements_openings} o
                LEFT JOIN {finplacements_applications} a ON a.openingid = o.id
                WHERE $where_sql
                GROUP BY o.id
                ORDER BY $orderby";

        // Pagination: limit and offset.
        $limitfrom = $page * $perpage;

        return $DB->get_records_sql($sql, $params, $limitfrom, $perpage);
    }

    /**
     * Returns count of active openings matching the given filters.
     *
     * Used for pagination calculations.
     *
     * @param string $location  Optional location filter.
     * @param string $type      Optional employment type filter.
     * @param string $keyword   Optional keyword filter.
     * @param string $exp_band  Optional experience band filter.
     * @param bool   $cfa_only  If true, only count openings requiring CFA.
     * @param bool   $frm_only  If true, only count openings requiring FRM.
     * @return int Count of matching openings.
     */
    public static function count_active(
        string $location = '',
        string $type = '',
        string $keyword = '',
        string $exp_band = '',
        bool $cfa_only = false,
        bool $frm_only = false
    ): int {
        global $DB;

        $where  = ['o.status = :status'];
        $params = ['status' => 'active'];

        if (!empty($location)) {
            $where[]            = $DB->sql_like('o.location', ':location', false);
            $params['location'] = '%' . $DB->sql_like_escape($location) . '%';
        }

        if (!empty($type)) {
            $where[]         = 'o.employment_type = :emptype';
            $params['emptype'] = $type;
        }

        if (!empty($keyword)) {
            $kw = '%' . $DB->sql_like_escape($keyword) . '%';
            $where[] = '(' . $DB->sql_like('o.title', ':kwtitle', false)
                    . ' OR ' . $DB->sql_like('o.company', ':kwcompany', false)
                    . ' OR ' . $DB->sql_like('o.jd_text', ':kwjd', false) . ')';
            $params['kwtitle']   = $kw;
            $params['kwcompany'] = $kw;
            $params['kwjd']      = $kw;
        }

        if (!empty($exp_band)) {
            $where[] = '(o.experience_required = :exp OR o.experience_required = :any)';
            $params['exp'] = $exp_band;
            $params['any'] = 'any';
        }

        if ($cfa_only) {
            $where[] = 'o.cfa_required IS NOT NULL AND o.cfa_required != :cfa_none';
            $params['cfa_none'] = 'none';
        }

        if ($frm_only) {
            $where[] = 'o.frm_required IS NOT NULL AND o.frm_required != :frm_none';
            $params['frm_none'] = 'none';
        }

        $where_sql = implode(' AND ', $where);

        // Count distinct openings (GROUP BY not needed for COUNT).
        $sql = "SELECT COUNT(DISTINCT o.id) FROM {finplacements_openings} o WHERE $where_sql";

        return (int)$DB->get_field_sql($sql, $params);
    }

    /**
     * Returns all openings with their application counts in a single SQL query.
     *
     * Replaces the N+1 pattern where get_application_count() was called per row.
     *
     * @param string|null $status Optional status filter.
     * @return array Each record has all opening fields plus app_count.
     */
    public static function get_all_with_counts(?string $status = null): array {
        global $DB;

        $where  = [];
        $params = [];

        if ($status !== null) {
            $where[]          = 'o.status = :status';
            $params['status'] = $status;
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT o.*, COUNT(a.id) AS app_count
                  FROM {finplacements_openings} o
             LEFT JOIN {finplacements_applications} a ON a.openingid = o.id
                       {$where_sql}
              GROUP BY o.id
              ORDER BY o.timecreated DESC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Creates a new opening from form data.
     *
     * Job ID is generated AFTER the DB insert using the auto-increment row ID,
     * which eliminates any race condition between simultaneous admin saves.
     *
     * @param stdClass $data Form data object.
     * @param int      $createdby Moodle user ID of the admin creating the opening.
     * @return int The new opening's ID.
     */
    public static function create(\stdClass $data, int $createdby): int {
        global $DB;

        $broadcast_ts = !empty($data->broadcast_date) ? (int)$data->broadcast_date : null;

        $record                      = new \stdClass();
        $record->jobid               = 'FNTR-PENDING-' . uniqid(); // Temporary — updated immediately below.
        $record->title               = $data->title;
        $record->company             = $data->company;
        $record->company_logo_url    = $data->company_logo_url ?? null;
        $record->location            = $data->location;
        $record->employment_type     = $data->employment_type;
        $record->experience_required = $data->experience_required;
        $record->min_ctc             = $data->min_ctc ?? null;
        $record->max_ctc             = $data->max_ctc ?? null;
        $record->stipend             = $data->stipend ?? null;
        $record->cfa_required        = $data->cfa_required ?? null;
        $record->frm_required        = $data->frm_required ?? null;
        $record->qualification_notes = $data->qualification_notes ?? null;
        $record->jd_text             = $data->jd_text ?? null;
        $record->role_overview       = $data->role_overview ?? null;
        $record->broadcast_date      = $broadcast_ts;
        $record->deadline            = (int)$data->deadline;
        $record->status              = $data->status ?? 'draft';
        $record->community_target    = $data->community_target ?? null;
        $record->timecreated         = time();
        $record->createdby           = $createdby;

        // Insert first — get the guaranteed-unique auto-increment ID.
        $new_id = $DB->insert_record('finplacements_openings', $record);

        // Now build the final job ID using the real row ID (no race condition possible).
        $final_jobid = self::build_job_id($new_id, $broadcast_ts);
        $DB->set_field('finplacements_openings', 'jobid', $final_jobid, ['id' => $new_id]);

        return $new_id;
    }

    /**
     * Updates an existing opening.
     *
     * @param int      $id   Opening ID.
     * @param stdClass $data Form data.
     * @return void
     */
    public static function update(int $id, \stdClass $data): void {
        global $DB;

        $existing = self::get_by_id($id);
        if (!$existing) {
            throw new \moodle_exception('error_notfound', 'local_finplacements');
        }

        $broadcast_ts = !empty($data->broadcast_date) ? (int)$data->broadcast_date : $existing->broadcast_date;

        $record                      = new \stdClass();
        $record->id                  = $id;
        $record->title               = $data->title;
        $record->company             = $data->company;
        $record->company_logo_url    = $data->company_logo_url ?? $existing->company_logo_url;
        $record->location            = $data->location;
        $record->employment_type     = $data->employment_type;
        $record->experience_required = $data->experience_required;
        $record->min_ctc             = $data->min_ctc ?? null;
        $record->max_ctc             = $data->max_ctc ?? null;
        $record->stipend             = $data->stipend ?? null;
        $record->cfa_required        = $data->cfa_required ?? null;
        $record->frm_required        = $data->frm_required ?? null;
        $record->qualification_notes = $data->qualification_notes ?? null;
        $record->jd_text             = $data->jd_text ?? null;
        $record->role_overview       = $data->role_overview ?? null;
        $record->broadcast_date      = $broadcast_ts;
        $record->deadline            = (int)$data->deadline;
        $record->status              = $data->status ?? $existing->status;
        $record->community_target    = $data->community_target ?? $existing->community_target;

        $DB->update_record('finplacements_openings', $record);
    }

    /**
     * Closes an opening by setting its status to 'closed'.
     *
     * @param int $id Opening ID.
     * @return void
     */
    public static function close_opening(int $id): void {
        global $DB;
        $DB->set_field('finplacements_openings', 'status', 'closed', ['id' => $id]);
    }

    /**
     * Returns count of applications for a given opening.
     *
     * @param int $openingid Opening ID.
     * @return int
     */
    public static function get_application_count(int $openingid): int {
        global $DB;
        return (int)$DB->count_records('finplacements_applications', ['openingid' => $openingid]);
    }
}
