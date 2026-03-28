<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * PHPUnit tests for the Candidate Manager class.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements\tests;

use local_finplacements\candidate_manager;

/**
 * Test case for candidate profile operations.
 * 
 * @covers \local_finplacements\candidate_manager
 */
class candidate_manager_test extends \advanced_testcase {

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/finplacements/lib.php');
        $this->resetAfterTest(true);
    }

    /**
     * Core functionality test: Creating a candidate profile from scratch.
     */
    public function test_save_new_profile() {
        global $DB;

        // 1. Generate a mock Moodle user using the data generator.
        $user = $this->getDataGenerator()->create_user();
        
        // Ensure no candidate profile exists yet.
        $this->assertFalse(candidate_manager::get_by_userid($user->id));

        // 2. Build mock submission data natively.
        $data = new \stdClass();
        $data->fullname        = 'John Doe';
        $data->email           = 'john@example.com';
        $data->phone           = '9999999999';
        $data->city            = 'Mumbai';
        $data->state           = 'Maharashtra';
        $data->experience_band = 'fresher';
        $data->cfa_level       = 'none';
        $data->resume_url      = 'https://drive.google.com/test';

        // 3. Save the profile.
        $candidate_id = candidate_manager::save_profile($user->id, $data);

        // 4. Verification Assertions.
        $this->assertGreaterThan(0, $candidate_id);

        $record = candidate_manager::get_by_id($candidate_id);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals('Mumbai', $record->city);
        $this->assertEquals('fresher', $record->experience_band);
        
        // Assert auto-score was triggered.
        $this->assertObjectHasProperty('profile_score', $record);
    }

    /**
     * Core functionality test: Updating an existing profile.
     */
    public function test_update_existing_profile() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        
        $data = new \stdClass();
        $data->phone = '1111111111';
        $data->city  = 'Delhi';
        
        // First save.
        $id1 = candidate_manager::save_profile($user->id, $data);

        // Modify and re-save.
        $data->city = 'Pune';
        $data->experience_band = '1-3';
        
        $id2 = candidate_manager::save_profile($user->id, $data);

        // Verify it updated the exact same record, rather than duplicating.
        $this->assertEquals($id1, $id2);

        $record = candidate_manager::get_by_userid($user->id);
        $this->assertEquals('Pune', $record->city);
        $this->assertEquals('1-3', $record->experience_band);
        $this->assertEquals('1111111111', $record->phone);

        // Verify total count in DB didn't increase.
        $count = $DB->count_records('finplacements_candidates', ['userid' => $user->id]);
        $this->assertEquals(1, $count);
    }
}
