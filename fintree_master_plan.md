# FinTree Placement Plugin — Complete Master Plan
## Project: local_finplacements (Moodle Local Plugin)

---

## 1. What We Are Building

A fully self-contained Moodle local plugin that replaces the entire manual placement workflow at FinTree Education. Built independently on a local Moodle instance, then deployed to the live Moodle server when ready.

### The Problem It Solves

| Current Pain | What Plugin Does |
|---|---|
| New Google Form created for every job opening | One permanent job board, openings added by admin |
| Candidates fill 30-field form repeatedly | One-time profile, apply with one click |
| 50-150 responses manually screened in Sheets | Auto-filtered by experience, location, CFA/FRM level |
| Phone numbers saved manually before WhatsApp | WhatsApp triggered automatically (mocked now, real later) |
| Recruiter sheet updated manually | Google Sheets syncs automatically on every key event |
| Candidate has no visibility on application status | Live tracker shows status per opening |
| Blacklist managed as a separate sheet | Reliability score built into every candidate record |

---

## 2. Plugin Identity

- **Plugin type**: Moodle local plugin
- **Plugin name**: `local_finplacements`
- **Folder location**: `[moodle_root]/local/finplacements/`
- **Database prefix**: `finplacements_`
- **Moodle version target**: 4.1+
- **PHP version**: 8.1+
- **Language**: PHP (Moodle API), HTML, CSS, JavaScript (vanilla)

---

## 3. Complete Architecture

### Three Modules, One Plugin

```
CANDIDATE MODULE          JOB BOARD MODULE         ADMIN MODULE
─────────────────         ────────────────         ────────────
profile.php               jobs.php                 admin/openings.php
 └─ Fill once             └─ See live openings      └─ Create/edit openings
 └─ Edit anytime          └─ Filtered by exp        └─ Set exp, deadline, JD
                                                    
apply.php                                          admin/applications.php
 └─ One-click apply                                 └─ All applicants per opening
 └─ Profile auto-attached                           └─ Filter by score
                                                    └─ Tick shortlist
tracker.php                                        
 └─ Status per opening    admin/shortlist.php      
 └─ Candidate-facing       └─ Final shortlist view
                           └─ Trigger email + WA
```

### Database Layer (4 Tables)

```
finplacements_candidates
 └─ id, userid, fullname, phone, email, city, state
 └─ experience_band (fresher/1-3/3-5/5-10/10+)
 └─ current_employer, current_ctc, designation
 └─ cfa_level, frm_level, other_certs
 └─ ug_field, pg_field, tenth_pct, twelfth_pct
 └─ resume_url, photo_url
 └─ courses_done (CFA L1/L2/L3, FRM P1/P2, FM)
 └─ package_purchased, enrollment_date
 └─ profile_complete (bool), timecreated, timemodified

finplacements_openings
 └─ id, jobid (FNTR-YYMMDD-A304 format)
 └─ title, company, location, employment_type
 └─ experience_required (maps to band)
 └─ min_ctc, max_ctc, stipend (for internships)
 └─ is_internship (bool)
 └─ qualification_required, cfa_required, frm_required
 └─ jd_text (full JD), role_overview
 └─ deadline, broadcast_date
 └─ status (draft/active/closed)
 └─ community_target (which WA community)
 └─ timecreated, createdby

finplacements_applications
 └─ id, candidateid, openingid
 └─ applied_date
 └─ status (applied/screened/shortlisted/forwarded/rejected/placed)
 └─ auto_score (0-100, computed on apply)
 └─ shortlisted_by, shortlisted_date
 └─ wa_sent (bool), email_sent (bool)
 └─ notes (admin notes field)

finplacements_reliability
 └─ id, candidateid
 └─ score (0-100, starts at 70)
 └─ times_applied, times_shortlisted
 └─ times_forwarded, times_placed
 └─ offers_backed_out, no_shows
 └─ last_updated
```

### Auto-Scoring Logic

When a candidate applies, the system auto-scores their fit against the opening:

```
Score = 0 to 100

Location match:         +25 points
Experience band match:  +25 points
CFA level match:        +20 points
FRM level match:        +10 points
CTC within range:       +10 points
Profile completeness:   +10 points

Score >= 70  → highlighted green in admin view
Score 40-69  → yellow
Score < 40   → gray (still visible, not hidden)
```

### Job ID Format

```
FNTR-[YYMMDD]-A[sequential_number]

Example: FNTR-260326-A304

- FNTR = FinTree constant
- 260326 = broadcast date (26 March 2026)
- A = constant
- 304 = auto-incremented from last opening number
```

Sequential number pulled from MAX(id) of openings table + offset to start from 304 (existing count).

---

## 4. File Structure (Complete)

```
local/finplacements/
│
├── version.php                    ← Plugin version, Moodle requires
├── lib.php                        ← Hook functions, navigation
├── db/
│   ├── access.php                 ← Capabilities definition
│   └── install.xml                ← Database schema (all 4 tables)
├── lang/
│   └── en/
│       └── local_finplacements.php ← All display strings
│
├── classes/
│   ├── candidate_manager.php      ← Candidate CRUD logic
│   ├── opening_manager.php        ← Openings CRUD + job ID gen
│   ├── application_manager.php    ← Application + scoring logic
│   ├── reliability_manager.php    ← Score tracking
│   ├── sheets_sync.php            ← Google Sheets mock sync
│   └── whatsapp_mock.php          ← WhatsApp mock layer
│
├── profile.php                    ← Candidate: fill/edit profile
├── jobs.php                       ← Candidate: view job board
├── apply.php                      ← Candidate: one-click apply
├── tracker.php                    ← Candidate: application tracker
│
├── admin/
│   ├── openings.php               ← Admin: create/manage openings
│   ├── opening_edit.php           ← Admin: add/edit single opening
│   ├── applications.php           ← Admin: view applicants per opening
│   └── shortlist.php              ← Admin: tick shortlist, trigger comms
│
├── templates/                     ← Mustache templates (optional)
│   ├── job_card.mustache
│   ├── candidate_card.mustache
│   └── status_badge.mustache
│
└── styles.css                     ← Plugin CSS
```

---

## 5. Phase-by-Phase Build Plan

---

### PHASE 1 — Plugin Skeleton + Environment
**Goal**: Valid plugin that Moodle recognises, installs, and shows in navigation.

**Deliverables**:
- XAMPP installed, Apache + MySQL running
- Moodle 4.x installed at localhost/moodle
- Admin account created
- Plugin folder created at local/finplacements
- version.php, lib.php, db/access.php, lang file created
- Plugin appears in Moodle's plugin list
- Navigation link appears for logged-in users

**Files built in this phase**:
- `version.php`
- `lib.php` (with navigation hook)
- `db/access.php` (two capabilities: candidate, admin)
- `lang/en/local_finplacements.php`

**Test**: Go to Site Admin → Plugins → Install pending plugins. Plugin installs with no errors. Navigation shows "Placements" link.

---

### PHASE 2 — Database + Candidate Profile
**Goal**: All 4 tables created. Candidate can fill and save their profile.

**Deliverables**:
- `db/install.xml` with all 4 tables
- `profile.php` — full form with all candidate fields matching the original 30-field form, minus redundant fields (email, name, courses, package, enrollment date pre-filled from Moodle user record)
- `classes/candidate_manager.php` — save, update, fetch profile
- Profile completeness score calculated on save
- Profile pre-fills from Moodle user data (name, email auto-populated)
- Validation on all required fields

**Fields on the profile form** (what candidate fills):
```
Personal:     Phone, City, State (Country default India)
Academic:     10th %, 12th %, UG status/field/GPA, PG status/field/GPA
Professional: CFA levels passed, FRM levels passed, Other certs
Work:         Employer, Place of work, CTC, Designation, Years of experience
Documents:    Resume URL (Google Drive link), Passport photo URL
```

**Fields NOT on form (pre-filled from Moodle/system)**:
```
Name, Email → from Moodle user record
Courses done with FinTree → from Moodle enrolment
Package purchased → future: from payment system
```

**Test**: Logged-in student fills profile, saves, profile persists. Re-opens profile, all fields pre-filled correctly.

---

### PHASE 3 — Job Board + One-Click Apply
**Goal**: Admin can create openings. Candidates see relevant openings and apply in one click.

**Deliverables**:
- `admin/openings.php` — list all openings with status
- `admin/opening_edit.php` — create/edit opening form with all fields
- Job ID auto-generated on create (FNTR-YYMMDD-A304 format)
- `jobs.php` — candidate-facing job board
  - Shows only ACTIVE openings
  - Filtered by candidate's experience band (they only see openings matching their level)
  - Each card shows: Job ID, Role, Company, Location, Deadline, Employment type
  - Apply button on each card
- `apply.php` — processes application
  - Checks candidate has complete profile (redirects to profile if not)
  - Creates application record
  - Calculates auto-score immediately
  - Prevents duplicate applications
  - Shows confirmation

**Test**: Admin creates opening. Candidate logs in, sees opening on job board. Clicks apply. Application record created in DB. Candidate cannot apply twice.

---

### PHASE 4 — Admin Shortlisting + Comms Trigger
**Goal**: Admin sees all applicants per opening, scores visible, ticks shortlist, triggers mock comms.

**Deliverables**:
- `admin/applications.php`
  - Lists all applicants for selected opening
  - Columns: Name, Experience, Location, CFA, CTC, Auto-score, Status, Resume link, Actions
  - Colour-coded by auto-score (green/yellow/gray)
  - Reliability score badge per candidate
  - Filter bar: by location, experience, min score
  - Resume link opens in new tab
  - Bulk select checkboxes
- `admin/shortlist.php`
  - Confirm shortlist action
  - On confirm: updates application status to 'shortlisted'
  - Triggers `whatsapp_mock.php` — logs message that would be sent
  - Triggers `sheets_sync.php` — logs sync event
  - Shows preview of WhatsApp message before sending
- `classes/whatsapp_mock.php`
  - Generates the exact WhatsApp message text (pre-screen update format)
  - Logs to Moodle's log table with full message content
  - Displays in admin UI as "Message that will be sent"
  - Ready to swap real API credentials in with one config change
- `classes/sheets_sync.php`
  - Generates the exact row that would be written to Google Sheets
  - Logs to Moodle's log table
  - Shows in admin UI as "Sheet row that will be synced"
  - Accepts webhook URL in config — when URL provided, fires real sync

**Test**: Admin views applications, sees colour-coded scores, ticks 3 candidates as shortlisted. System shows WhatsApp messages that would be sent. Status updates to shortlisted in DB. Candidate tracker shows updated status.

---

### PHASE 5 — Candidate Tracker + Reliability Score
**Goal**: Candidates see their application history and status. Reliability score updates automatically.

**Deliverables**:
- `tracker.php`
  - Lists all openings candidate applied to
  - Status per opening: Applied → Screened → Shortlisted → Forwarded → Result
  - Date applied, deadline, role details
  - Clean status timeline view
- `classes/reliability_manager.php`
  - Score starts at 70 for all candidates
  - Increments: +5 shortlisted, +10 forwarded, +15 placed
  - Decrements: -20 backed out after offer, -10 no-show
  - Score displayed as badge in admin view only (not visible to candidates)
  - Updates automatically on status changes
- Admin view enhancements
  - Reliability score badge on every candidate row
  - Filter: hide candidates below score threshold
  - Score history expandable per candidate

**Test**: Candidate applies, sees "Applied" status. Admin shortlists, candidate sees "Shortlisted". Reliability score updates correctly when admin marks outcomes.

---

### PHASE 6 — Polish, Navigation, Access Control
**Goal**: Clean UX, proper role-based access, everything locked down correctly.

**Deliverables**:
- Moodle navigation: Placements → My Profile / Job Board / My Applications
- Admin navigation: Site Admin → Placements → Openings / Applications
- Access control enforcement:
  - Candidates cannot access admin pages (403 redirect)
  - Admins can see all candidate data
  - Non-enrolled users see "not eligible" message
- Profile completeness banner — if profile incomplete, banner shows on job board
- Deadline enforcement — Apply button disabled after deadline
- Duplicate application prevention UI — shows "Already applied" instead of Apply button
- Mobile-responsive CSS for all pages
- Empty states — "No openings currently" / "No applications yet"
- Success/error flash messages on all form submissions

**Test**: Log in as student, try to access admin URL — blocked. Log in as admin, see all pages. Profile incomplete → banner shows on job board. After deadline → apply button disabled.

---

## 6. Mocked Integrations — Swap-In Ready

### WhatsApp Mock
The `whatsapp_mock.php` class generates the exact message text in FinTree's standard format:

```
*Subject: Application Update – [Role]*

Hello [Name],

You have been successfully pre-screened by FinTree, and your 
resume has been forwarded to the hiring company.

Company Name: *[Company]*
Role: *[Role]*
Location: *[Location]*

The company will contact you directly regarding next steps.

Best regards,
*FinTree Career Services Team*
```

To go live: add WhatsApp Business API credentials to plugin config. One line change.

### Google Sheets Mock
The `sheets_sync.php` class generates the exact row data in recruiter sheet format and logs it. To go live: add Google Sheets webhook URL to plugin config. One line change.

---

## 7. What This Demo Proves to Leadership

When you walk in with this plugin running on localhost:

1. **Show candidate flow**: Log in as a student → fill profile once → see job board → one-click apply → see tracker update
2. **Show admin flow**: Log in as admin → create opening (Job ID auto-generates) → see applications with colour-coded scores → tick shortlist → see WhatsApp message preview
3. **Show the before/after numbers**: 8 manual steps per opening → 1 (tick shortlist)
4. **Show the data**: Everything in one place, no scattered sheets, no phone saving

This is a working product, not a proposal.

---

## 8. Future Phases (Post-Approval)

- **Phase 7**: WhatsApp Business API live integration
- **Phase 8**: Google Sheets live sync
- **Phase 9**: Deploy to production Moodle
- **Phase 10**: Alumni community module (same DB, new pages)
- **Phase 11**: Analytics dashboard for leadership
- **Phase 12**: Support team module on same platform

---

## 9. Technical Decisions Summary

| Decision | Choice | Reason |
|---|---|---|
| Plugin type | local | Most flexible, full control |
| Database | Moodle's own DB (MySQL) | No external dependency |
| Frontend | PHP + Moodle APIs + vanilla JS | No framework needed, Moodle-native |
| Scoring | Server-side on apply | Fast, no client-side exposure |
| Comms | Mocked with swap-in ready | Build now, wire live later |
| Sheets | Mocked with webhook ready | Same approach |
| Roles | Moodle native capabilities | No custom auth needed |
| Job ID | Auto-generated on save | Preserves existing FNTR format |
