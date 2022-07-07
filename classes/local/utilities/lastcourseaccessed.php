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
 * This is a utility class for the last course accessed data.
 * It should be always used to obtains details about the last course the current user has accessed.
 *
 * To use this class:
 * $lastcourseaccessed = new theme_university\local\utilities\lastcourseaccessed();
 * $lastcourseaccessed->set_user($USER); // This part is optional.
 * $lastcourseaccessed->set_courses(enrol_get_my_courses()); // This part is optional.
 * $lastcourseaccessed->get_data();
 *
 * Available resulting data:
 * $lastcourseaccessed->hasdata; // True or False.
 * $lastcourseaccessed->data; // The last course accessed object or null.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local\utilities;

defined('MOODLE_INTERNAL') || die;

/**
 * Class lastcourseaccessed
 *
 * @package theme_university\local\utilities
 */
class lastcourseaccessed {

    /**
     * Declare the class properties.
     */
    protected $user;
    /** @var array $courses */
    protected $courses = [];
    /** @var bool $hasdata */
    public $hasdata = false;
    /** @var \stdClass $data */
    public $data = null; // This is the output object.

    /**
     * Set the user property to be a specific user object (optional).
     * If this function is not called, user defaults to the current $USER object.
     *
     * @param null $specifieduser
     */
    public function set_user($specifieduser = null) {
        global $USER;

        $this->user = !empty($specifieduser) ? $specifieduser : $USER;
    }

    /**
     * Set the courses property to be an array of course objects.
     * If this function is not called, courses defaults to enrolled courses.
     *
     * @param array $specifiedcourses Array of course objects - array key is course id.
     */
    public function set_courses(array $specifiedcourses = []) {
        $this->courses = !empty($specifiedcourses) ? $specifiedcourses : enrol_get_my_courses();
    }

    /**
     * Get the last course accessed by this user.
     */
    public function get_data() {
        global $DB;

        // Check if the user property has been set using set_user().
        if (empty($this->user)) {
            // The courses property is not set, so set it to the user's enrolled courses instead.
            $this->set_user();
        }

        $lastcourse = [];
        if (!empty($this->user->currentcourseaccess)) {
            $lastcourse = array_keys($this->user->currentcourseaccess, max($this->user->currentcourseaccess));
        } elseif (!empty($this->user->lastcourseaccess)) {
            $lastcourse = array_keys($this->user->lastcourseaccess, max($this->user->lastcourseaccess));
        }
        // Find the first element of the $lastcourse array above.
        $lastcourseid = reset($lastcourse);
        if ($lastcourseid) {
            // We have a course id, let's check if the courses array property has been set using set_courses().
            if (empty($this->courses)) {
                // The courses property is not set, so set it to the user's enrolled courses instead.
                $this->set_courses();
            }
            if (!empty($this->courses) && isset($this->courses[$lastcourseid])) {
                // The course exists in the courses property.
                $this->data = $this->courses[$lastcourseid];
            } else {
                // They have a lastcourseid but it not found in the courses property. Get the course from the DB instead.
                $this->data = $DB->get_record('course', ['id' => $lastcourseid]);
            }
            if ($this->data) {
                $this->hasdata = true;
            }
        }
    }

}
