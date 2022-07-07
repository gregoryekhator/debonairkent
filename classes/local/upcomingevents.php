<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The contents class for this block. All of the data for this block should be created here.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace block_sl_upcomingcertifications\local;

defined('MOODLE_INTERNAL') || die();

class content implements \renderable, \templatable {

    /**
     * Declare the class properties.
     */
    protected $themelib; // Theme library.
    protected $blocksettings; // The block settings.

    protected $certifications = [];

    /**
     * Create the Class Contructor.
     *
     * @param $blocksettings
     */
    public function __construct($blocksettings) {
        // Get the admin settings data for this block.
        $this->blocksettings = $blocksettings;
        // Make the theme library available.
        $this->themelib = new \theme_pennine\local\themelib;

        // Get the certifications data.
        $this->get_upcoming_certificiations();
    }

    /**
     * Get this user's upcoming certifications.
     */
    protected function get_upcoming_certificiations() {
        global $USER, $DB;

        // Get this user's certifications DB records.
        $sql = "SELECT p.id as pid, p.fullname, p.summary, cfc.timewindowopens, cfc.certifpath
                  FROM {prog} p
            INNER JOIN {certif_completion} cfc
                    ON (cfc.certifid = p.certifid AND cfc.userid = :uid)
                 WHERE p.visible = 1
                   AND (cfc.certifpath = :cert OR (cfc.certifpath = :recert AND cfc.renewalstatus = :due))
                   AND EXISTS (SELECT id
                                 FROM {prog_user_assignment} pua
                                WHERE pua.userid = cfc.userid
                                  AND pua.programid = p.id
                                  AND pua.exceptionstatus <> :raised
                                  AND pua.exceptionstatus <> :dismissed
                       )
              ORDER BY cfc.timewindowopens DESC";

        $params = [
            'uid' => $USER->id,
            'cert' => CERTIFPATH_CERT,
            'recert' => CERTIFPATH_RECERT,
            'due' => CERTIFRENEWALSTATUS_DUE,
            'raised' => PROGRAM_EXCEPTION_RAISED,
            'dismissed' => PROGRAM_EXCEPTION_DISMISSED
        ];
        $records = $DB->get_records_sql($sql, $params);
        if (!empty($records)) {
            foreach ($records as $record) {
                $data = new \stdClass();
                $data->name = $record->fullname;
                $data->summary = $record->summary;
                $url = new \moodle_url('/totara/program/required.php', ['id' => $record->pid]);
                $data->link = $url->out();

                $prog_completion = $DB->get_record(
                    'prog_completion',
                    ['programid' => $record->pid, 'userid' => $USER->id, 'coursesetid' => 0]
                );
                $data->hasduedate = false;
                $data->hasexpired = false;
                if ($prog_completion) {
                    if (!empty($prog_completion->timedue) && $prog_completion->timedue != COMPLETION_TIME_NOT_SET) {
                        $data->hasduedate = true;
                        $data->duedate = userdate($prog_completion->timedue, get_string('strftimedate', 'langconfig'));
                        if ($prog_completion->timedue < time()) {
                            $data->hasexpired = true;
                        }
                    }
                }
                $this->certifications[] = $data;
            }
        }
    }

    /**
     * Create an array of the data for use in a mustache template.
     * @param \renderer_base $output
     *
     * @return array|\stdClass
     */
    public function export_for_template(\renderer_base $output) {
        // Get the feature image.
        $featureimage = $output->pix_url('logo-circles', 'block_sl_upcomingcertifications');
        // Process the certifications data.
        $certifications = [];
        foreach ($this->certifications as $certification) {
            $certifications[] = (array) $certification;
        }
        $data = [
            'featureimage' => $featureimage->out(),
            'hascertifications' => (!empty($this->certifications) ? true : false),
            'certifications' => $certifications
        ];

        return $data;
    }
}
