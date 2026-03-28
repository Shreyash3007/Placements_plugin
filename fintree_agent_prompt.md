# AGENT IMPLEMENTATION PROMPT
## FinTree Placement Plugin — local_finplacements

---

## YOUR MISSION

You are building a complete, production-ready Moodle local plugin called `local_finplacements` for FinTree Education. This plugin replaces their entire manual placement workflow. You will build it phase by phase, completing each phase fully before moving to the next. At the end of all phases, the result must be a fully working plugin that can be zipped and installed on any Moodle 4.x instance.

Do not ask for clarification. All decisions are made. Build everything described below completely and correctly.

---

## CONTEXT — WHAT THIS PLUGIN DOES

FinTree Education is a CFA/FRM exam preparation company in India. They currently manage job placements for their students entirely manually — creating new Google Forms for every job opening, saving phone numbers one by one, manually screening 50-150 applicants by reading spreadsheet rows, and sending individual WhatsApp messages to shortlisted candidates.

This plugin eliminates all of that. It lives inside Moodle LMS (where students already study) and provides:
- A one-time candidate profile (filled once, never again)
- A live job board (admin posts openings, candidates see and apply in one click)
- An admin shortlisting panel (colour-coded auto-scores, resume links, bulk shortlist)
- A candidate tracker (students see their application status per opening)
- A reliability score system (replaces the manual blacklist)
- Mocked WhatsApp and Google Sheets integrations (ready to go live with one config change)

---

## TECHNICAL REQUIREMENTS

- **Moodle version**: 4.1 and above
- **PHP**: 8.1+
- **Database**: MySQL (via Moodle's $DB API — never write raw SQL queries, always use Moodle DB API)
- **No external libraries**: Use only Moodle core APIs and vanilla JS
- **No framework**: No React, no Vue, no Tailwind — standard Moodle HTML output
- **Security**: Always use `require_login()`, `require_capability()`, `sesskey()` for forms, and `$DB->get_record()` not raw queries
- **Moodle coding style**: Follow Moodle coding guidelines — snake_case functions, proper use of `$OUTPUT->header()`, `$OUTPUT->footer()`, `$PAGE->set_*`

---

## COMPLETE FILE STRUCTURE

Build every file listed below. Do not skip any file.

```
local/finplacements/
├── version.php
├── lib.php
├── styles.css
├── db/
│   ├── access.php
│   └── install.xml
├── lang/
│   └── en/
│       └── local_finplacements.php
├── classes/
│   ├── candidate_manager.php
│   ├── opening_manager.php
│   ├── application_manager.php
│   ├── reliability_manager.php
│   ├── sheets_sync.php
│   └── whatsapp_mock.php
├── profile.php
├── jobs.php
├── apply.php
├── tracker.php
└── admin/
    ├── openings.php
    ├── opening_edit.php
    ├── applications.php
    └── shortlist.php
```

---

## DATABASE SCHEMA — BUILD EXACTLY AS SPECIFIED

### Table: finplacements_candidates

```xml
<TABLE NAME="finplacements_candidates">
  <FIELDS>
    <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
    <FIELD NAME="userid" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="fullname" TYPE="char" LENGTH="255" NOTNULL="true"/>
    <FIELD NAME="phone" TYPE="char" LENGTH="20" NOTNULL="true"/>
    <FIELD NAME="email" TYPE="char" LENGTH="255" NOTNULL="true"/>
    <FIELD NAME="city" TYPE="char" LENGTH="100" NOTNULL="true"/>
    <FIELD NAME="state" TYPE="char" LENGTH="100" NOTNULL="true"/>
    <FIELD NAME="experience_band" TYPE="char" LENGTH="20" NOTNULL="true"/>
    <!-- Values: fresher, 1-3, 3-5, 5-10, 10+ -->
    <FIELD NAME="current_employer" TYPE="char" LENGTH="255" NOTNULL="false"/>
    <FIELD NAME="current_ctc" TYPE="char" LENGTH="50" NOTNULL="false"/>
    <FIELD NAME="designation" TYPE="char" LENGTH="255" NOTNULL="false"/>
    <FIELD NAME="tenth_pct" TYPE="char" LENGTH="10" NOTNULL="false"/>
    <FIELD NAME="twelfth_pct" TYPE="char" LENGTH="10" NOTNULL="false"/>
    <FIELD NAME="ug_status" TYPE="char" LENGTH="20" NOTNULL="false"/>
    <!-- Values: completed, pursuing -->
    <FIELD NAME="ug_field" TYPE="char" LENGTH="255" NOTNULL="false"/>
    <FIELD NAME="ug_gpa" TYPE="char" LENGTH="20" NOTNULL="false"/>
    <FIELD NAME="pg_status" TYPE="char" LENGTH="20" NOTNULL="false"/>
    <FIELD NAME="pg_field" TYPE="char" LENGTH="255" NOTNULL="false"/>
    <FIELD NAME="pg_gpa" TYPE="char" LENGTH="20" NOTNULL="false"/>
    <FIELD NAME="cfa_level" TYPE="char" LENGTH="30" NOTNULL="false"/>
    <!-- Values: none, level1, level2, level3, charter -->
    <FIELD NAME="frm_level" TYPE="char" LENGTH="30" NOTNULL="false"/>
    <!-- Values: none, part1, part2, charter -->
    <FIELD NAME="other_certs" TYPE="char" LENGTH="255" NOTNULL="false"/>
    <FIELD NAME="fm_cert" TYPE="int" LENGTH="1" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="courses_done" TYPE="text" NOTNULL="false"/>
    <!-- JSON array: ["CFA L1","CFA L2","FRM P1","FM"] -->
    <FIELD NAME="resume_url" TYPE="text" NOTNULL="false"/>
    <FIELD NAME="photo_url" TYPE="text" NOTNULL="false"/>
    <FIELD NAME="profile_complete" TYPE="int" LENGTH="1" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="profile_score" TYPE="int" LENGTH="3" NOTNULL="false" DEFAULT="0"/>
    <!-- 0-100 completeness score -->
    <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="timemodified" TYPE="int" LENGTH="10" NOTNULL="true"/>
  </FIELDS>
  <KEYS>
    <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
    <KEY NAME="userid" TYPE="unique" FIELDS="userid"/>
  </KEYS>
</TABLE>
```

### Table: finplacements_openings

```xml
<TABLE NAME="finplacements_openings">
  <FIELDS>
    <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
    <FIELD NAME="jobid" TYPE="char" LENGTH="30" NOTNULL="true"/>
    <!-- Format: FNTR-YYMMDD-A304 -->
    <FIELD NAME="title" TYPE="char" LENGTH="255" NOTNULL="true"/>
    <FIELD NAME="company" TYPE="char" LENGTH="255" NOTNULL="true"/>
    <FIELD NAME="location" TYPE="char" LENGTH="255" NOTNULL="true"/>
    <FIELD NAME="employment_type" TYPE="char" LENGTH="20" NOTNULL="true"/>
    <!-- Values: fulltime, internship -->
    <FIELD NAME="experience_required" TYPE="char" LENGTH="20" NOTNULL="true"/>
    <!-- Values: fresher, 1-3, 3-5, 5-10, 10+, any -->
    <FIELD NAME="min_ctc" TYPE="char" LENGTH="50" NOTNULL="false"/>
    <FIELD NAME="max_ctc" TYPE="char" LENGTH="50" NOTNULL="false"/>
    <FIELD NAME="stipend" TYPE="char" LENGTH="50" NOTNULL="false"/>
    <!-- For internships only -->
    <FIELD NAME="cfa_required" TYPE="char" LENGTH="30" NOTNULL="false"/>
    <FIELD NAME="frm_required" TYPE="char" LENGTH="30" NOTNULL="false"/>
    <FIELD NAME="qualification_notes" TYPE="text" NOTNULL="false"/>
    <FIELD NAME="jd_text" TYPE="text" NOTNULL="false"/>
    <!-- Full JD pasted in -->
    <FIELD NAME="role_overview" TYPE="text" NOTNULL="false"/>
    <FIELD NAME="broadcast_date" TYPE="int" LENGTH="10" NOTNULL="false"/>
    <FIELD NAME="deadline" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="status" TYPE="char" LENGTH="20" NOTNULL="true" DEFAULT="draft"/>
    <!-- Values: draft, active, closed -->
    <FIELD NAME="community_target" TYPE="char" LENGTH="20" NOTNULL="false"/>
    <!-- Which WA community to broadcast to -->
    <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="createdby" TYPE="int" LENGTH="10" NOTNULL="true"/>
  </FIELDS>
  <KEYS>
    <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
    <KEY NAME="jobid" TYPE="unique" FIELDS="jobid"/>
  </KEYS>
</TABLE>
```

### Table: finplacements_applications

```xml
<TABLE NAME="finplacements_applications">
  <FIELDS>
    <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
    <FIELD NAME="candidateid" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <!-- FK to finplacements_candidates.id -->
    <FIELD NAME="openingid" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <!-- FK to finplacements_openings.id -->
    <FIELD NAME="applied_date" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="status" TYPE="char" LENGTH="20" NOTNULL="true" DEFAULT="applied"/>
    <!-- Values: applied, screened, shortlisted, forwarded, rejected, placed -->
    <FIELD NAME="auto_score" TYPE="int" LENGTH="3" NOTNULL="false" DEFAULT="0"/>
    <!-- 0-100 fit score calculated on apply -->
    <FIELD NAME="shortlisted_by" TYPE="int" LENGTH="10" NOTNULL="false"/>
    <FIELD NAME="shortlisted_date" TYPE="int" LENGTH="10" NOTNULL="false"/>
    <FIELD NAME="wa_sent" TYPE="int" LENGTH="1" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="email_sent" TYPE="int" LENGTH="1" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="admin_notes" TYPE="text" NOTNULL="false"/>
  </FIELDS>
  <KEYS>
    <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
  </KEYS>
  <INDEXES>
    <INDEX NAME="candidateid" UNIQUE="false" FIELDS="candidateid"/>
    <INDEX NAME="openingid" UNIQUE="false" FIELDS="openingid"/>
  </INDEXES>
</TABLE>
```

### Table: finplacements_reliability

```xml
<TABLE NAME="finplacements_reliability">
  <FIELDS>
    <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
    <FIELD NAME="candidateid" TYPE="int" LENGTH="10" NOTNULL="true"/>
    <FIELD NAME="score" TYPE="int" LENGTH="3" NOTNULL="true" DEFAULT="70"/>
    <!-- Starts at 70. Max 100. Min 0. -->
    <FIELD NAME="times_applied" TYPE="int" LENGTH="5" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="times_shortlisted" TYPE="int" LENGTH="5" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="times_forwarded" TYPE="int" LENGTH="5" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="times_placed" TYPE="int" LENGTH="5" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="offers_backed_out" TYPE="int" LENGTH="5" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="no_shows" TYPE="int" LENGTH="5" NOTNULL="false" DEFAULT="0"/>
    <FIELD NAME="last_updated" TYPE="int" LENGTH="10" NOTNULL="true"/>
  </FIELDS>
  <KEYS>
    <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
    <KEY NAME="candidateid" TYPE="unique" FIELDS="candidateid"/>
  </KEYS>
</TABLE>
```

---

## AUTO-SCORING ALGORITHM

Implement this exactly in `application_manager.php`:

```php
function calculate_fit_score($candidate, $opening) {
    $score = 0;

    // Location match (+25)
    if (!empty($opening->location) && !empty($candidate->city)) {
        if (stripos($opening->location, $candidate->city) !== false ||
            stripos($candidate->city, $opening->location) !== false) {
            $score += 25;
        }
    }

    // Experience band match (+25)
    if ($opening->experience_required === 'any' ||
        $opening->experience_required === $candidate->experience_band) {
        $score += 25;
    }

    // CFA level match (+20)
    if (!empty($opening->cfa_required) && $opening->cfa_required !== 'none') {
        $cfa_levels = ['none'=>0,'level1'=>1,'level2'=>2,'level3'=>3,'charter'=>4];
        $required = $cfa_levels[$opening->cfa_required] ?? 0;
        $has = $cfa_levels[$candidate->cfa_level] ?? 0;
        if ($has >= $required) {
            $score += 20;
        }
    } else {
        $score += 20; // No CFA required = full points
    }

    // FRM level match (+10)
    if (!empty($opening->frm_required) && $opening->frm_required !== 'none') {
        $frm_levels = ['none'=>0,'part1'=>1,'part2'=>2,'charter'=>3];
        $required = $frm_levels[$opening->frm_required] ?? 0;
        $has = $frm_levels[$candidate->frm_level] ?? 0;
        if ($has >= $required) {
            $score += 10;
        }
    } else {
        $score += 10;
    }

    // CTC match (+10) — skip for internships
    if ($opening->employment_type === 'fulltime' &&
        !empty($opening->min_ctc) && !empty($candidate->current_ctc)) {
        // Basic check: parse numeric value
        $candidate_ctc = (int) preg_replace('/[^0-9]/', '', $candidate->current_ctc);
        $min_ctc = (int) preg_replace('/[^0-9]/', '', $opening->min_ctc);
        if ($candidate_ctc <= $min_ctc * 1.3) { // within 30% above min
            $score += 10;
        }
    } else {
        $score += 10;
    }

    // Profile completeness (+10)
    if ($candidate->profile_score >= 80) {
        $score += 10;
    }

    return min(100, $score);
}
```

---

## JOB ID GENERATION

Implement in `opening_manager.php`:

```php
function generate_job_id($broadcast_date_timestamp = null) {
    global $DB;

    // Date part: YYMMDD of broadcast date (or today if not set)
    $ts = $broadcast_date_timestamp ?? time();
    $date_part = date('ymd', $ts);

    // Sequential number: start from 304 (existing openings before system)
    $max = $DB->get_field_sql('SELECT MAX(id) FROM {finplacements_openings}');
    $seq = ($max ? $max + 303 : 303) + 1;
    // +303 offset so first opening = A304

    return 'FNTR-' . $date_part . '-A' . $seq;
}
```

---

## WHATSAPP MOCK CLASS

Build `classes/whatsapp_mock.php` to generate and log the exact message FinTree uses:

```php
function generate_prescreen_message($candidate_name, $company, $role, $location) {
    return "*Subject: Application Update – {$role}*\n\n"
         . "Hello {$candidate_name},\n\n"
         . "You have been successfully pre-screened by FinTree, and your resume "
         . "has been forwarded to the hiring company for further consideration.\n\n"
         . "Company Name: *{$company}*\n"
         . "Role: *{$role}*\n"
         . "Location: *{$location}*\n\n"
         . "The company will contact you directly regarding the next steps "
         . "in the selection process.\n\n"
         . "Please ensure that you are available to respond to their communication.\n\n"
         . "Best regards,\n"
         . "*FinTree Career Services Team*";
}

function send_or_mock($phone, $message) {
    global $DB;
    // Check if real API config exists
    $api_url = get_config('local_finplacements', 'whatsapp_api_url');
    if (!empty($api_url)) {
        // REAL: fire actual API call here
        // return $this->send_real($phone, $message);
    }
    // MOCK: log the message
    $record = new stdClass();
    $record->phone = $phone;
    $record->message = $message;
    $record->timecreated = time();
    $record->status = 'mock_logged';
    $DB->insert_record('finplacements_wa_log', $record);
    return ['status' => 'mocked', 'message' => $message];
}
```

---

## GOOGLE SHEETS MOCK CLASS

Build `classes/sheets_sync.php`:

```php
function sync_shortlisted_candidate($candidate, $opening, $application) {
    // Build the row exactly matching FinTree's recruiter sheet format
    $row = [
        'Sr. No.'                   => $application->id,
        'Broadcast Date'            => date('d-m-Y', $opening->broadcast_date),
        'Company Name'              => $opening->company,
        'Position'                  => $opening->title,
        'Location'                  => $opening->location,
        'Form Closure Date'         => date('d-m-Y', $opening->deadline),
        'Candidate Name'            => $candidate->fullname,
        'Candidate Number'          => $candidate->phone,
        'Shortlisted Date'          => date('d-m-Y', $application->shortlisted_date),
        'Status'                    => $application->status,
        'Auto Score'                => $application->auto_score,
    ];

    $webhook_url = get_config('local_finplacements', 'sheets_webhook_url');
    if (!empty($webhook_url)) {
        // REAL: POST to Apps Script webhook
        // $this->post_to_webhook($webhook_url, $row);
    }

    // MOCK: log the row
    global $DB;
    $record = new stdClass();
    $record->row_data = json_encode($row);
    $record->timecreated = time();
    $record->status = 'mock_logged';
    $DB->insert_record('finplacements_sheets_log', $record);
    return ['status' => 'mocked', 'row' => $row];
}
```

---

## RELIABILITY SCORE RULES

In `reliability_manager.php`, implement:

```php
// On shortlisted: +5
// On forwarded to company: +10
// On placed (got the job): +15
// On offer backed out: -20
// On no-show to interview: -10
// Score floor: 0, Score ceiling: 100
// New candidate record created automatically on first application

function update_score($candidateid, $event) {
    // events: 'shortlisted', 'forwarded', 'placed', 'backed_out', 'no_show'
    $changes = [
        'shortlisted' => +5,
        'forwarded'   => +10,
        'placed'      => +15,
        'backed_out'  => -20,
        'no_show'     => -10,
    ];
    // ... fetch record, apply change, clamp 0-100, save
}
```

---

## UI REQUIREMENTS FOR EACH PAGE

### profile.php
- Show completion percentage bar at top
- Group fields into sections: Personal Info, Academic Background, Professional Certifications, Work Experience, Documents
- Required fields marked with red asterisk
- Auto-populate name and email from `$USER` object
- Save button at bottom
- On save: recalculate profile_score, update profile_complete flag if score >= 80
- Success message after save

### jobs.php
- Header: "Available Openings"
- If profile incomplete: yellow banner "Complete your profile to apply — X% done" with link to profile
- Each opening shown as a card with:
  - Job ID badge (FNTR-YYMMDD-A304)
  - Role title (large)
  - Company name
  - Location pill
  - Experience required pill
  - Employment type badge (Full-time / Internship)
  - CFA/FRM required (if any)
  - Deadline (red if within 48 hours)
  - Apply button — disabled and shows "Applied" if already applied
  - Apply button — disabled and shows "Deadline passed" if after deadline
- Filter bar: by location, employment type
- Empty state: "No openings available right now. Check back soon."

### apply.php
- Not a form page — processes GET request with openingid
- Checks: logged in, has profile, profile complete enough (>50%), not already applied, deadline not passed
- On success: redirect to tracker with success message
- On duplicate: redirect to tracker with "Already applied" message

### tracker.php
- Header: "My Applications"
- Table with columns: Opening, Company, Location, Applied On, Deadline, Status
- Status shown as coloured badge:
  - Applied = gray
  - Screened = blue
  - Shortlisted = amber
  - Forwarded = purple
  - Placed = green
  - Rejected = red
- Empty state: "You haven't applied to any openings yet."

### admin/openings.php
- Table of all openings: Job ID, Title, Company, Exp Required, Deadline, Status, Applications count, Actions
- Status filter dropdown (draft/active/closed)
- "Create New Opening" button at top
- Actions per row: Edit, View Applications, Close Opening

### admin/opening_edit.php
- Full form to create/edit an opening
- Fields:
  - Title, Company, Location
  - Employment type (radio: Full-time / Internship)
  - Experience required (dropdown: Fresher / 1-3 yrs / 3-5 yrs / 5-10 yrs / 10+ yrs / Any)
  - If Full-time: Min CTC, Max CTC
  - If Internship: Stipend amount
  - CFA required (dropdown: None / Level 1 / Level 2 / Level 3 / Charter)
  - FRM required (dropdown: None / Part 1 / Part 2 / Charter)
  - Qualification notes (textarea)
  - Broadcast date (date picker)
  - Deadline (date picker)
  - JD text (large textarea — paste full JD here)
  - Role overview (textarea)
  - Status (draft/active)
- On save: auto-generate Job ID, save to DB
- Show generated Job ID prominently after save

### admin/applications.php
- Header: "[Opening Title] — Applications ([count])"
- Filter bar: City, Experience band, Min auto-score slider
- Table columns: Name, Phone, City, Exp, CFA, CTC, Auto Score, Reliability, Resume, Status, Select
- Auto-score colour: green (>=70), amber (40-69), gray (<40)
- Reliability score badge: green (>=70), amber (40-69), red (<40)
- Resume column: clickable link "View CV" opening in new tab
- Select column: checkbox for bulk shortlisting
- "Shortlist Selected" button at bottom
- "Mark as Rejected" button for cleanup

### admin/shortlist.php
- Confirmation page before committing shortlist
- Shows list of selected candidates with their details
- Shows preview of WhatsApp message that will be sent to each
- Shows preview of Google Sheets row that will be synced
- "Confirm Shortlist" button
- On confirm: update statuses, trigger mock WA, trigger mock sheets sync, redirect to applications with success message

---

## CAPABILITIES TO DEFINE IN db/access.php

```php
'local/finplacements:candidate' => [
    'captype'      => 'read',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes'   => ['student' => CAP_ALLOW],
],
'local/finplacements:admin' => [
    'captype'      => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes'   => ['manager' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW],
],
```

---

## NAVIGATION SETUP IN lib.php

```php
function local_finplacements_extend_navigation(global_navigation $nav) {
    global $USER;
    if (!isloggedin() || isguestuser()) return;

    $context = context_system::instance();
    $node = $nav->add(
        get_string('pluginname', 'local_finplacements'),
        null, navigation_node::TYPE_ROOTNODE
    );

    if (has_capability('local/finplacements:candidate', $context)) {
        $node->add('My Profile', new moodle_url('/local/finplacements/profile.php'));
        $node->add('Job Board', new moodle_url('/local/finplacements/jobs.php'));
        $node->add('My Applications', new moodle_url('/local/finplacements/tracker.php'));
    }

    if (has_capability('local/finplacements:admin', $context)) {
        $node->add('Manage Openings', new moodle_url('/local/finplacements/admin/openings.php'));
    }
}
```

---

## PHASE EXECUTION INSTRUCTIONS

Work through phases in order. Complete each phase fully before starting the next.

### Phase 1 — Skeleton
Build: version.php, lib.php, db/access.php, lang/en/local_finplacements.php
Goal: Plugin installs in Moodle with no errors. Navigation appears.

### Phase 2 — Database + Profile
Build: db/install.xml, classes/candidate_manager.php, profile.php, styles.css (base)
Goal: Candidate can fill and save profile. Data persists in DB.

### Phase 3 — Job Board + Apply
Build: classes/opening_manager.php, classes/application_manager.php, jobs.php, apply.php
Build: admin/openings.php, admin/opening_edit.php
Goal: Admin creates opening with auto Job ID. Candidate sees it on job board and applies.

### Phase 4 — Shortlisting + Mock Comms
Build: admin/applications.php, admin/shortlist.php, classes/whatsapp_mock.php, classes/sheets_sync.php
Goal: Admin sees colour-coded applicants, ticks shortlist, sees WhatsApp preview and Sheets row preview.

### Phase 5 — Tracker + Reliability
Build: tracker.php, classes/reliability_manager.php
Goal: Candidate sees application statuses. Reliability score updates on events.

### Phase 6 — Polish
Polish all pages: access control enforcement, empty states, flash messages, mobile CSS, deadline enforcement, duplicate prevention UI, profile completion banner.
Goal: Complete UX. All edge cases handled. Ready for demo.

---

## WHAT SUCCESS LOOKS LIKE

When all phases are complete, the following demo flow must work without errors:

**Admin flow**:
1. Log in as admin
2. Go to Manage Openings → Create New Opening
3. Fill in role details, set deadline, paste JD text, set status to Active
4. Save — Job ID auto-generates as FNTR-YYMMDD-A304
5. Opening appears in list with status Active

**Candidate flow**:
1. Log in as student
2. See "Placements" in navigation
3. Go to My Profile — fill all fields, save — see completion bar reach 100%
4. Go to Job Board — see the opening admin created
5. Click Apply — one click, no form to fill
6. Go to My Applications — see the opening listed with status "Applied"

**Admin shortlisting flow**:
1. Go to Manage Openings → View Applications on the opening
2. See the candidate with their auto-score (colour coded)
3. See their reliability score badge
4. Click their resume link
5. Tick the checkbox next to them → Shortlist Selected
6. Shortlist confirmation page shows:
   - Candidate details
   - WhatsApp message preview (exact FinTree format)
   - Google Sheets row preview
7. Confirm → status updates to Shortlisted

**Candidate sees update**:
1. Candidate goes to My Applications
2. Status now shows "Shortlisted" (amber badge)

---

## IMPORTANT IMPLEMENTATION NOTES

1. Never use raw SQL. Always use `$DB->get_record()`, `$DB->get_records()`, `$DB->insert_record()`, `$DB->update_record()`.
2. Always call `require_login()` at the top of every page.
3. Always call `require_capability()` before any admin action.
4. Always use `sesskey()` verification on any form that writes data.
5. Use `$OUTPUT->notification()` for flash messages.
6. Use `$OUTPUT->header()` and `$OUTPUT->footer()` on every page.
7. Use `$PAGE->set_title()`, `$PAGE->set_heading()`, `$PAGE->set_url()` on every page.
8. Escape all output with `format_string()` or `s()` — never echo raw DB data.
9. The plugin must install cleanly with no errors — test the install.xml carefully.
10. The mock WhatsApp and Sheets classes must log to the DB for verification — not just to error_log.

---

## START NOW

Begin with Phase 1. Output every file completely — no truncation, no placeholders, no "// rest of code here". Every file must be complete and working. Output files in this order:

1. version.php
2. lang/en/local_finplacements.php
3. db/access.php
4. lib.php

Then confirm Phase 1 is complete and move to Phase 2 automatically.
