<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin — shortlist confirmation page.
 *
 * Shows selected candidates with:
 *  - Candidate details summary
 *  - WhatsApp message preview
 *  - Google Sheets row preview
 *
 * On confirmation, updates statuses and triggers mock comms.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/finplacements/lib.php');

use local_finplacements\opening_manager;
use local_finplacements\application_manager;
use local_finplacements\whatsapp_mock;
use local_finplacements\sheets_sync;

require_login();

$context = context_system::instance();
require_capability('local/finplacements:admin', $context);

require_sesskey();

global $DB;

$openingid = required_param('openingid', PARAM_INT);
$action    = required_param('action', PARAM_ALPHA);

$opening = opening_manager::get_by_id($openingid);
if (!$opening) {
    throw new moodle_exception('error_notfound', 'local_finplacements');
}

$apps_url = new moodle_url('/local/finplacements/admin/applications.php', ['openingid' => $openingid]);

// Collect selected application IDs.
$selected = optional_param_array('selected', [], PARAM_INT);
$selected = array_filter($selected); // Remove empty/zero values.

if (empty($selected)) {
    redirect($apps_url,
        get_string('no_candidates_selected', 'local_finplacements'),
        null, \core\output\notification::NOTIFY_WARNING);
}

// ---------- CONFIRM POST: commit the shortlist ----------
if ($action === 'confirm') {
    // This fires after the confirmation page was shown.
    $confirmed_ids = required_param('confirmed_ids', PARAM_TEXT);
    $app_ids       = array_filter(array_map('intval', explode(',', $confirmed_ids)));

    foreach ($app_ids as $appid) {
        // Security: validate this application actually belongs to this opening.
        $app = application_manager::get_by_id($appid);
        if (!$app || (int)$app->openingid !== (int)$openingid) {
            continue; // Skip tampered or cross-opening IDs.
        }

        application_manager::update_status($appid, 'shortlisted', $USER->id);

        // Re-fetch after status update.
        $app       = application_manager::get_by_id($appid);
        $candidate = $app ? $DB->get_record('finplacements_candidates', ['id' => $app->candidateid]) : null;

        if ($candidate && $app) {
            $msg = whatsapp_mock::generate_prescreen_message(
                $candidate->fullname,
                $opening->company,
                $opening->title,
                $opening->location
            );

            // INSTITUTIONAL UPGRADE: Queue ALL external integrations (WA + Sheets) 
            // as a single background Adhoc Task. This makes the UI instant.
            $task = new \local_finplacements\task\send_message_task();
            $task->set_custom_data((object)[
                'phone' => $candidate->phone,
                'msg'   => $msg,
                'appid' => $appid
            ]);
            \core\task\manager::queue_adhoc_task($task);
        }
    }

    $count = count($app_ids);
    redirect($apps_url,
        "Successfully shortlisted {$count} candidates. WhatsApp messages have been queued for background delivery.",
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// ---------- REJECT action — direct commit, no preview ----------
if ($action === 'reject') {
    application_manager::bulk_reject($selected, $USER->id);
    redirect($apps_url,
        'Selected candidates marked as rejected.',
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// ---------- SHORTLIST action — show preview first ----------
// Fetch full application + candidate data for preview.
$preview_data = [];

foreach ($selected as $appid) {
    $app = application_manager::get_by_id($appid);
    if (!$app) continue;

    $candidate = $DB->get_record('finplacements_candidates', ['id' => $app->candidateid]);
    if (!$candidate) continue;

    $wa_msg  = whatsapp_mock::generate_prescreen_message(
        $candidate->fullname,
        $opening->company,
        $opening->title,
        $opening->location
    );

    // Build preview sheets row (without persisting yet).
    $sheets_row = [
        'Sr. No.'          => $app->id,
        'Broadcast Date'   => $opening->broadcast_date ? date('d-m-Y', $opening->broadcast_date) : '—',
        'Company Name'     => $opening->company,
        'Position'         => $opening->title,
        'Location'         => $opening->location,
        'Form Closure Date'=> date('d-m-Y', $opening->deadline),
        'Candidate Name'   => $candidate->fullname,
        'Candidate Number' => $candidate->phone,
        'Shortlisted Date' => date('d-m-Y'),
        'Status'           => 'Shortlisted',
        'Auto Score'       => $app->auto_score,
    ];

    $preview_data[] = [
        'app'        => $app,
        'candidate'  => $candidate,
        'wa_msg'     => $wa_msg,
        'sheets_row' => $sheets_row,
    ];
}

if (empty($preview_data)) {
    redirect($apps_url, 'No valid candidates found.', null, \core\output\notification::NOTIFY_WARNING);
}

$confirmed_ids_str = implode(',', $selected);

$PAGE->set_url(new moodle_url('/local/finplacements/admin/shortlist.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('shortlist_heading', 'local_finplacements') . ' — ' . format_string($opening->title));
$PAGE->set_heading(get_string('shortlist_heading', 'local_finplacements') . ' — ' . format_string($opening->title));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/finplacements/styles.css'));

echo $OUTPUT->header();
?>
<div class="fp-admin-page fp-shortlist-page">
  <div class="fp-admin-header">
    <h2>Confirm Shortlist — <?php echo s($opening->title); ?></h2>
    <a href="<?php echo s($apps_url->out(false)); ?>" class="fp-btn fp-btn-secondary fp-btn-sm">← Cancel</a>
  </div>

  <div class="fp-notice-info">
    Review the candidates below. When you confirm, their status will be updated to
    <strong>Shortlisted</strong>, the WhatsApp message below will be logged (and sent when
    API is live), and a row will be synced to the Google Recruiter Sheet.
  </div>

  <?php foreach ($preview_data as $idx => $item):
    $c   = $item['candidate'];
    $a   = $item['app'];
    $row = $item['sheets_row'];
  ?>
  <div class="fp-candidate-shortlist-card">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
      <div>
        <strong style="font-size:1rem;"><?php echo s($c->fullname); ?></strong>
        <span style="margin-left:12px;color:#546e7a;"><?php echo s($c->phone); ?></span>
        <span style="margin-left:12px;">📍 <?php echo s($c->city); ?></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <?php echo local_finplacements_score_badge((int)$a->auto_score); ?>
        <?php echo local_finplacements_status_badge('shortlisted'); ?>
      </div>
    </div>

    <!-- WhatsApp preview -->
    <div style="margin-top:14px;">
      <strong style="font-size:0.85rem;color:#2e7d32;">📱 WhatsApp Message Preview</strong>
      <pre class="fp-wa-preview"><?php echo s($item['wa_msg']); ?></pre>
    </div>

    <!-- Sheets row preview -->
    <div style="margin-top:14px;">
      <strong style="font-size:0.85rem;color:#1a237e;">📊 Google Sheets Row Preview</strong>
      <div class="fp-sheets-preview">
        <table class="fp-sheets-row-table">
          <thead>
            <tr>
              <?php foreach (array_keys($row) as $col): ?>
                <th><?php echo s($col); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <tr>
              <?php foreach ($row as $val): ?>
                <td><?php echo s((string)$val); ?></td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
  <?php endforeach; ?>

  <!-- Confirm form -->
  <form method="post"
        action="<?php echo s((new moodle_url('/local/finplacements/admin/shortlist.php'))->out(false)); ?>"
        style="margin-top:28px;">
    <input type="hidden" name="sesskey"       value="<?php echo sesskey(); ?>">
    <input type="hidden" name="openingid"     value="<?php echo (int)$openingid; ?>">
    <input type="hidden" name="action"        value="confirm">
    <input type="hidden" name="confirmed_ids" value="<?php echo s($confirmed_ids_str); ?>">

    <div style="display:flex;gap:12px;justify-content:flex-end;">
      <a href="<?php echo s($apps_url->out(false)); ?>" class="fp-btn fp-btn-secondary">← Cancel</a>
      <button type="submit" class="fp-btn fp-btn-success" id="fp-confirm-shortlist-btn">
        ✓ Confirm Shortlist (<?php echo count($preview_data); ?> candidate<?php echo count($preview_data) !== 1 ? 's' : ''; ?>)
      </button>
    </div>
  </form>
</div>
<?php
echo $OUTPUT->footer();
