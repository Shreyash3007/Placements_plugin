<?php
namespace local_finplacements\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event for when a candidate is shortlisted by an admin.
 *
 * @package    local_finplacements
 * @copyright  2026 FinTree Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class candidate_shortlisted extends \core\event\base {

    /**
     * Initialise the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'finplacements_applications';
        $this->data['crud']        = 'u';
        $this->data['edulevel']    = self::LEVEL_TEACHING;
    }

    /**
     * Returns the event name.
     */
    public static function get_name() {
        return 'Candidate shortlisted';
    }

    /**
     * Returns short description of what happened.
     */
    public function get_description() {
        return "The admin with id '{$this->userid}' shortlisted candidate with id '{$this->other['candidateid']}' for opening '{$this->other['openingid']}'.";
    }

    /**
     * Get URL related to the event.
     */
    public function get_url() {
        return new \moodle_url('/local/finplacements/admin/applications.php', ['openingid' => $this->other['openingid']]);
    }
}
