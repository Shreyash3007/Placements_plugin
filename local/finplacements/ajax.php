<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AJAX endpoint for application table status changes.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once(__DIR__ . '/lib.php');

// Ensure JSON response regardless of fatal errors
header('Content-Type: application/json');

try {
    // Basic security checks
    require_login();
    require_sesskey();

    $context = context_system::instance();
    require_capability('local/finplacements:admin', $context);

    // Get parameters
    $action = required_param('action', PARAM_ALPHA);
    
    if ($action !== 'updatestatus') {
        throw new \moodle_exception('invalidaction', 'error');
    }

    $appid = required_param('appid', PARAM_INT);
    $newstatus = required_param('newstatus', PARAM_TEXT);

    // Perform status update via the centralized manager
    \local_finplacements\application_manager::update_status($appid, $newstatus);

    // Provide immediate feedback to the JS promise
    echo json_encode([
        'status' => 'success',
        'appid'  => $appid,
        'newstatus' => $newstatus
    ]);
    die;

} catch (\Exception $e) {
    // Catch-all for API errors (capabilities, missing params, DB failures)
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
    die;
}
