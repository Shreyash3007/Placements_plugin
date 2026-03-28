# 🚀 Quick Start Guide - Testing FinTree Placements v1.1.0

## Step-by-Step Testing Instructions

### Step 1: Deploy the Plugin

1. **Copy plugin folder** to your Moodle installation:
   ```
   Copy: c:\Users\Shreyash\Desktop\moodle_placement_plugin\local\finplacements\
   To:   [YOUR_MOODLE_ROOT]\local\finplacements\
   ```

2. **Log in to Moodle** as admin

3. **Trigger plugin upgrade**:
   - Navigate to: **Site Administration → Plugins → Install plugins**
   - Moodle will detect the version change (1.0.0 → 1.1.0)
   - Click **"Upgrade Moodle plugins now"**
   - This will run the upgrade script and add the `company_logo_url` field

---

### Step 2: Generate Demo Data

1. **Navigate to the generator script**:
   ```
   http://localhost/moodle/local/finplacements/generate_demo_data.php
   ```
   (Adjust URL based on your Moodle installation)

2. **Click "🚀 Generate Demo Data"**
   - Wait 10-30 seconds for completion
   - You should see:
     - ✓ 40 openings created
     - ✓ 100 candidates created
     - ✓ 250+ applications created
     - ✓ 100 reliability records created

3. **DELETE the generator script** (security):
   ```
   Delete: [MOODLE_ROOT]/local/finplacements/generate_demo_data.php
   ```

---

### Step 3: Test as Candidate

1. **Log in as a student** (or create a test student account)

2. **Navigate to Job Board**:
   - Click: **Placements → Job Board**
   - Or: `http://localhost/moodle/local/finplacements/jobs.php`

3. **Test the new features**:

   **A. AJAX Filtering** (no page reloads!)
   - Type "analyst" in the search box
   - Watch the jobs update instantly (no page refresh)
   - You should see a loading spinner

   **B. Advanced Filters**
   - Select Location: "Mumbai"
   - Select Experience: "Fresher"
   - Check "CFA Required"
   - All filters should combine and update via AJAX

   **C. Sorting**
   - Try each sort option:
     - Most Recent
     - Deadline (Soonest)
     - Company Name (A-Z)
     - Job Title (A-Z)
     - Relevance (search for "equity" first)

   **D. Pagination**
   - If you have >10 active openings, you'll see pagination
   - Click "Next →" to go to page 2
   - Should update without full page reload

   **E. Company Logos**
   - Look for job cards with company logos (every 5th job has one)
   - Logos should be 80x80px on the left side of the card

   **F. Posted Date & Applicant Count**
   - Each card shows "Posted X days ago"
   - Each card shows "X people applied"

---

### Step 4: Test as Admin

1. **Log in as admin**

2. **Create Opening with Logo**:
   - Navigate to: **Placements → Manage Openings**
   - Click **"+ Create New Opening"**
   - Fill in the form:
     - Title: "Test Analyst Position"
     - Company: "Test Corp"
     - Location: "Mumbai"
     - **Company Logo URL**: `https://via.placeholder.com/200x200.png?text=TEST`
     - Employment Type: Full-time
     - Experience: 1-3 years
     - Set deadline to 2 weeks from now
     - Status: Active
   - Click **Save**
   - Verify Job ID is generated (e.g., FNTR-260328-A344)
   - Logo preview should appear

3. **View Applications**:
   - Click **"View Apps"** on any opening
   - See colour-coded auto-scores
   - See reliability scores
   - Test filtering by city, experience, min score

---

### Step 5: Performance Testing

1. **Open browser DevTools** (F12)
2. **Go to Network tab**
3. **Load job board**
   - Should load in < 2 seconds
   - Check number of requests

4. **Test AJAX filtering**
   - Type in search box
   - Watch Network tab for AJAX request
   - Response should be < 1 second

5. **Check Console for errors**
   - Should be no JavaScript errors
   - No failed requests

---

### Step 6: Mobile Testing

1. **Open browser DevTools** (F12)
2. **Toggle device toolbar** (Ctrl+Shift+M or Cmd+Opt+M)
3. **Select mobile device** (iPhone, Android, etc.)
4. **Test job board**:
   - Filters should stack vertically
   - Job cards should be full width
   - Company logo should resize to 60x60px
   - Pagination buttons should be tappable

---

## Expected Results Summary

| Feature | Expected Behavior | Status |
|---------|------------------|--------|
| AJAX Filtering | No page reloads, instant updates | ☐ Pass ☐ Fail |
| Keyword Search | Searches title, company, AND JD | ☐ Pass ☐ Fail |
| Advanced Filters | All filters work and combine | ☐ Pass ☐ Fail |
| Sorting | All 5 sort options work | ☐ Pass ☐ Fail |
| Pagination | 10 jobs/page, prev/next work | ☐ Pass ☐ Fail |
| Company Logos | Display correctly when URL provided | ☐ Pass ☐ Fail |
| Posted Date | Shows "Posted X days ago" | ☐ Pass ☐ Fail |
| Applicant Count | Shows "X people applied" | ☐ Pass ☐ Fail |
| Mobile Responsive | Works on mobile devices | ☐ Pass ☐ Fail |
| Loading States | Spinner shows during AJAX | ☐ Pass ☐ Fail |
| Error Handling | Graceful error messages | ☐ Pass ☐ Fail |

---

## Troubleshooting

### Issue: AJAX not working (page reloads on filter)
**Solution**: 
1. Check browser console for JavaScript errors
2. Verify `amd/src/jobboard_filter.js` exists
3. Purge Moodle caches: **Site Administration → Development → Purge all caches**

### Issue: "Invalid capability" error
**Solution**:
1. Go to **Site Administration → Users → Permissions → Define roles**
2. Edit your role (or create test role)
3. Ensure `local/finplacements:candidate` is allowed

### Issue: Company logo not showing
**Solution**:
1. Check if logo URL is valid (open in new tab)
2. Verify `company_logo_url` field exists in database
3. Check browser console for image load errors

### Issue: No pagination visible
**Solution**:
1. Ensure you have >10 active openings
2. Check if filters are hiding results
3. Verify `$perpage = 10` in `jobs.php`

### Issue: Search not finding jobs in JD
**Solution**:
1. Verify `jd_text` field has content
2. Check SQL query in `opening_manager.php` includes JD in WHERE clause
3. Test with simple keyword first

---

## Next Steps After Testing

1. **Document any bugs** in the TEST_CHECKLIST.md file
2. **Report issues** with:
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable
   - Browser/console errors

3. **Prioritize fixes**:
   - P0: Critical bugs (breaks functionality)
   - P1: High priority (major UX issue)
   - P2: Medium priority (minor issue)
   - P3: Low priority (nice to have)

---

## Contact

For issues or questions, check:
- `QWEN.md` - Complete documentation
- `TEST_CHECKLIST.md` - Detailed test cases
- `fintree_master_plan.md` - Original architecture plan
