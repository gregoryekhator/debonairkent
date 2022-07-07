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
 * Course Category Information.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local\utilities;

defined('MOODLE_INTERNAL') || die();

class coursecategory {

    /**
     * Declare the class properties.
     */
    public $category;

    /**
     * coursecategory constructor.
     *
     * @param \stdClass $course Moodle Course Object.
     */
    public function __construct($courseinstance) {
        global $CFG;

        if (class_exists('core_course_list_element')) {
            $category = \core_course_category::get($courseinstance->category);
        } else if (file_exists($CFG->dirroot . '/lib/coursecatlib.php')) {
            // Allow for legacy use of the `get_course_details()` function.
            include_once($CFG->dirroot . '/lib/coursecatlib.php');
            $category = \coursecat::get($courseinstance->category);
        }

        $this->category = $category;
    }

}
