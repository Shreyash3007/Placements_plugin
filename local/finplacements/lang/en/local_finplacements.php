<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Language strings for local_finplacements.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name.
$string['pluginname']            = 'FinTree Placements';

// Capabilities.
$string['finplacements:candidate'] = 'Use FinTree Placements as a candidate';
$string['finplacements:admin']     = 'Manage FinTree Placements openings and applications';

// Navigation.
$string['myprofile']             = 'My Profile';
$string['jobboard']              = 'Job Board';
$string['myapplications']       = 'My Applications';
$string['manageopenings']       = 'Manage Openings';

// Profile page.
$string['profile_heading']       = 'My Placement Profile';
$string['profile_save']          = 'Save Profile';
$string['profile_saved']         = 'Your profile has been saved successfully.';
$string['profile_complete']      = 'Profile Complete';
$string['profile_incomplete']    = 'Profile Incomplete';
$string['profile_completion']    = 'Profile Completion';
$string['profile_score']         = 'Profile Completeness';

// Profile sections.
$string['section_personal']      = 'Personal Information';
$string['section_academic']      = 'Academic Background';
$string['section_certifications'] = 'Professional Certifications';
$string['section_work']          = 'Work Experience';
$string['section_documents']     = 'Documents';

// Profile fields.
$string['fullname']              = 'Full Name';
$string['phone']                 = 'Phone Number';
$string['email']                 = 'Email Address';
$string['city']                  = 'City';
$string['state']                 = 'State';
$string['experience_band']       = 'Years of Experience';
$string['current_employer']      = 'Current Employer';
$string['current_ctc']           = 'Current CTC (per annum)';
$string['designation']           = 'Designation / Job Title';
$string['tenth_pct']             = '10th Percentage';
$string['twelfth_pct']           = '12th Percentage';
$string['ug_status']             = 'Undergraduate Degree Status';
$string['ug_field']              = 'UG Field / Specialisation';
$string['ug_gpa']                = 'UG GPA / Percentage';
$string['pg_status']             = 'Postgraduate Degree Status';
$string['pg_field']              = 'PG Field / Specialisation';
$string['pg_gpa']                = 'PG GPA / Percentage';
$string['cfa_level']             = 'CFA Level Cleared';
$string['frm_level']             = 'FRM Level Cleared';
$string['other_certs']           = 'Other Certifications';
$string['fm_cert']               = 'FinTree FM Certification';
$string['courses_done']          = 'FinTree Courses Completed';
$string['resume_url']            = 'Resume URL (Google Drive Link)';
$string['photo_url']             = 'Passport Photo URL';

// Experience band options.
$string['exp_fresher']           = 'Fresher (0 years)';
$string['exp_1_3']               = '1 – 3 years';
$string['exp_3_5']               = '3 – 5 years';
$string['exp_5_10']              = '5 – 10 years';
$string['exp_10plus']            = '10+ years';
$string['exp_any']               = 'Any';

// CFA level options.
$string['cfa_none']              = 'None';
$string['cfa_level1']            = 'CFA Level 1 Cleared';
$string['cfa_level2']            = 'CFA Level 2 Cleared';
$string['cfa_level3']            = 'CFA Level 3 Cleared';
$string['cfa_charter']           = 'CFA Charterholder';

// FRM level options.
$string['frm_none']              = 'None';
$string['frm_part1']             = 'FRM Part 1 Cleared';
$string['frm_part2']             = 'FRM Part 2 Cleared';
$string['frm_charter']           = 'FRM Certified';

// UG/PG status options.
$string['status_completed']      = 'Completed';
$string['status_pursuing']       = 'Pursuing';

// Job board page.
$string['jobboard_heading']      = 'Available Openings';
$string['no_openings']           = 'No openings available right now. Check back soon.';
$string['profile_incomplete_banner'] = 'Complete your profile to apply — {$a}% done.';
$string['complete_profile_link'] = 'Complete your profile';

// Job card fields.
$string['job_id']                = 'Job ID';
$string['company']               = 'Company';
$string['location']              = 'Location';
$string['employment_type']       = 'Employment Type';
$string['experience_required']   = 'Experience Required';
$string['deadline']              = 'Deadline';
$string['apply_now']             = 'Apply Now';
$string['already_applied']       = 'Applied';
$string['deadline_passed']       = 'Deadline Passed';
$string['deadline_soon']         = 'Deadline in {$a} hrs';
$string['fulltime']              = 'Full-time';
$string['internship']            = 'Internship';

// Apply page.
$string['apply_success']         = 'Your application has been submitted successfully!';
$string['apply_duplicate']       = 'You have already applied for this opening.';
$string['apply_deadline_passed'] = 'The application deadline for this opening has passed.';
$string['apply_profile_needed']  = 'Please complete at least 50% of your profile before applying.';
$string['apply_no_profile']      = 'Please fill in your profile before applying.';
$string['opening_not_found']     = 'Opening not found.';

// Tracker page.
$string['tracker_heading']       = 'My Applications';
$string['no_applications']       = 'You haven\'t applied to any openings yet.';
$string['applied_on']            = 'Applied On';
$string['status']                = 'Status';

// Application statuses.
$string['status_applied']        = 'Applied';
$string['status_screened']       = 'Screened';
$string['status_shortlisted']    = 'Shortlisted';
$string['status_forwarded']      = 'Forwarded';
$string['status_placed']         = 'Placed';
$string['status_rejected']       = 'Rejected';

// Admin — openings list.
$string['admin_openings_heading'] = 'Manage Openings';
$string['create_opening']         = 'Create New Opening';
$string['filter_by_status']       = 'Filter by Status';
$string['all_statuses']           = 'All Statuses';
$string['status_draft']           = 'Draft';
$string['status_active']          = 'Active';
$string['status_closed']          = 'Closed';
$string['applications_count']     = 'Applications';
$string['actions']                = 'Actions';
$string['edit']                   = 'Edit';
$string['view_applications']      = 'View Applications';
$string['close_opening']          = 'Close Opening';
$string['opening_closed']         = 'Opening has been closed.';

// Admin — opening edit form.
$string['edit_opening_heading']   = 'Create / Edit Opening';
$string['opening_title']          = 'Job Title';
$string['opening_company']        = 'Company Name';
$string['opening_location']       = 'Location';
$string['min_ctc']                = 'Minimum CTC (LPA)';
$string['max_ctc']                = 'Maximum CTC (LPA)';
$string['stipend']                = 'Stipend (per month)';
$string['cfa_required']           = 'CFA Level Required';
$string['frm_required']           = 'FRM Level Required';
$string['qualification_notes']    = 'Qualification Notes';
$string['broadcast_date']         = 'Broadcast Date';
$string['jd_text']                = 'Full Job Description';
$string['role_overview']          = 'Role Overview';
$string['opening_status']         = 'Opening Status';
$string['save_opening']           = 'Save Opening';
$string['opening_saved']          = 'Opening saved. Job ID: {$a}';
$string['generated_job_id']       = 'Generated Job ID';
$string['community_target']       = 'WhatsApp Community Target';

// Admin — applications list.
$string['applications_heading']   = 'Applications';
$string['filter_city']            = 'Filter by City';
$string['filter_exp']             = 'Filter by Experience';
$string['filter_min_score']       = 'Minimum Auto-Score';
$string['auto_score']             = 'Auto Score';
$string['reliability_score']      = 'Reliability';
$string['resume']                 = 'Resume';
$string['view_cv']                = 'View CV';
$string['select']                 = 'Select';
$string['shortlist_selected']     = 'Shortlist Selected';
$string['reject_selected']        = 'Mark as Rejected';
$string['no_candidates']          = 'No candidates have applied for this opening yet.';
$string['cfa_level_label']        = 'CFA';
$string['frm_level_label']        = 'FRM';

// Admin — shortlist confirmation.
$string['shortlist_heading']      = 'Confirm Shortlist';
$string['shortlist_confirm_btn']  = 'Confirm Shortlist';
$string['wa_preview']             = 'WhatsApp Message Preview';
$string['sheets_preview']         = 'Google Sheets Row Preview';
$string['shortlist_confirmed']    = '{$a} candidate(s) have been shortlisted. WhatsApp messages and Sheets sync logged.';
$string['no_candidates_selected'] = 'No candidates selected.';

// Errors.
$string['error_nopermission']    = 'You do not have permission to access this page.';
$string['error_notfound']        = 'The requested record was not found.';
$string['error_sesskey']         = 'Session key mismatch. Please try again.';
