# 🧪 Testing Checklist - FinTree Placements v1.1.0

## Pre-Testing Setup

### 1. Install Demo Data
1. Log in to Moodle as **admin**
2. Navigate to: `http://localhost/moodle/local/finplacements/generate_demo_data.php`
3. Click **"🚀 Generate Demo Data"**
4. Wait for completion (should create 40 openings, 100 candidates, 250+ applications)
5. **IMPORTANT**: Delete `generate_demo_data.php` after use

### 2. Verify Database
Check that you have:
- [ ] 40 openings in `mdl_local_finplacements_openings`
- [ ] 100 candidates in `mdl_local_finplacements_candidates`
- [ ] 250+ applications in `mdl_local_finplacements_applications`
- [ ] 100 reliability records in `mdl_local_finplacements_reliability`

---

## Test Suite 1: Job Board (Candidate View)

### Test 1.1: Basic Job Board Load
- [ ] Navigate to Job Board as a student/candidate
- [ ] Page loads without errors
- [ ] Shows 10 jobs per page (pagination visible if >10 active openings)
- [ ] Job cards display correctly with:
  - [ ] Job ID (FNTR-YYMMDD-A###)
  - [ ] Job title
  - [ ] Company name
  - [ ] Location
  - [ ] Experience required
  - [ ] Employment type badge
  - [ ] Deadline
  - [ ] Posted date ("Posted X days ago")
  - [ ] Applicant count ("X people applied")
  - [ ] Company logo (if URL provided)

### Test 1.2: Keyword Search
- [ ] Type "analyst" in search box
- [ ] Results update **without page reload** (AJAX working)
- [ ] Shows jobs with "analyst" in title, company, OR JD
- [ ] Search is case-insensitive
- [ ] Clear button resets search

**Test weighted relevance:**
- [ ] Search for "equity" - jobs with "Equity" in title should rank higher than jobs mentioning it only in JD
- [ ] Verify relevance sorting works

### Test 1.3: Location Filter
- [ ] Type "Mumbai" in location filter
- [ ] Results update via AJAX
- [ ] Only shows Mumbai jobs
- [ ] Combine with keyword search (e.g., "analyst" + "Mumbai")

### Test 1.4: Employment Type Filter
- [ ] Select "Full-time" - shows only full-time jobs
- [ ] Select "Internship" - shows only internships
- [ ] Verify filter works with other filters combined

### Test 1.5: Experience Band Filter
- [ ] Select "Fresher" - shows jobs for freshers (and "any" experience)
- [ ] Select "1-3 years" - filters correctly
- [ ] Test combined with other filters

### Test 1.6: CFA/FRM Filters
- [ ] Check "CFA Required" - shows only jobs requiring CFA
- [ ] Check "FRM Required" - shows only jobs requiring FRM
- [ ] Test both checkboxes together

### Test 1.7: Sort Options
Test each sort option:
- [ ] **Most Recent** - newest openings first
- [ ] **Deadline (Soonest)** - urgent deadlines first
- [ ] **Company Name (A-Z)** - alphabetical by company
- [ ] **Job Title (A-Z)** - alphabetical by title
- [ ] **Relevance** - when keyword entered, sorts by relevance score

### Test 1.8: Pagination
- [ ] Navigate to page 2, 3, etc.
- [ ] "Previous" button works
- [ ] "Next" button works
- [ ] Page X of Y displays correctly
- [ ] Pagination preserves current filters
- [ ] Pagination works with AJAX (no full page reload)

### Test 1.9: Applied Jobs Display
- [ ] Apply to a job as a candidate
- [ ] Return to job board
- [ ] Applied job shows "✓ Applied" button (disabled, green)
- [ ] "View Details" button changes appropriately

### Test 1.10: Deadline Handling
- [ ] Jobs with deadlines <48hrs show "Closing soon!" warning
- [ ] Expired jobs show "Deadline Passed" (button disabled)
- [ ] Expired jobs cannot be applied to

### Test 1.11: Company Logo Display
- [ ] Jobs with logo URL show company logo (80x80px)
- [ ] Logo is properly sized and centered
- [ ] Invalid logo URLs don't break the card (image hidden gracefully)
- [ ] Jobs without logos display cleanly (no broken image icon)

### Test 1.12: Mobile Responsiveness
- [ ] Open job board on mobile device (or browser dev tools)
- [ ] Filter form stacks vertically on small screens
- [ ] Job cards stack in single column
- [ ] Company logo resizes appropriately
- [ ] Pagination buttons are tappable

### Test 1.13: Loading States
- [ ] When filtering, see loading spinner
- [ ] Job grid has subtle opacity change during load
- [ ] No flickering or multiple spinners
- [ ] Loading completes within 1-2 seconds

### Test 1.14: Error Handling
- [ ] Invalid filter values don't break the page
- [ ] Network errors show user-friendly message
- [ ] Empty search results show "No openings available" message

### Test 1.15: URL Bookmarking
- [ ] Apply filters, go to page 2
- [ ] Copy URL and open in new tab
- [ ] Same filters and page are preserved
- [ ] URL contains filter parameters (?keyword=...&location=...&page=1)

---

## Test Suite 2: Admin - Create Opening with Logo

### Test 2.1: Create Opening with Logo
- [ ] Navigate to Admin → Manage Openings → Create New Opening
- [ ] Fill in all required fields
- [ ] Add company logo URL (use: `https://via.placeholder.com/200x200.png?text=TEST`)
- [ ] Save opening
- [ ] Verify Job ID is generated correctly (FNTR-YYMMDD-A###)
- [ ] Logo preview shows in edit form
- [ ] Opening appears on job board with logo

### Test 2.2: Create Opening without Logo
- [ ] Create another opening without logo URL
- [ ] Save successfully
- [ ] Job card displays cleanly without logo (no broken image)

---

## Test Suite 3: Performance Testing

### Test 3.1: Load Time with 40 Openings
- [ ] Job board initial load: < 2 seconds
- [ ] AJAX filter response: < 1 second
- [ ] Pagination navigation: < 1 second

### Test 3.2: Database Query Efficiency
- [ ] Enable Moodle debug mode (Developer → Debugging)
- [ ] Load job board with filters
- [ ] Check for N+1 queries (should see minimal queries)
- [ ] No database errors in logs

### Test 3.3: Memory Usage
- [ ] Check PHP memory usage (should not exceed 128MB)
- [ ] No memory limit warnings

---

## Test Suite 4: Edge Cases

### Test 4.1: Empty States
- [ ] Search for non-existent keyword ("xyzabc123")
- [ ] Shows "No openings available" message
- [ ] Filters can be cleared

### Test 4.2: Special Characters
- [ ] Search with special chars: `&`, `<`, `>`, `"`, `'`
- [ ] No XSS vulnerabilities
- [ ] Page doesn't break

### Test 4.3: Very Long Text
- [ ] Job title with 100+ characters
- [ ] Company name with 50+ characters
- [ ] Cards display correctly (text truncates or wraps)

### Test 4.4: Concurrent Users
- [ ] Open job board in 2 different browsers (2 users)
- [ ] Both can filter and browse simultaneously
- [ ] No locking or blocking issues

### Test 4.5: Browser Back/Forward
- [ ] Apply filter, go to page 2
- [ ] Click browser back button
- [ ] Previous state is preserved
- [ ] Click forward button
- [ ] State is restored correctly

---

## Test Suite 5: Security

### Test 5.1: Capability Checks
- [ ] Log in as guest user → cannot access job board
- [ ] Log in as user without candidate capability → blocked
- [ ] Admin can access all pages

### Test 5.2: SQL Injection
- [ ] Search for: `' OR '1'='1`
- [ ] Search for: `'; DROP TABLE--`
- [ ] No SQL errors, query is properly escaped

### Test 5.3: XSS Prevention
- [ ] Search for: `<script>alert('XSS')</script>`
- [ ] Script doesn't execute
- [ ] Output is properly escaped

---

## Known Issues / Bugs to Fix

| Issue | Priority | Status |
|-------|----------|--------|
| [Leave blank for testers to fill] | | |

---

## Performance Benchmarks

| Metric | Target | Actual | Pass/Fail |
|--------|--------|--------|-----------|
| Job board initial load | < 2s | ___ | |
| AJAX filter response | < 1s | ___ | |
| Total openings | 40 | ___ | |
| Total candidates | 100 | ___ | |
| Total applications | 250+ | ___ | |
| Database queries per page load | < 10 | ___ | |

---

## Sign-off

**Tested by:** ________________  
**Date:** ________________  
**Version:** 1.1.0  
**Status:** ☐ PASS ☐ FAIL (with fixes noted above)

**Comments:**

