<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin — openings list page.
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

$PAGE->set_url(new moodle_url('/local/finplacements/admin/openings.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('admin_openings_heading', 'local_finplacements'));
$PAGE->set_heading(get_string('admin_openings_heading', 'local_finplacements'));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

// --- Handle close action ---
$action    = optional_param('action', '', PARAM_ALPHA);
$openingid = optional_param('id', 0, PARAM_INT);

if ($action === 'close' && $openingid > 0) {
    require_sesskey();
    opening_manager::close_opening($openingid);
    redirect(new moodle_url('/local/finplacements/admin/openings.php'),
        get_string('opening_closed', 'local_finplacements'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// --- Filter ---
$filter_status = optional_param('status', '', PARAM_ALPHA);
$openings      = opening_manager::get_all_with_counts($filter_status ?: null);

echo $OUTPUT->header();
echo local_finplacements_render_nav('openings');

// Flash message check.
?>
<div class="fp-admin-page">

  <div class="fp-admin-header">
    <h2><?php echo get_string('admin_openings_heading', 'local_finplacements'); ?></h2>
    <a href="<?php echo s((new moodle_url('/local/finplacements/admin/opening_edit.php'))->out(false)); ?>"
       class="fp-btn fp-btn-primary" id="fp-create-opening-btn">
      + <?php echo get_string('create_opening', 'local_finplacements'); ?>
    </a>
  </div>

  <!-- Filter bar -->
  <form method="get" action="" class="fp-admin-filters" id="fp-status-filter-form">
    <div>
      <label><?php echo get_string('filter_by_status', 'local_finplacements'); ?></label>
      <select name="status" id="fp_filter_status" onchange="this.form.submit()">
        <option value=""><?php echo get_string('all_statuses', 'local_finplacements'); ?></option>
        <option value="draft"  <?php echo ($filter_status === 'draft')  ? 'selected' : ''; ?>>Draft</option>
        <option value="active" <?php echo ($filter_status === 'active') ? 'selected' : ''; ?>>Active</option>
        <option value="closed" <?php echo ($filter_status === 'closed') ? 'selected' : ''; ?>>Closed</option>
      </select>
    </div>
  </form>

<?php if (empty($openings)): ?>
  <div class="fp-empty-state">
    <div class="fp-empty-icon">📋</div>
    <h3>No openings found.</h3>
    <p>Create your first opening using the button above.</p>
  </div>
<?php else: ?>
  <div class="fp-table-wrap">
    <table class="fp-table" id="fp-openings-table">
      <thead>
        <tr>
          <th>Job ID</th>
          <th>Title</th>
          <th>Company</th>
          <th>Exp. Required</th>
          <th>Deadline</th>
          <th>Status</th>
          <th>Applications</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $exp_bands = local_finplacements_get_experience_bands_with_any();
        foreach ($openings as $opening):
          $app_count  = (int)($opening->app_count ?? 0); // Already in result from JOIN.
          $edit_url   = new moodle_url('/local/finplacements/admin/opening_edit.php', ['id' => $opening->id]);
          $apps_url   = new moodle_url('/local/finplacements/admin/applications.php', ['openingid' => $opening->id]);
          $close_url  = new moodle_url('/local/finplacements/admin/openings.php', [
                            'action'  => 'close',
                            'id'      => $opening->id,
                            'sesskey' => sesskey(),
                        ]);
          $deadline_passed = ($opening->deadline < time());
        ?>
        <tr>
          <td><code><?php echo s($opening->jobid); ?></code></td>
          <td><strong><?php echo s($opening->title); ?></strong></td>
          <td><?php echo s($opening->company); ?></td>
          <td><?php echo s($exp_bands[$opening->experience_required] ?? $opening->experience_required); ?></td>
          <td style="<?php echo $deadline_passed ? 'color:#c62828;font-weight:600;' : ''; ?>">
            <?php echo userdate($opening->deadline, get_string('strftimedate', 'langconfig')); ?>
          </td>
          <td>
            <span class="fp-type-badge <?php echo 'status-' . s($opening->status); ?>">
              <?php echo ucfirst(s($opening->status)); ?>
            </span>
          </td>
          <td>
            <a href="<?php echo s($apps_url->out(false)); ?>" style="font-weight:700;color:#1a237e;">
              <?php echo (int)$app_count; ?>
            </a>
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?php echo s($edit_url->out(false)); ?>"
                 class="fp-btn fp-btn-secondary fp-btn-sm" id="fp-edit-<?php echo (int)$opening->id; ?>">Edit</a>
              <a href="<?php echo s($apps_url->out(false)); ?>"
                 class="fp-btn fp-btn-primary fp-btn-sm" id="fp-viewapps-<?php echo (int)$opening->id; ?>">View Apps</a>
              <?php if ($opening->status !== 'closed'): ?>
              <a href="<?php echo s($close_url->out(false)); ?>"
                 class="fp-btn fp-btn-danger fp-btn-sm"
                 id="fp-close-<?php echo (int)$opening->id; ?>"
                 onclick="return confirm('Close this opening? This cannot be undone.');">Close</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</div>
<?php
echo $OUTPUT->footer();
