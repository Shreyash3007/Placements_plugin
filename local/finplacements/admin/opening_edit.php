<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin — create/edit a single opening.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\opening_manager;

require_login();

$context = context_system::instance();
require_capability('local/finplacements:admin', $context);

$id = optional_param('id', 0, PARAM_INT); // 0 = create new.

$PAGE->set_url(new moodle_url('/local/finplacements/admin/opening_edit.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('edit_opening_heading', 'local_finplacements'));
$PAGE->set_heading(get_string('edit_opening_heading', 'local_finplacements'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

$opening  = ($id > 0) ? opening_manager::get_by_id($id) : null;
$saved    = false;
$new_jobid = '';
$errors   = [];

if ($opening === false) {
    throw new moodle_exception('error_notfound', 'local_finplacements');
}

// ---------- Handle form submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $data = new stdClass();
    $data->title               = required_param('title', PARAM_TEXT);
    $data->company             = required_param('company', PARAM_TEXT);
    $data->location            = required_param('location', PARAM_TEXT);
    $data->company_logo_url    = optional_param('company_logo_url', '', PARAM_URL);
    $data->employment_type     = required_param('employment_type', PARAM_ALPHA);
    $data->experience_required = required_param('experience_required', PARAM_ALPHANUMEXT);
    $data->min_ctc             = optional_param('min_ctc', '', PARAM_TEXT);
    $data->max_ctc             = optional_param('max_ctc', '', PARAM_TEXT);
    $data->stipend             = optional_param('stipend', '', PARAM_TEXT);
    $data->cfa_required        = optional_param('cfa_required', 'none', PARAM_ALPHANUMEXT);
    $data->frm_required        = optional_param('frm_required', 'none', PARAM_ALPHANUMEXT);
    $data->qualification_notes = optional_param('qualification_notes', '', PARAM_TEXT);
    $data->jd_text             = optional_param('jd_text', '', PARAM_RAW);
    $data->role_overview       = optional_param('role_overview', '', PARAM_TEXT);
    $data->community_target    = optional_param('community_target', '', PARAM_TEXT);
    $data->status              = optional_param('status', 'draft', PARAM_ALPHA);

    // Parse dates from HTML date inputs (YYYY-MM-DD → timestamp).
    $broadcast_str     = optional_param('broadcast_date', '', PARAM_TEXT);
    $deadline_str      = required_param('deadline', PARAM_TEXT);
    $data->broadcast_date = !empty($broadcast_str) ? strtotime($broadcast_str) : null;
    $data->deadline       = !empty($deadline_str)  ? strtotime($deadline_str)  : 0;

    // Validate required.
    if (empty($data->title))   $errors[] = 'Job title is required.';
    if (empty($data->company)) $errors[] = 'Company name is required.';
    if (empty($data->location)) $errors[] = 'Location is required.';
    if (!$data->deadline)      $errors[] = 'Deadline is required.';

    if (empty($errors)) {
        if ($opening) {
            opening_manager::update($opening->id, $data);
            $opening  = opening_manager::get_by_id($opening->id);
            $new_jobid = $opening->jobid;
        } else {
            $new_id   = opening_manager::create($data, $USER->id);
            $opening  = opening_manager::get_by_id($new_id);
            $new_jobid = $opening->jobid;
            $id        = $new_id;
        }
        $saved = true;
    }
}

// Default empty opening for create form.
if (!$opening) {
    $opening = new stdClass();
    $opening->id                  = 0;
    $opening->title               = '';
    $opening->company             = '';
    $opening->company_logo_url    = '';
    $opening->location            = '';
    $opening->employment_type     = 'fulltime';
    $opening->experience_required = 'any';
    $opening->min_ctc             = '';
    $opening->max_ctc             = '';
    $opening->stipend             = '';
    $opening->cfa_required        = 'none';
    $opening->frm_required        = 'none';
    $opening->qualification_notes = '';
    $opening->jd_text             = '';
    $opening->role_overview       = '';
    $opening->broadcast_date      = null;
    $opening->deadline            = 0;
    $opening->status              = 'draft';
    $opening->community_target    = '';
    $opening->jobid               = '';
}

$exp_bands  = local_finplacements_get_experience_bands_with_any();
$cfa_levels = local_finplacements_get_cfa_levels();
$frm_levels = local_finplacements_get_frm_levels();

echo $OUTPUT->header();
?>
<div class="fp-admin-page">
  <div class="fp-admin-header">
    <h2><?php echo get_string('edit_opening_heading', 'local_finplacements'); ?></h2>
    <a href="<?php echo s((new moodle_url('/local/finplacements/admin/openings.php'))->out(false)); ?>"
       class="fp-btn fp-btn-secondary fp-btn-sm">← Back to Openings</a>
  </div>

<?php
if ($saved) {
    echo '<div class="fp-job-id-result">
            <div>
              <div class="fp-job-id-label">Opening saved! Generated Job ID:</div>
              <div class="fp-job-id-value" id="fp-generated-jobid">' . s($new_jobid) . '</div>
            </div>
          </div>';
}
foreach ($errors as $err) {
    echo $OUTPUT->notification(s($err), 'error');
}
?>

  <div class="fp-opening-form">
    <form method="post" action="<?php echo s((new moodle_url('/local/finplacements/admin/opening_edit.php', ['id' => $id]))->out(false)); ?>" id="fp-opening-form">
      <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

      <div class="fp-form-grid">

        <!-- Title -->
        <div class="fp-field fp-field-wide">
          <label for="fp_title">Job Title <span class="fp-required">*</span></label>
          <input type="text" id="fp_title" name="title"
                 value="<?php echo s($opening->title); ?>"
                 placeholder="e.g. Equity Research Analyst" class="fp-input" required>
        </div>

        <!-- Company -->
        <div class="fp-field">
          <label for="fp_company">Company Name <span class="fp-required">*</span></label>
          <input type="text" id="fp_company" name="company"
                 value="<?php echo s($opening->company); ?>"
                 placeholder="e.g. HDFC Securities" class="fp-input" required>
        </div>

        <!-- Company Logo URL -->
        <div class="fp-field">
          <label for="fp_company_logo_url">Company Logo URL</label>
          <input type="text" id="fp_company_logo_url" name="company_logo_url"
                 value="<?php echo s($opening->company_logo_url ?? ''); ?>"
                 placeholder="https://example.com/logo.png" class="fp-input">
          <small class="fp-field-hint">Paste URL of company logo (PNG/JPG, ideally 200x200px). Leave empty if no logo.</small>
          <?php if (!empty($opening->company_logo_url)): ?>
            <div style="margin-top:10px;">
              <img src="<?php echo s($opening->company_logo_url); ?>" alt="Logo preview" 
                   style="max-height:80px;max-width:80px;border:1px solid #e0e0e0;border-radius:4px;">
            </div>
          <?php endif; ?>
        </div>

        <!-- Location -->
        <div class="fp-field">
          <label for="fp_location">Location <span class="fp-required">*</span></label>
          <input type="text" id="fp_location" name="location"
                 value="<?php echo s($opening->location); ?>"
                 placeholder="e.g. Mumbai" class="fp-input" required>
        </div>

        <!-- Employment type -->
        <div class="fp-field">
          <label>Employment Type <span class="fp-required">*</span></label>
          <div style="display:flex;gap:24px;margin-top:6px;">
            <label class="fp-checkbox-label">
              <input type="radio" name="employment_type" value="fulltime" id="fp_type_fulltime"
                     <?php echo ($opening->employment_type === 'fulltime') ? 'checked' : ''; ?>
                     onchange="fpToggleCTCFields()">
              Full-time
            </label>
            <label class="fp-checkbox-label">
              <input type="radio" name="employment_type" value="internship" id="fp_type_internship"
                     <?php echo ($opening->employment_type === 'internship') ? 'checked' : ''; ?>
                     onchange="fpToggleCTCFields()">
              Internship
            </label>
          </div>
        </div>

        <!-- Experience required -->
        <div class="fp-field">
          <label for="fp_experience_required">Experience Required <span class="fp-required">*</span></label>
          <select id="fp_experience_required" name="experience_required" class="fp-select">
            <?php foreach ($exp_bands as $val => $label): ?>
              <option value="<?php echo s($val); ?>"
                <?php echo ($opening->experience_required === $val) ? 'selected' : ''; ?>>
                <?php echo s($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Full-time CTC fields -->
        <div class="fp-fulltime-fields" id="fp-fulltime-fields" style="display:<?php echo ($opening->employment_type !== 'internship') ? 'contents' : 'none'; ?>;">
          <div class="fp-field">
            <label for="fp_min_ctc">Minimum CTC (LPA)</label>
            <input type="text" id="fp_min_ctc" name="min_ctc"
                   value="<?php echo s($opening->min_ctc); ?>"
                   placeholder="e.g. 6" class="fp-input">
          </div>
          <div class="fp-field">
            <label for="fp_max_ctc">Maximum CTC (LPA)</label>
            <input type="text" id="fp_max_ctc" name="max_ctc"
                   value="<?php echo s($opening->max_ctc); ?>"
                   placeholder="e.g. 10" class="fp-input">
          </div>
        </div>

        <!-- Internship stipend -->
        <div class="fp-internship-fields" id="fp-internship-fields" style="display:<?php echo ($opening->employment_type === 'internship') ? 'contents' : 'none'; ?>;">
          <div class="fp-field">
            <label for="fp_stipend">Stipend (per month)</label>
            <input type="text" id="fp_stipend" name="stipend"
                   value="<?php echo s($opening->stipend); ?>"
                   placeholder="e.g. ₹15,000" class="fp-input">
          </div>
        </div>

        <!-- CFA required -->
        <div class="fp-field">
          <label for="fp_cfa_required">CFA Level Required</label>
          <select id="fp_cfa_required" name="cfa_required" class="fp-select">
            <?php foreach ($cfa_levels as $val => $label): ?>
              <option value="<?php echo s($val); ?>"
                <?php echo ($opening->cfa_required === $val) ? 'selected' : ''; ?>>
                <?php echo s($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- FRM required -->
        <div class="fp-field">
          <label for="fp_frm_required">FRM Level Required</label>
          <select id="fp_frm_required" name="frm_required" class="fp-select">
            <?php foreach ($frm_levels as $val => $label): ?>
              <option value="<?php echo s($val); ?>"
                <?php echo ($opening->frm_required === $val) ? 'selected' : ''; ?>>
                <?php echo s($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Qualification notes -->
        <div class="fp-field fp-field-wide">
          <label for="fp_qualification_notes">Qualification Notes</label>
          <textarea id="fp_qualification_notes" name="qualification_notes"
                    rows="3" class="fp-textarea"
                    placeholder="Any additional qualification requirements…"><?php echo s($opening->qualification_notes); ?></textarea>
        </div>

        <!-- Broadcast date -->
        <div class="fp-field">
          <label for="fp_broadcast_date">Broadcast Date</label>
          <input type="date" id="fp_broadcast_date" name="broadcast_date"
                 value="<?php echo ($opening->broadcast_date ? date('Y-m-d', $opening->broadcast_date) : ''); ?>"
                 class="fp-input">
        </div>

        <!-- Deadline -->
        <div class="fp-field">
          <label for="fp_deadline">Application Deadline <span class="fp-required">*</span></label>
          <input type="date" id="fp_deadline" name="deadline"
                 value="<?php echo ($opening->deadline ? date('Y-m-d', $opening->deadline) : ''); ?>"
                 class="fp-input" required>
        </div>

        <!-- Status -->
        <div class="fp-field">
          <label for="fp_status">Opening Status</label>
          <select id="fp_status" name="status" class="fp-select">
            <option value="draft"  <?php echo ($opening->status === 'draft')  ? 'selected' : ''; ?>>Draft</option>
            <option value="active" <?php echo ($opening->status === 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="closed" <?php echo ($opening->status === 'closed') ? 'selected' : ''; ?>>Closed</option>
          </select>
        </div>

        <!-- WhatsApp community target -->
        <div class="fp-field">
          <label for="fp_community_target">WhatsApp Community Target</label>
          <input type="text" id="fp_community_target" name="community_target"
                 value="<?php echo s($opening->community_target); ?>"
                 placeholder="e.g. CFA Aspirants, Finance Professionals" class="fp-input">
        </div>

        <!-- Role overview -->
        <div class="fp-field fp-field-wide">
          <label for="fp_role_overview">Role Overview</label>
          <textarea id="fp_role_overview" name="role_overview"
                    rows="4" class="fp-textarea"
                    placeholder="Brief overview of the role as it will appear on the job card…"><?php echo s($opening->role_overview); ?></textarea>
        </div>

        <!-- JD text -->
        <div class="fp-field fp-field-wide">
          <label for="fp_jd_text">Full Job Description <span class="fp-hint">(paste full JD here)</span></label>
          <textarea id="fp_jd_text" name="jd_text"
                    rows="12" class="fp-textarea"
                    placeholder="Paste the complete job description here. This is stored internally for admin reference."><?php echo s($opening->jd_text); ?></textarea>
        </div>

      </div><!-- /fp-form-grid -->

      <div class="fp-form-actions" style="margin-top:24px;">
        <button type="submit" class="fp-btn fp-btn-primary" id="fp-save-opening-btn">
          Save Opening
        </button>
      </div>

    </form>
  </div><!-- /fp-opening-form -->
</div><!-- /fp-admin-page -->

<script>
function fpToggleCTCFields() {
  var isInternship = document.getElementById('fp_type_internship').checked;
  var ftFields = document.getElementById('fp-fulltime-fields');
  var inFields = document.getElementById('fp-internship-fields');
  if (ftFields) ftFields.style.display = isInternship ? 'none' : 'contents';
  if (inFields) inFields.style.display = isInternship ? 'contents' : 'none';
}
// Run on page load.
document.addEventListener('DOMContentLoaded', fpToggleCTCFields);
</script>

<?php
echo $OUTPUT->footer();
