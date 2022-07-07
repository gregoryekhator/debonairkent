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
 * This is a utility class for the user data.
 * It should be always used to obtain details about the current user.
 *
 * To use this class:
 * $userdata = new theme_university\local\utilities\userdata([
 *     'user' => <A User Object>, // Optional - Defaults to $USER.
 *     'courses' => <An array of Course Objects>, // Optional - Defaults to
 *     'badges' => <A Course ID>, // Optional.
 *     'userstats' => true // Optional.
 * ]);
 *
 *
 * Available resulting data:
 * $userdata->data; // The data object or an empty array.
 * $userdata->user; // User Object.
 * $userdata->courses; // An array list of Course objects.
 * $userdata->data['badges']; // An array list of badge objects.
 * $userdata->data['userstats']; // A simple array of completion statistics.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local\utilities;

use theme_university\local\util;
use theme_university\local\utilities as utilities;

defined('MOODLE_INTERNAL') || die;

/**
 * Class userdata
 *
 * @package theme_university\local\utilities
 */
class userdata {

    /**
     * Declare the class properties.
     */
    public $user;
    public $courses = [];
    public $courseuserinfo;
    public $badges = [];
    public $userstats;

    /** @var \stdClass $data */
    public $data = []; // This is the output array.

    /**
     * To add a new option you must create a new class property.
     *
     * userdata constructor.
     *
     * @param array $includeoptions
     */
    public function __construct(array $includeoptions = []) {
        global $USER;

        // The user object defaults to the global USER object unless added to the $includeoptions array.
        if (empty($includeoptions) || !array_key_exists('user', $includeoptions)) {
            $includeoptions['user'] = $USER;
        }

        // Check for a valid User.
        $this->data['hasuserdata'] = false;
        if (util::validate_user($includeoptions['user'])) {
            // Always set $this->user first.
            $this->user = $includeoptions['user'];
            // Next let's auto include all the other methods in the $includeoptions array.
            util::include_methods($this, $includeoptions);
        }
    }

    /**
     * The user data function.
     * Use this function to add extra user data.
     */
    public function include_user() {
        global $DB, $OUTPUT;

        // Add the user's full name.
        $this->user->fullname = fullname($this->user);

        // Add in the core user picture.
        $this->user->userpicture = $OUTPUT->user_picture($this->user, ['size' => '100', 'alt' => fullname($this->user)]);

        // Add the user's profile url.
        $profileurlobj = new \moodle_url('/user/profile.php', ['id' => $this->user->id]);
        $this->user->profileurl = $profileurlobj->out();

        // Get the user's last course accessed.
        $lastcourses = [];
        if (!empty($this->user->currentcourseaccess)) {
            $lastcourses = $this->user->currentcourseaccess;
            $lastcoursesids = array_keys($this->user->currentcourseaccess, max($this->user->currentcourseaccess));
        } elseif (!empty($this->user->lastcourseaccess)) {
            $lastcourses = $this->user->lastcourseaccess;
            $lastcoursesids = array_keys($this->user->lastcourseaccess, max($this->user->lastcourseaccess));
        }
        $this->user->haslastcourseaccessedid = false;
        if (!empty($lastcourses)) {
            $lastcoursesid = reset($lastcoursesids);
            $this->user->lastcourseaccessedid = $lastcoursesid;
            $this->user->lastcourseaccesseddate = $lastcourses[$lastcoursesid];
            $this->user->haslastcourseaccessedid = true;
        }

        // Add Totara only functionality.
        if (util::is_totara()) {

            // Recursively get this user's manager data.
            $managerids = \totara_job\job_assignment::get_all_manager_userids($this->user->id);
            if (!empty($managerids)) {
                // We only need the first record.
                $managerid = reset($managerids);
                if (!empty($managerid)) {
                    $manager = $DB->get_record('user', ['id' => $managerid]);
                    // We have this manager user object - get their user data.
                    $managerdata = new self(['user' => $manager]);
                    if ($managerdata->data['hasuserdata']) {
                        $this->user->manager = $managerdata->data;
                    }
                }
            }
        }

        $this->data['user'] = $this->user;
        $this->data['hasuserdata'] = true;

    }

    /**
     * Set the courses property to be an array of course objects.
     * If this function is not called, courses defaults to enrolled courses.
     *
     * @param array $specifiedcourses Array of course objects - array key is course id.
     */
    public function include_courses(array $specifiedcourses = []) {
        $this->data['hascourses'] = false;
        $this->courses = !empty($specifiedcourses) ? $specifiedcourses : enrol_get_all_users_courses($this->user->id, true);
        if (!empty($this->courses)) {
            $this->data['hascourses'] = true;
            foreach ($this->courses as $courseid => $course) {
                // Add the rest of the course data.
                $coursedata = new utilities\coursedata($course);
                $this->courses[$courseid] = $coursedata->coursedata;
            }
        }
    }

    /**
     * Include the specified user's badges data.
     *
     * @param int $courseid
     */
    public function include_badges($courseid = 0) {

        global $CFG;

        require_once($CFG->dirroot . '/lib/badgeslib.php');

        $this->data['hasbadges'] = false;
        if ($courseid == SITEID || empty($courseid)) {
            // Account for standard pages.
            $courseid = 0;
        }
        if ($this->data['badges'] = \badges_get_user_badges($this->user->id, $courseid, 0, 4, '', true)) {
            $this->data['hasbadges'] = true;
        }
    }

    /**
     * Include the specified user's course info for a single course.
     *
     * Updates the course object.
     */
    public function include_courseuserinfo() {

        // Get the courses to use.
        if (empty($this->courses)) {
            // The courses property is not set, so set it to the user's enrolled courses instead.
            $this->include_courses();
        }
        foreach ($this->courses as $courseid => $course) {
            $this->courses[$courseid]->courseuserinfo = new utilities\courseuserinfo($course, $this->user->id);
        }
    }

    /**
     * Include the specified user's completion data.
     * TODO: Lots more user stats should be added to this function.
     */
    public function include_userstats() {
        $this->userstats = new \stdClass();
        // Build the default data.
        $this->userstats->coursesenrolled = 0;
        $this->userstats->coursescompleted = 0;
        $this->userstats->incomplete = 0;

        // Get the courses to use.
        if (empty($this->courses)) {
            // The courses property is not set, so set it to the user's enrolled courses instead.
            $this->include_courses();
        }

        foreach ($this->courses as $course) {

            $this->userstats->coursesenrolled++; // Count the enrolled courses.

            $info = new \completion_info($course);
            $completions = $info->get_completions($this->user->id);

            // Check this user is enroled.
            if ($info->is_tracked_user($this->user->id)) {
                // For aggregating activity completion.
                $activities = [];
                $activities_complete = 0;

                // Loop through course criteria.
                foreach ($completions as $completion) {
                    $criteria = $completion->get_criteria();
                    $complete = $completion->is_complete();

                    if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                        $activities[$criteria->moduleinstance] = $complete;
                        if ($complete) {
                            $activities_complete++;
                        }
                    }
                }
                if ($activities_complete > 0 && $activities_complete == count($activities)) {
                    $this->userstats->coursescompleted++;
                }
            }
        }
        $this->userstats->incomplete = $this->userstats->coursesenrolled - $this->userstats->coursescompleted;

        // Add Totara only functionality.
        if (util::is_totara()) {

            $this->userstats->certificationstotal = 0;
            $this->userstats->overduetotal = 0;

            // Check if Totara Programs or Certifications are enabled and count them for the user.
            if (!totara_feature_disabled('programs')) {
                $cprograms = prog_get_all_programs($this->user->id, '', '', '', false);
            }

            if ($cprograms) {
                foreach ($cprograms as $cp) {
                    if (!prog_is_accessible($cp)) {
                        continue;
                    }
                    if (!empty($cp->timeexpires)) {
                        if ($cp->timeexpires < time()) {
                            $this->userstats->overduetotal++;
                        }
                    } else {
                        if ($cp->duedate > 0 && $cp->duedate < time()) {
                            $this->userstats->overduetotal++;
                        }
                    }
                    if (!empty($cp->certifid)) {
                        $this->userstats->certificationstotal++;
                    }
                }
            }
        }

        $this->data['userstats'] = $this->userstats;
    }

}
