<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * PHPUnit tests for the Application Scoring logic.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_finplacements\tests;

use local_finplacements\application_manager;

/**
 * Test case for the automated Candidate matching algorithm.
 * 
 * @covers \local_finplacements\application_manager::calculate_fit_score
 */
class application_scoring_test extends \advanced_testcase {

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/finplacements/lib.php');
        $this->resetAfterTest(true);
    }

    /**
     * Test a perfect 100/100 candidate match.
     */
    public function test_perfect_score_match() {
        $candidate = new \stdClass();
        $candidate->city            = 'Mumbai';
        $candidate->experience_band = '1-3';
        $candidate->cfa_level       = 'level3'; // Exceeds required L2
        $candidate->frm_level       = 'none';   // Matches required none
        $candidate->current_ctc     = '500000'; // Under max ceiling
        $candidate->profile_score   = 100;

        $opening = new \stdClass();
        $opening->location            = 'Mumbai, India';
        $opening->experience_required = '1-3';
        $opening->cfa_required        = 'level2';
        $opening->frm_required        = 'none';
        $opening->employment_type     = 'fulltime';
        $opening->min_ctc             = '600000';

        $score = application_manager::calculate_fit_score($candidate, $opening);

        // Expected Score Breakdown:
        // Location (Mumbai) = 25
        // Experience (1-3) = 25
        // CFA Level 3 > Level 2 = 20
        // FRM none = 10
        // CTC 500k <= (600k * 1.3) = 10
        // Profile Score 100 >= 80 = 10
        // Total = 100
        $this->assertEquals(100, $score);
    }

    /**
     * Test a complete 0 match.
     */
    public function test_zero_score_match() {
        $candidate = new \stdClass();
        $candidate->city            = 'Delhi';
        $candidate->experience_band = 'fresher';
        $candidate->cfa_level       = 'none'; 
        $candidate->frm_level       = 'none'; 
        $candidate->current_ctc     = '1000000'; 
        $candidate->profile_score   = 50;

        $opening = new \stdClass();
        $opening->location            = 'Pune';
        $opening->experience_required = '3-5';
        $opening->cfa_required        = 'level1';
        $opening->frm_required        = 'part1';
        $opening->employment_type     = 'fulltime';
        $opening->min_ctc             = '500000';

        $score = application_manager::calculate_fit_score($candidate, $opening);

        // Expected Score Breakdown:
        // Location mismatch = 0
        // Experience mismatch = 0
        // CFA none < Level 1 = 0
        // FRM none < part 1 = 0
        // CTC 1M > (500k * 1.3 = 650k) = 0
        // Profile Score 50 < 80 = 0
        // Total = 0
        $this->assertEquals(0, $score);
    }

    /**
     * Test case insensitivity and partial string matches in locations.
     */
    public function test_location_partial_match() {
        $candidate = new \stdClass();
        $candidate->city = 'new york';
        
        $opening = new \stdClass();
        $opening->location = 'New York City, NY';
        $opening->experience_required = 'any';
        
        $score = application_manager::calculate_fit_score($candidate, $opening);
        
        // Location matches = 25
        // Experience (any) = 25
        // CFA (default) = 20
        // FRM (default) = 10
        // CTC (default) = 10
        // Profile (default < 80) = 0
        // Total = 90
        $this->assertEquals(90, $score);
    }
}
