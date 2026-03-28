<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Demo Data Generator for FinTree Placements
 *
 * Run this script once to populate your database with:
 * - 40 job openings (active, closed, draft)
 * - 100 candidate profiles
 * - 250+ applications
 * - Reliability scores for all candidates
 *
 * HOW TO USE:
 * 1. Log in as admin
 * 2. Navigate to: [moodle_root]/local/finplacements/generate_demo_data.php
 * 3. Wait for completion message
 * 4. Delete this file after use (security best practice)
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\candidate_manager;
use local_finplacements\opening_manager;
use local_finplacements\application_manager;
use local_finplacements\reliability_manager;

// Only admins can run this.
require_login();
$context = context_system::instance();
require_capability('local/finplacements:admin', $context);

echo $OUTPUT->header();
?>
<div class="fp-admin-page">
  <div class="fp-admin-header">
    <h2>📊 Demo Data Generator</h2>
  </div>
  
  <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3>⚠️ Important Notes</h3>
    <ul>
      <li>This script will create <strong>40 openings, 100 candidates, and 250+ applications</strong></li>
      <li>It will use real Moodle users (students) for candidate profiles</li>
      <li>If you have fewer than 100 users, it will create placeholder candidate records</li>
      <li><strong>Delete this file after running</strong> for security</li>
    </ul>
  </div>

<?php

if (optional_param('run', 0, PARAM_INT)) {
    require_sesskey();
    
    echo '<h3>🚀 Generating Demo Data...</h3>';
    echo '<div style="font-family: monospace; background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto;">';
    
    global $DB, $USER;
    
    $stats = [
        'openings' => 0,
        'candidates' => 0,
        'applications' => 0,
        'reliability' => 0,
    ];
    
    // =====================
    // 1. Generate 40 Openings
    // =====================
    echo "<p><strong>Step 1: Creating 40 job openings...</strong></p>";
    
    $companies = [
        'HDFC Securities', 'ICICI Direct', 'Kotak Securities', 'Axis Capital', 'Motilal Oswal',
        'Zerodha', 'Groww', 'Angel One', '5Paisa', 'Edelweiss',
        'CRISIL', 'Care Ratings', 'ICRA', 'Brickworks Ratings', 'India Ratings',
        'Goldman Sachs', 'Morgan Stanley', 'JP Morgan', 'Barclays', 'Deutsche Bank',
        'KPMG', 'Deloitte', 'EY', 'PwC', 'BDO',
        'BlackRock', 'Vanguard', 'Fidelity', 'T. Rowe Price', 'Invesco',
        'Citigroup', 'Bank of America', 'Wells Fargo', 'Credit Suisse', 'UBS',
        'Bajaj Finance', 'HDFC Bank', 'ICICI Bank', 'Axis Bank', 'Kotak Mahindra Bank',
    ];
    
    $locations = ['Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Hyderabad', 'Pune', 'Ahmedabad', 'Kolkata'];
    
    $job_titles = [
        'Equity Research Analyst', 'Investment Banking Analyst', 'Portfolio Manager',
        'Risk Analyst', 'Credit Analyst', 'Financial Analyst', 'Research Associate',
        'Wealth Manager', 'Relationship Manager', 'Investment Advisor',
        'Quantitative Analyst', 'Fixed Income Analyst', 'Derivatives Analyst',
        'Compliance Officer', 'Fund Accountant', 'Treasury Analyst',
        'Business Analyst', 'Data Analyst', 'Operations Manager', 'Product Manager',
    ];
    
    $employment_types = ['fulltime', 'internship'];
    $experience_bands = ['fresher', '1-3', '3-5', '5-10', '10+', 'any'];
    $cfa_levels = ['none', 'level1', 'level2', 'level3', 'charter'];
    $frm_levels = ['none', 'part1', 'part2', 'charter'];
    
    $jd_templates = [
        "We are looking for a motivated {role} to join our team. The ideal candidate will have strong analytical skills and knowledge of financial markets. Responsibilities include conducting research, building financial models, and preparing investment recommendations. CFA/FRM qualification preferred.",
        "Join our dynamic team as a {role}. You will be responsible for analyzing market trends, evaluating investment opportunities, and supporting senior team members. Strong Excel and financial modeling skills required. Experience with Bloomberg terminal is a plus.",
        "Exciting opportunity for a {role} in our Mumbai office. The role involves conducting industry research, company analysis, and preparing detailed reports. Candidate should have excellent communication skills and attention to detail.",
        "We are hiring a {role} to support our investment team. Key responsibilities include data analysis, market research, and client presentations. The ideal candidate will be pursuing CFA/FRM and have strong quantitative skills.",
        "Looking for a {role} with 2-5 years of experience. The role involves portfolio analysis, risk assessment, and client reporting. Knowledge of Indian equity and debt markets is essential. CFA Level 2 or higher preferred.",
    ];
    
    $role_overviews = [
        "This role offers excellent growth opportunities in the financial services sector.",
        "Join a team of experienced professionals and work on challenging projects.",
        "Great opportunity to learn from industry experts and build your career.",
        "Work with top-tier clients and gain exposure to complex financial transactions.",
        "Be part of a fast-growing organization with a collaborative culture.",
    ];
    
    $today = time();
    
    for ($i = 1; $i <= 40; $i++) {
        $company = $companies[array_rand($companies)];
        $title = $job_titles[array_rand($job_titles)];
        $location = $locations[array_rand($locations)];
        $emp_type = $employment_types[array_rand($employment_types)];
        $exp_band = $experience_bands[array_rand($experience_bands)];
        $cfa_req = $cfa_levels[array_rand($cfa_levels)];
        $frm_req = $frm_levels[array_rand($frm_levels)];
        
        // Generate realistic CTC ranges
        if ($exp_band === 'fresher') {
            $min_ctc = rand(3, 6) * 100000;
            $max_ctc = $min_ctc + rand(1, 3) * 100000;
        } elseif ($exp_band === '1-3') {
            $min_ctc = rand(6, 12) * 100000;
            $max_ctc = $min_ctc + rand(3, 8) * 100000;
        } elseif ($exp_band === '3-5') {
            $min_ctc = rand(12, 20) * 100000;
            $max_ctc = $min_ctc + rand(5, 15) * 100000;
        } else {
            $min_ctc = rand(20, 40) * 100000;
            $max_ctc = $min_ctc + rand(10, 40) * 100000;
        }
        
        $stipend = ($emp_type === 'internship') ? '₹' . rand(10000, 50000) . ' per month' : '';
        
        // Broadcast date: random in last 30 days
        $broadcast = $today - rand(1, 30) * 86400;
        // Deadline: some passed, some soon, some far
        if ($i <= 5) {
            $deadline = $today - rand(1, 10) * 86400; // Passed
        } elseif ($i <= 15) {
            $deadline = $today + rand(1, 3) * 86400; // Soon (1-3 days)
        } else {
            $deadline = $today + rand(5, 30) * 86400; // Future
        }
        
        // Status distribution
        if ($i <= 5) {
            $status = 'closed';
        } elseif ($i <= 10) {
            $status = 'draft';
        } else {
            $status = 'active';
        }
        
        $jd_template = $jd_templates[array_rand($jd_templates)];
        $jd_text = str_replace('{role}', $title, $jd_template);
        $jd_text .= " Skills required: Financial modeling, valuation, Excel, PowerPoint. "
                  . "Knowledge of " . ($cfa_req !== 'none' ? "CFA curriculum, " : "")
                  . ($frm_req !== 'none' ? "FRM curriculum, " : "")
                  . "equity research, and financial statements.";
        
        $data = new stdClass();
        $data->title = $title . ' (' . $i . ')';
        $data->company = $company;
        $data->location = $location;
        $data->employment_type = $emp_type;
        $data->experience_required = $exp_band;
        $data->min_ctc = '₹' . number_format($min_ctc) . ' LPA';
        $data->max_ctc = '₹' . number_format($max_ctc) . ' LPA';
        $data->stipend = $stipend;
        $data->cfa_required = $cfa_req;
        $data->frm_required = $frm_req;
        $data->qualification_notes = '';
        $data->jd_text = $jd_text;
        $data->role_overview = $role_overviews[array_rand($role_overviews)];
        $data->broadcast_date = $broadcast;
        $data->deadline = $deadline;
        $data->status = $status;
        $data->community_target = 'WhatsApp Group ' . rand(1, 5);
        
        // Add logo URL for some companies (using placeholder service)
        if ($i % 5 === 0) {
            $data->company_logo_url = 'https://via.placeholder.com/200x200.png?text=' . urlencode(substr($company, 0, 3));
        }
        
        try {
            $new_id = opening_manager::create($data, $USER->id);
            $stats['openings']++;
            echo "<div>✓ Created opening #$new_id: {$data->title} at {$data->company}</div>";
        } catch (Exception $e) {
            echo "<div style='color: red;'>✗ Error creating opening: {$e->getMessage()}</div>";
        }
    }
    
    echo "<p><strong>✓ Created {$stats['openings']} openings</strong></p>";
    
    // =====================
    // 2. Generate 100 Candidates
    // =====================
    echo "<p><strong>Step 2: Creating 100 candidate profiles...</strong></p>";
    
    // Get existing Moodle users
    $users = $DB->get_records('user', ['deleted' => 0], 'timecreated DESC', 'id,firstname,lastname,email', 0, 150);
    $users = array_values($users); // Re-index
    
    $first_names = ['Rajesh', 'Priya', 'Amit', 'Sneha', 'Rahul', 'Neha', 'Vikram', 'Pooja', 'Arjun', 'Anjali',
                    'Suresh', 'Kavita', 'Manish', 'Ritu', 'Sanjay', 'Deepa', 'Karan', 'Meera', 'Rohan', 'Isha'];
    $last_names = ['Sharma', 'Verma', 'Gupta', 'Singh', 'Patel', 'Kumar', 'Joshi', 'Reddy', 'Nair', 'Desai',
                   'Mehta', 'Kapoor', 'Malhotra', 'Bhatt', 'Iyer', 'Menon', 'Chopra', 'Bansal', 'Aggarwal', 'Jain'];
    
    $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Hyderabad', 'Pune', 'Ahmedabad', 'Kolkata', 'Jaipur', 'Lucknow'];
    $states = ['Maharashtra', 'Delhi', 'Karnataka', 'Tamil Nadu', 'Telangana', 'Maharashtra', 'Gujarat', 'West Bengal', 'Rajasthan', 'Uttar Pradesh'];
    $ug_fields = ['Commerce', 'Finance', 'Economics', 'Business Administration', 'Accounting', 'Mathematics', 'Statistics', 'Engineering'];
    $employers = ['TCS', 'Infosys', 'Wipro', 'HCL', 'Tech Mahindra', 'Accenture', 'Deloitte', 'KPMG', 'EY', 'PwC',
                  'HDFC Bank', 'ICICI Bank', 'SBI', 'Axis Bank', 'Reliance', 'Tata Motors', 'Maruti', 'Bajaj Auto'];
    
    for ($i = 1; $i <= 100; $i++) {
        // Use existing user if available, otherwise create placeholder
        if ($i <= count($users)) {
            $user = $users[$i - 1];
            $fullname = $user->firstname . ' ' . $user->lastname;
            $email = $user->email;
            $moodle_userid = $user->id;
        } else {
            $fullname = $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
            $email = 'demo' . $i . '@fintree.edu';
            $moodle_userid = $USER->id; // Fallback to admin user
        }
        
        $phone = '+91 ' . rand(70, 99) . rand(10000000, 99999999);
        $city_idx = array_rand($cities);
        $city = $cities[$city_idx];
        $state = $states[$city_idx];
        $exp_band = $experience_bands[array_rand($experience_bands)];
        $employer = $employers[array_rand($employers)];
        $ctc = rand(3, 25) * 100000;
        $designation = ['Analyst', 'Associate', 'Senior Analyst', 'Executive', 'Manager'][array_rand(5)];
        
        $tenth_pct = rand(60, 95) . '%';
        $twelfth_pct = rand(60, 95) . '%';
        $ug_status = ['completed', 'pursuing'][array_rand(2)];
        $ug_field = $ug_fields[array_rand($ug_fields)];
        $ug_gpa = rand(60, 95) / 10;
        
        $cfa_level = $cfa_levels[array_rand($cfa_levels)];
        $frm_level = $frm_levels[array_rand($frm_levels)];
        $other_certs = ['NISM', 'NSE', 'BSE', 'Financial Modeling'][array_rand(4)];
        $fm_cert = rand(0, 1);
        
        $resume_url = 'https://drive.google.com/file/d/' . uniqid() . '/view';
        
        $data = new stdClass();
        $data->userid = $moodle_userid;
        $data->fullname = $fullname;
        $data->phone = $phone;
        $data->email = $email;
        $data->city = $city;
        $data->state = $state;
        $data->experience_band = $exp_band;
        $data->current_employer = $employer;
        $data->current_ctc = '₹' . number_format($ctc) . ' LPA';
        $data->designation = $designation;
        $data->tenth_pct = $tenth_pct;
        $data->twelfth_pct = $twelfth_pct;
        $data->ug_status = $ug_status;
        $data->ug_field = $ug_field;
        $data->ug_gpa = number_format($ug_gpa, 1);
        $data->pg_status = 'none';
        $data->pg_field = '';
        $data->pg_gpa = '';
        $data->cfa_level = $cfa_level;
        $data->frm_level = $frm_level;
        $data->other_certs = $other_certs;
        $data->fm_cert = $fm_cert;
        $data->courses_done = json_encode(['CFA L1', 'Financial Modeling']);
        $data->resume_url = $resume_url;
        $data->photo_url = '';
        
        try {
            // Check if candidate already exists for this user
            $existing = candidate_manager::get_by_userid($moodle_userid);
            if (!$existing) {
                candidate_manager::save_profile($moodle_userid, $data);
                $stats['candidates']++;
                echo "<div>✓ Created candidate: {$fullname} ({$city})</div>";
            } else {
                echo "<div>⊘ Skipped (already exists): {$fullname}</div>";
            }
        } catch (Exception $e) {
            echo "<div style='color: red;'>✗ Error creating candidate: {$e->getMessage()}</div>";
        }
    }
    
    echo "<p><strong>✓ Created {$stats['candidates']} candidates</strong></p>";
    
    // =====================
    // 3. Generate 250+ Applications
    // =====================
    echo "<p><strong>Step 3: Creating 250+ applications...</strong></p>";
    
    $candidates = $DB->get_records('finplacements_candidates', [], 'id ASC');
    $openings = $DB->get_records('finplacements_openings', ['status' => 'active'], 'id ASC');
    
    $candidates = array_values($candidates);
    $openings = array_values($openings);
    
    $statuses = ['applied', 'screened', 'shortlisted', 'forwarded', 'placed', 'rejected'];
    $status_weights = [50, 25, 10, 5, 3, 7]; // Weighted distribution
    
    $application_count = 0;
    
    // Each candidate applies to 2-5 random openings
    foreach ($candidates as $candidate) {
        $num_applications = rand(2, 5);
        $applied_opening_ids = [];
        
        for ($j = 0; $j < $num_applications; $j++) {
            if (empty($openings)) break;
            
            $opening = $openings[array_rand($openings)];
            
            // Avoid duplicate applications
            if (in_array($opening->id, $applied_opening_ids)) continue;
            $applied_opening_ids[] = $opening->id;
            
            // Check if application already exists
            $existing_app = $DB->get_record('finplacements_applications', [
                'candidateid' => $candidate->id,
                'openingid' => $opening->id
            ]);
            
            if ($existing_app) continue;
            
            // Calculate fit score
            $fit_score = rand(35, 95);
            
            // Weighted random status
            $status = $statuses[array_rand($statuses, 1, $status_weights)];
            
            $applied_date = $today - rand(1, 25) * 86400;
            
            $app_data = new stdClass();
            $app_data->candidateid = $candidate->id;
            $app_data->openingid = $opening->id;
            $app_data->applied_date = $applied_date;
            $app_data->status = $status;
            $app_data->auto_score = $fit_score;
            $app_data->shortlisted_by = ($status !== 'applied') ? $USER->id : null;
            $app_data->shortlisted_date = ($status !== 'applied') ? $today - rand(1, 10) * 86400 : null;
            $app_data->wa_sent = rand(0, 1);
            $app_data->email_sent = 0;
            $app_data->admin_notes = '';
            
            try {
                $appid = $DB->insert_record('finplacements_applications', $app_data);
                $stats['applications']++;
                $application_count++;
                
                if ($application_count <= 20) {
                    echo "<div>✓ Created application: {$candidate->fullname} → {$opening->title}</div>";
                }
            } catch (Exception $e) {
                echo "<div style='color: red;'>✗ Error creating application: {$e->getMessage()}</div>";
            }
        }
    }
    
    if ($application_count > 20) {
        echo "<div>... and " . ($application_count - 20) . " more applications</div>";
    }
    
    echo "<p><strong>✓ Created {$stats['applications']} applications</strong></p>";
    
    // =====================
    // 4. Generate Reliability Scores
    // =====================
    echo "<p><strong>Step 4: Creating reliability scores...</strong></p>";
    
    foreach ($candidates as $candidate) {
        $existing = $DB->get_record('finplacements_reliability', ['candidateid' => $candidate->id]);
        
        if ($existing) continue;
        
        $rel_data = new stdClass();
        $rel_data->candidateid = $candidate->id;
        $rel_data->score = rand(50, 95); // Random score between 50-95
        $rel_data->times_applied = rand(1, 10);
        $rel_data->times_shortlisted = rand(0, 5);
        $rel_data->times_forwarded = rand(0, 3);
        $rel_data->times_placed = rand(0, 2);
        $rel_data->offers_backed_out = rand(0, 1);
        $rel_data->no_shows = rand(0, 2);
        $rel_data->last_updated = $today;
        
        try {
            $DB->insert_record('finplacements_reliability', $rel_data);
            $stats['reliability']++;
        } catch (Exception $e) {
            echo "<div style='color: red;'>✗ Error creating reliability record: {$e->getMessage()}</div>";
        }
    }
    
    echo "<p><strong>✓ Created {$stats['reliability']} reliability records</strong></p>";
    
    echo '</div>';
    
    // Summary
    echo '<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0;">';
    echo '<h3>✅ Demo Data Generation Complete!</h3>';
    echo '<ul>';
    echo '<li><strong>' . $stats['openings'] . '</strong> job openings created</li>';
    echo '<li><strong>' . $stats['candidates'] . '</strong> candidate profiles created</li>';
    echo '<li><strong>' . $stats['applications'] . '</strong> applications created</li>';
    echo '<li><strong>' . $stats['reliability'] . '</strong> reliability records created</li>';
    echo '</ul>';
    echo '<p><a href="' . (new moodle_url('/local/finplacements/jobs.php'))->out() . '" class="fp-btn fp-btn-primary">View Job Board →</a></p>';
    echo '<p style="color: #c62828; font-weight: bold;">⚠️ Remember to delete this file (generate_demo_data.php) after use!</p>';
    echo '</div>';
    
} else {
    // Show start button
    echo '<div style="text-align: center; padding: 40px;">';
    echo '<h3>Ready to Generate Demo Data?</h3>';
    echo '<p>This will create:</p>';
    echo '<ul style="display: inline-block; text-align: left;">';
    echo '<li>✅ 40 job openings (active, closed, draft)</li>';
    echo '<li>✅ 100 candidate profiles</li>';
    echo '<li>✅ 250+ applications</li>';
    echo '<li>✅ Reliability scores for all candidates</li>';
    echo '</ul>';
    echo '<br><br>';
    echo '<form method="post">';
    echo '<input type="hidden" name="run" value="1">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<button type="submit" class="fp-btn fp-btn-primary" onclick="return confirm(\'This will create demo data. Continue?\')">🚀 Generate Demo Data</button>';
    echo '</form>';
    echo '</div>';
}

?>
</div>
<?php

echo $OUTPUT->footer();
