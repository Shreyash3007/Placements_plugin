<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin — applications list for a specific opening.
 *
 * Features:
 *  - SQL-level city, experience, and min-score filters (no PHP array_filter)
 *  - Clickable sortable column headers (auto_score, reliability, fullname)
 *  - Per-row pipeline actions: Forwarded, Placed, No-Show, Backed Out
 *  - Resume URL validated for http/https scheme before rendering
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\opening_manager;
use local_finplacements\application_manager;

require_login();

$context = context_system::instance();
require_capability('local/finplacements:admin', $context);

$openingid = optional_param('openingid', 0, PARAM_INT);

$opening = null;
if ($openingid > 0) {
    $opening = opening_manager::get_by_id($openingid);
    if (!$opening) {
        throw new moodle_exception('error_notfound', 'local_finplacements');
    }
}

// --- Filter params (passed to SQL query directly) ---
$filter_city  = optional_param('city',      '',  PARAM_TEXT);
$filter_exp   = optional_param('exp',       '',  PARAM_ALPHANUMEXT);
$filter_score = optional_param('min_score', 0,   PARAM_INT);
$sort         = optional_param('sort',      'auto_score', PARAM_ALPHANUMEXT);
$dir          = optional_param('dir',       'DESC',       PARAM_ALPHA);
// Toggle direction for links.
$next_dir = ($dir === 'DESC') ? 'ASC' : 'DESC';

$page_title = $opening ? (s($opening->title) . ' — Applications') : 'All Applications';

$PAGE->set_url(new moodle_url('/local/finplacements/admin/applications.php',
    $openingid ? ['openingid' => $openingid] : []));
$PAGE->set_context($context);
$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

// --- Fetch applications with SQL filters ---
if ($opening) {
    $applications = application_manager::get_for_opening(
        $openingid, $filter_city, $filter_exp, $filter_score, $sort, $dir
    );
} else {
    // All applications across all openings (admin overview — uses existing method).
    $applications = application_manager::get_all_applications();
}

$count = count($applications);

// Shared URL builder for sort links.
$base_params = ['openingid' => $openingid, 'city' => $filter_city, 'exp' => $filter_exp, 'min_score' => $filter_score];
$sort_url = function(string $col) use ($base_params, $sort, $next_dir, $dir): string {
    $d = ($sort === $col) ? $next_dir : 'DESC';
    $p = array_merge($base_params, ['sort' => $col, 'dir' => $d]);
    return (new moodle_url('/local/finplacements/admin/applications.php', $p))->out(false);
};
$sort_arrow = function(string $col) use ($sort, $dir): string {
    if ($sort !== $col) return '';
    return ' ' . ($dir === 'ASC' ? '↑' : '↓');
};

// URL for update_status actions.
$update_url = (new moodle_url('/local/finplacements/admin/update_status.php'))->out(false);

echo $OUTPUT->header();
echo local_finplacements_render_nav('applications');
?>
<div class="fp-admin-page">
  <div class="fp-admin-header">
    <h2><?php echo $opening ? s($opening->title) . ' — Applications (' . $count . ')' : 'All Applications (' . $count . ')'; ?></h2>
    <a href="<?php echo s((new moodle_url('/local/finplacements/admin/openings.php'))->out(false)); ?>"
       class="fp-btn fp-btn-secondary fp-btn-sm">← Back to Openings</a>
  </div>

<?php if ($opening): ?>
  <!-- Opening info strip -->
  <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;padding:12px 18px;background:#e8eaf6;border-radius:8px;font-size:0.88rem;">
    <span><strong>Job ID:</strong> <code><?php echo s($opening->jobid); ?></code></span>
    <span><strong>Company:</strong> <?php echo s($opening->company); ?></span>
    <span><strong>Location:</strong> <?php echo s($opening->location); ?></span>
    <span><strong>Deadline:</strong> <?php echo userdate($opening->deadline, get_string('strftimedate', 'langconfig')); ?></span>
    <span class="fp-type-badge <?php echo 'status-' . s($opening->status); ?>"><?php echo ucfirst(s($opening->status)); ?></span>
  </div>
<?php endif; ?>

  <!-- Filters — passed straight to SQL query -->
  <form method="get" action="" class="fp-admin-filters" id="fp-apps-filter-form">
    <?php if ($openingid): ?><input type="hidden" name="openingid" value="<?php echo (int)$openingid; ?>"><?php endif; ?>
    <input type="hidden" name="sort" value="<?php echo s($sort); ?>">
    <input type="hidden" name="dir"  value="<?php echo s($dir); ?>">

    <div>
      <label>City</label>
      <input type="text" name="city" id="fp_filter_city"
             value="<?php echo s($filter_city); ?>" placeholder="e.g. Mumbai">
    </div>
    <div>
      <label>Experience</label>
      <select name="exp" id="fp_filter_exp">
        <option value="">All</option>
        <?php foreach (local_finplacements_get_experience_bands() as $val => $lbl): ?>
          <option value="<?php echo s($val); ?>" <?php echo ($filter_exp === $val) ? 'selected' : ''; ?>>
            <?php echo s($lbl); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Min Auto-Score</label>
      <input type="number" name="min_score" id="fp_filter_score"
             value="<?php echo (int)$filter_score; ?>"
             min="0" max="100" style="width:80px;">
    </div>
    <div style="display:flex;align-items:flex-end;gap:8px;">
      <button type="submit" class="fp-btn fp-btn-primary fp-btn-sm">Filter</button>
      <a href="<?php echo s((new moodle_url('/local/finplacements/admin/applications.php', ['openingid' => $openingid]))->out(false)); ?>"
         class="fp-btn fp-btn-secondary fp-btn-sm">Clear</a>
    </div>
  </form>

<?php if (empty($applications)): ?>
  <div class="fp-empty-state">
    <div class="fp-empty-icon">👤</div>
    <h3>No candidates match your filters.</h3>
    <p>Try adjusting the filters above, or <a href="<?php echo s((new moodle_url('/local/finplacements/admin/applications.php', ['openingid' => $openingid]))->out(false)); ?>">clear all filters</a>.</p>
  </div>
<?php else: ?>

  <form method="post"
        action="<?php echo s((new moodle_url('/local/finplacements/admin/shortlist.php'))->out(false)); ?>"
        id="fp-bulk-form">
    <input type="hidden" name="sesskey"   value="<?php echo sesskey(); ?>">
    <input type="hidden" name="openingid" value="<?php echo (int)$openingid; ?>">

    <div class="fp-table-wrap">
      <table class="fp-table" id="fp-applications-table">
        <thead>
          <tr>
            <th><a href="<?php echo s($sort_url('fullname')); ?>" style="color:inherit;">Name<?php echo $sort_arrow('fullname'); ?></a></th>
            <th>Phone</th>
            <th>City</th>
            <th>Exp</th>
            <th>CFA</th>
            <th>CTC</th>
            <th><a href="<?php echo s($sort_url('auto_score')); ?>" style="color:inherit;">Auto Score<?php echo $sort_arrow('auto_score'); ?></a></th>
            <th><a href="<?php echo s($sort_url('reliability_score')); ?>" style="color:inherit;">Reliability<?php echo $sort_arrow('reliability_score'); ?></a></th>
            <th>Resume</th>
            <th>Status</th>
            <th>Actions</th>
            <th><input type="checkbox" id="fp-select-all" title="Select all" onchange="fpSelectAll(this)"></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $cfa_labels = local_finplacements_get_cfa_levels();
          foreach ($applications as $app):
            $rel_score   = isset($app->reliability_score) ? (int)$app->reliability_score : 70;
            // Validate resume URL — only render as link if http(s).
            $safe_resume = (!empty($app->resume_url) &&
                            preg_match('/^https?:\/\//i', $app->resume_url))
                           ? $app->resume_url : null;
          ?>
          <tr id="fp-app-row-<?php echo (int)$app->id; ?>">
            <td><strong><?php echo s($app->fullname); ?></strong></td>
            <td><?php echo s($app->phone); ?></td>
            <td><?php echo s($app->city); ?></td>
            <td><?php echo s($app->experience_band); ?></td>
            <td><?php echo s($cfa_labels[$app->cfa_level] ?? $app->cfa_level ?? '—'); ?></td>
            <td><?php echo s($app->current_ctc ?: '—'); ?></td>
            <td><?php echo local_finplacements_score_badge((int)$app->auto_score); ?></td>
            <td><?php echo local_finplacements_reliability_badge($rel_score); ?></td>
            <td>
              <?php if ($safe_resume): ?>
                <a href="<?php echo s($safe_resume); ?>" target="_blank"
                   rel="noopener noreferrer"
                   class="fp-btn fp-btn-secondary fp-btn-sm"
                   id="fp-cv-<?php echo (int)$app->id; ?>">View CV</a>
              <?php elseif (!empty($app->resume_url)): ?>
                <span style="color:#e53935;font-size:0.78rem;" title="Invalid URL scheme">⚠ Invalid URL</span>
              <?php else: ?>
                <span style="color:#9e9e9e;font-size:0.8rem;">No CV</span>
              <?php endif; ?>
            </td>
            <td id="fp-status-cell-<?php echo (int)$app->id; ?>">
              <?php echo local_finplacements_status_badge($app->status); ?>
            </td>

            <!-- Pipeline actions -->
            <td>
              <div style="display:flex;flex-direction:column;gap:4px;min-width:130px;">
                <?php if ($app->status === 'shortlisted'): ?>
                  <button type="button" class="fp-btn fp-btn-sm"
                          data-action="status-change" data-appid="<?php echo (int)$app->id; ?>" data-newstatus="forwarded"
                          style="background:#7c4dff;color:#fff;font-size:0.75rem;"
                          id="fp-fwd-<?php echo (int)$app->id; ?>">→ Forwarded</button>
                <?php endif; ?>
                <?php if ($app->status === 'forwarded'): ?>
                  <button type="button" class="fp-btn fp-btn-sm"
                          data-action="status-change" data-appid="<?php echo (int)$app->id; ?>" data-newstatus="placed"
                          style="background:#2e7d32;color:#fff;font-size:0.75rem;"
                          id="fp-placed-<?php echo (int)$app->id; ?>">🏆 Placed</button>
                <?php endif; ?>
                <?php if (!in_array($app->status, ['rejected', 'placed'])): ?>
                  <button type="button" class="fp-btn fp-btn-sm"
                          data-action="status-change" data-appid="<?php echo (int)$app->id; ?>" data-newstatus="no_show"
                          style="background:#ef6c00;color:#fff;font-size:0.75rem;"
                          id="fp-noshow-<?php echo (int)$app->id; ?>">No-Show</button>

                  <button type="button" class="fp-btn fp-btn-sm"
                          data-action="status-change" data-appid="<?php echo (int)$app->id; ?>" data-newstatus="backed_out"
                          style="background:#c62828;color:#fff;font-size:0.75rem;"
                          id="fp-backedout-<?php echo (int)$app->id; ?>">Backed Out</button>
                <?php endif; ?>
              </div>
            </td>

            <td>
              <input type="checkbox" name="selected[]"
                     value="<?php echo (int)$app->id; ?>"
                     id="fp-chk-<?php echo (int)$app->id; ?>"
                     class="fp-app-checkbox">
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="fp-bulk-actions">
      <button type="submit" name="action" value="shortlist"
              class="fp-btn fp-btn-success" id="fp-shortlist-btn">
        ✓ Shortlist Selected
      </button>
      <button type="submit" name="action" value="reject"
              class="fp-btn fp-btn-danger" id="fp-reject-btn"
              onclick="return confirm('Mark selected candidates as rejected?');">
        ✗ Mark as Rejected
      </button>
    </div>

  </form>
<?php endif; ?>
</div>

<script>
function fpSelectAll(checkbox) {
  document.querySelectorAll('.fp-app-checkbox').forEach(function(cb) {
    cb.checked = checkbox.checked;
  });
}
</script>
<?php
// Inject our custom native JS AMD module for rapid admin UX
$PAGE->requires->js_call_amd('local_finplacements/admin_ajax', 'init');

echo $OUTPUT->footer();

