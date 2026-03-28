<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade script for local_finplacements.
 *
 * Each schema change after v1.0.0 must go in a versioned if-block here.
 * This file must exist before any upgrade is attempted.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin DB schema.
 *
 * @param int $oldversion Previous installed version number.
 * @return bool True on success.
 */
function xmldb_local_finplacements_upgrade(int $oldversion): bool {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026032800) {

        // Add indexes to finplacements_candidates
        $table = new xmldb_table('finplacements_candidates');

        $index = new xmldb_index('city', XMLDB_INDEX_NOTUNIQUE, ['city']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('experience_band', XMLDB_INDEX_NOTUNIQUE, ['experience_band']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('cfa_level', XMLDB_INDEX_NOTUNIQUE, ['cfa_level']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add indexes to finplacements_applications
        $table = new xmldb_table('finplacements_applications');

        $index = new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Savepoint
        upgrade_plugin_savepoint(true, 2026032800, 'local', 'finplacements');
    }

    // Version 2026033001: Add company_logo_url field to openings table
    if ($oldversion < 2026033001) {
        $table = new xmldb_table('finplacements_openings');
        $field = new xmldb_field('company_logo_url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'company');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026033001, 'local', 'finplacements');
    }

    return true;
}
