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
 * Course Image.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local\utilities;

use context_course;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class courseimage {

    /**
     * Declare the class properties.
     */
    public $imageurl;

    /**
     * courseimage constructor.
     *
     * @TODO Add in Totara Course Appearance image.
     *
     * @param \stdClass $course Moodle Course Object.
     */
    public function __construct($course) {
        global $OUTPUT;

        $this->imageurl = $OUTPUT->image_url('course_defaultimage', 'moodle');

        if ($course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);
            if ($context) {
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, "filename", false);
                if (count($files) > 0) {
                    foreach ($files as $file) {
                        if ($file->is_valid_image()) {
                            $url = moodle_url::make_pluginfile_url(
                                $file->get_contextid(),
                                $file->get_component(),
                                $file->get_filearea(),
                                null,
                                $file->get_filepath(),
                                $file->get_filename()
                            );
                            $this->imageurl = $url;
                        }
                    }
                }
            }
        }
    }

}
