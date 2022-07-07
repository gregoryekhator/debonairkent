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
 * Course Teachers.
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

class courseteachers {

    /**
     * Declare the class properties.
     */
    public $teachers;

    /**
     * courseteachers constructor.
     *
     * @param \stdClass $course Moodle Course Object.
     */
    public function __construct($courseinstance) {
        global $DB, $PAGE;


        if ($courseinstance->has_course_contacts()) {
            $teachers = array();
            $i = 0;
            $contacts = $courseinstance->get_course_contacts();
            foreach ($contacts as $contactid => $coursecontact) {
                $fields = \user_picture::fields();
                $sql = "SELECT $fields FROM {user} WHERE id = $contactid";
                $user = $DB->get_record_sql($sql);

                $image = new \user_picture($user);
                $teacher = new \stdClass();
                $teacher->src = $image->get_url($PAGE)->out();
                $teacher->name = $user->firstname.' '.$user->lastname;

                $teachers[] = $teacher;
                unset($contacts[$contactid]);
                $i++;
                if ($i == 3) {
                    break;
                }
            }
            $this->teachers = $teachers;

            if (count($contacts) > 1) {
                $numberleft = count($contacts);
                $names = array();
                foreach ($contacts as $coursecontact) {
                    $names[] = $coursecontact['user']->firstname.' '.$coursecontact['user']->lastname;
                }
                $names = implode("<br />", $names);
                $this->teachers->teachersextra = \html_writer::tag('div', '+'.$numberleft, ['class'=>'teachericon placeholder d-inline-block', 'data-toggle'=>"tooltip", 'data-placement'=>"bottom", 'data-html'=>"true", 'title'=>$names]);
            }
        }

    }

}
