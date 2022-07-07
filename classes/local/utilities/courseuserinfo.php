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
 * User information about a specific course.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/*
 Object
(
    [totalactivities] => 2
    [completeactivities] => 0
    [completeactivitiesperc] => 0
    [incompleteactivities] => 2
    [incompleteactivitiesperc] => 100
    [iscoursecomplete] => 0
    [enrolmentdate] => 1570773699
)
 */

namespace theme_university\local\utilities;

defined('MOODLE_INTERNAL') || die();

class courseuserinfo {

    /**
     * Declare the class properties.
     */
    public $totalactivities = 0;
    public $completeactivities = 0;
    public $completeactivitiesperc = 0;
    public $incompleteactivities = 0;
    public $incompleteactivitiesperc = 0;
    public $infuture = 0;
    public $iscoursecomplete = 0;
    public $enrolmentdate = 0;
    public $enrolmentstarted = 0;
    public $enrolmentended = 0;

    /**
     * stats constructor.
     *
     * @param \stdClass $course Moodle Course Object.
     * @param int $userid User id.
     */
    public function __construct($course, $userid) {
        global $DB;

        $now = round(time(), -2);

        $info = new \completion_info($course);

        // Check this user is enroled.
        if ($info->is_tracked_user($userid)) {

            // Is the course in the future?
            if ($course->startdate > $now) {
                $this->infuture = 1;
            }

            // Add the user's enrollment info for this course.
            $sql = "
               SELECT ue.enrolid, ue.timestart,
                      IF(ue.timestart < :now, 1, 0) AS started,
                      IF (ue.timeend = 0 OR ue.timeend > :now2, 0, 1) AS ended
                 FROM {enrol} e
                 JOIN {user_enrolments} ue
                   ON ue.enrolid = e.id AND ue.status = :active
                WHERE e.courseid = :courseid AND e.status = :enabled
             ORDER BY e.sortorder ASC
            ";
            $enrolmentdata = $DB->get_records_sql($sql, [
                'courseid' => $course->id,
                'active' => ENROL_USER_ACTIVE,
                'enabled' => ENROL_INSTANCE_ENABLED,
                'now' => $now,
                'now2' => $now
            ], 0, 1);

            if (!empty($enrolmentdata)) {
                $enrolment = reset($enrolmentdata);
                $this->enrolmentdate = $enrolment->timestart;
                $this->enrolmentstarted = $enrolment->started;
                $this->enrolmentended = $enrolment->ended;
            }

            $completions = $info->get_completions($userid);

            // Check if completion tracking exists
            if (!empty($completions)) {
                $this->hascompletion = true;
            }

            // For aggregating activity completion.
            $activities = [];

            // Loop through course criteria.
            foreach ($completions as $completion) {
                $criteria = $completion->get_criteria();
                $complete = $completion->is_complete();

                if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                    $this->totalactivities++;
                    $activities[$criteria->moduleinstance] = $complete;
                    if ($complete) {
                        $this->completeactivities++;
                    } else {
                        $this->incompleteactivities++;
                    }
                }
            }
            if ($this->totalactivities > 0) {
                 // Percentage stats.
                $this->completeactivitiesperc = floor(100 * ($this->completeactivities / $this->totalactivities));
                $this->incompleteactivitiesperc = (100 - $this->completeactivitiesperc);
                // Are all of this courses' activities complete?
                if ($this->completeactivities > 0 && $this->completeactivities == $this->totalactivities) {
                    $this->iscoursecomplete = true;
                }
            }
        }

    }

}
