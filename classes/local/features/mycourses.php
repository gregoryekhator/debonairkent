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
 * My Courses Feature for university
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local\features;

use \theme_university\local\utilities as utilities;
use stdclass;
use component_action;
use context_course;
use context_system;
use moodle_url;
use pix_icon;

defined('MOODLE_INTERNAL') || die();

class mycourses implements \renderable, \templatable {

    /**
     * Declare the class properties.
     */

    protected $mycourses = [];

    /**
     * Create the Class Contructor.
     */
    public function __construct($max = 12) {
        global $PAGE;

        $this->get_mycourses($max);
        foreach ($this->mycourses as $mycourseid => $mycourse) {
            $mycoursebadges = new utilities\userdata(['badges' => $mycourseid]);

            if ($mycoursebadges->data['hasbadges']) {
                // Let's use the badge data to get the Core badges output.
                /** @var \core_badges_renderer $badgerenderer */
                $badgerenderer = $PAGE->get_renderer('core', 'badges');
                $mycoursebadges->data['badgesoutput'] = $this->print_badges_list(
                    $mycoursebadges->data['badges'],
                    $mycoursebadges->data['user']->id,
                    true
                );
                $this->mycourses[$mycourseid]->badges = $mycoursebadges->data['badgesoutput'];
            }
        }
    }

    /**
     * Get course outline data from the rcids config settings.
     * @TODO Apply the max parameter.
     */
    public function get_mycourses($max = 12) {
        $userdata = new utilities\userdata(['courses' => [], 'courseuserinfo' => true]);

        foreach ($userdata->courses as $courseid => $course) {
            if ($userdata->user->haslastcourseaccessedid) {
                if ($courseid == $userdata->user->lastcourseaccessedid) {
                    $course->islastaccessed = true;
                }
            }
            $course->inprogress = false;
            $course->iscomplete = false;
            if ($userdata->courses[$courseid]->courseuserinfo->completeactivitiesperc == 100) {
                $course->iscomplete = true;
            } else {
                if (!empty($userdata->courses[$courseid]->courseuserinfo->completeactivitiesperc)) {
                    $course->inprogress = true;
                }
            }
            $this->mycourses[$courseid] = $course;
        }
    }

    // Outputs badges list.

    /**
     * @TODO Move this function out of here.
     */
    public function print_badges_list($badges, $userid, $profile = false, $external = false) {
        global $CFG, $USER;

        $items = [];
        foreach ($badges as $badge) {

            $item = new stdClass();

            if (!$external) {
                $context = ($badge->type == BADGE_TYPE_SITE) ? context_system::instance() : context_course::instance($badge->courseid);
                $bname = $badge->name;
                $imageurl = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
            } else {
                $bname = s($badge->assertion->badge->name);
                $imageurl = $badge->imageUrl;
            }

            $name = $bname;

            if (!empty($badge->dateexpire) && $badge->dateexpire < time()) {
                $image .= $this->output->pix_icon('i/expired',
                        get_string('expireddate', 'badges', userdate($badge->dateexpire)),
                        'moodle',
                        array('class' => 'expireimage'));
                $name .= '(' . get_string('expired', 'badges') . ')';
            }

            $download = $status = $push = '';
            $url = '';
            if (($userid == $USER->id) && !$profile) {
                $url = new moodle_url('mybadges.php', array('download' => $badge->id, 'hash' => $badge->uniquehash, 'sesskey' => sesskey()));
                $notexpiredbadge = (empty($badge->dateexpire) || $badge->dateexpire > time());
                $backpackexists = badges_user_has_backpack($USER->id);
                if (!empty($CFG->badges_allowexternalbackpack) && $notexpiredbadge && $backpackexists) {
                    $assertion = new moodle_url('/badges/assertion.php', array('b' => $badge->uniquehash));
                    $action = new component_action('click', 'addtobackpack', array('assertion' => $assertion->out(false)));
                    $push = $this->output->action_icon(new moodle_url('#'), new pix_icon('t/backpack', get_string('addtobackpack', 'badges')), $action);
                }

                $download = $this->output->action_icon($url, new pix_icon('t/download', get_string('download')));
                if ($badge->visible) {
                    $url = new moodle_url('mybadges.php', array('hide' => $badge->issuedid, 'sesskey' => sesskey()));
                    $status = $this->output->action_icon($url, new pix_icon('t/hide', get_string('makeprivate', 'badges')));
                } else {
                    $url = new moodle_url('mybadges.php', array('show' => $badge->issuedid, 'sesskey' => sesskey()));
                    $status = $this->output->action_icon($url, new pix_icon('t/show', get_string('makepublic', 'badges')));
                }
            }

            if (!$profile) {
                $url = new moodle_url('badge.php', array('hash' => $badge->uniquehash));
            } else {
                if (!empty($badge->uniquehash)) {
                    if (!$external) {
                        $url = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
                    } else {
                        $hash = hash('md5', $badge->hostedUrl);
                        $url = new moodle_url('/badges/external.php', array('hash' => $hash, 'user' => $userid));
                    }
                }
            }

            $item->id = $badge->id;
            $item->name = $name;

            $item->url = false;
            if (!empty($url)) {
                $item->url = $url->out();
            }

            $item->imageurl = $imageurl->out();

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Create an array of the data for use in a mustache template.
     * @param \renderer_base $output
     *
     * @return array|\stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $hasmycourses = false;
        $mycourses = [];
        if (!empty($this->mycourses)) {
            $hasmycourses = true;
            foreach ($this->mycourses as $course) {
                $mycourses[] = (array) $course;
            }
        }
        $data = [
            'hasmycourses' => $hasmycourses,
            'mycourses' => $mycourses
        ];

        return $data;
    }

    public function output() {
        global $OUTPUT;
        $data = $this->export_for_template($OUTPUT);
        return $OUTPUT->render_from_template('theme_university/mycourses', $data);
    }

}
