<?php
namespace local_finplacements\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event for when a candidate successfully submits a job application.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class application_submitted extends \core\event\base {

    /**
     * Initialise the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'finplacements_applications';
        $this->data['crud']        = 'c';
        $this->data['edulevel']    = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns the event name.
     */
    public static function get_name() {
        return 'Job application submitted';
    }

    /**
     * Returns short description of what happened.
     */
    public function get_description() {
        return "The user with id '{$this->userid}' submitted a job application for opening '{$this->other['openingid']}'.";
    }

    /**
     * Get URL related to the event.
     */
    public function get_url() {
        return new \moodle_url('/local/finplacements/tracker.php');
    }
}
