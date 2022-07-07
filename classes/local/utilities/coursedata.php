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
 * Course Data.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local\utilities;

use theme_university\local\util;

defined('MOODLE_INTERNAL') || die();

class coursedata {

    /**
     * Declare the class properties.
     */
    public $coursedata;

    /**
     * coursedata constructor.
     *
     * @param \stdClass $course Moodle Course Object.
     */
    public function __construct($course) {
        global $CFG;

        $this->coursedata = $course;

        // Get the core course instance object.
        if (class_exists('core_course_list_element')) {
            $courseinstance = new \core_course_list_element($course);
        } else if (file_exists($CFG->dirroot . '/lib/coursecatlib.php')) {
            // Allow for legacy use of the `get_course_details()` function.
            include_once($CFG->dirroot . '/lib/coursecatlib.php');
            $courseinstance = new \course_in_list($course);
        }

        // Get the course category data - Replace the default category property with the category object.
        $cat = new coursecategory($courseinstance);
        $this->coursedata->category = $cat->category;

        // Get the course summary - Include is required here.
        include_once ($CFG->dirroot.'/course/renderer.php');
        $chelper = new \coursecat_helper();
        $shortsummary = util::truncate_html($chelper->get_course_formatted_summary($courseinstance), 600);
        $this->coursedata->summary = format_text($shortsummary, $courseinstance->summaryformat);

        // Add the course image.
        $image = new courseimage($course);
        $this->coursedata->imageurl = $image->imageurl->out();

        // Add the course teachers.
        $teachers = new courseteachers($courseinstance);
        $this->coursedata->teachers = $teachers->teachers;

        // Lastly, add in the link to the course.
        $courselink = new \moodle_url('/course/view.php', array('id' => $courseinstance->id));
        $this->coursedata->courseurl = $courselink->out();
    }

}
