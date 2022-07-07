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
 * This is a utility class for the manager data.
 * It should be always used to obtain details about the current manager.
 *
 * To use this class:
 * $managerdata = new theme_university\local\utilities\managerdata([
 *     'manager' => <A Manager User Object>, // Optional - Defaults to $USER.
 *     'teamstats' => true // Optional.
 * ]);
 *
 *
 * Available resulting data:
 * $managerdata->data; // The data object or an empty array.
 * $managerdata->data['manager']; // Manager User Object.
 * $managerdata->data['teamstats']; // Manager Team statistics Object.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local\utilities;

use \theme_university\local\util as util;

defined('MOODLE_INTERNAL') || die;

/**
 * Class managerdata
 *
 * @package theme_university\local\utilities
 */
class managerdata {

    /**
     * Declare the class properties.
     */
    public $manager;
    public $teamstats;

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

        // The manager user object defaults to the global USER object unless added to the $includeoptions array.
        if (empty($includeoptions) || !array_key_exists('manager', $includeoptions)) {
            $includeoptions['manager'] = $USER;
        }

        // Check for a valid User.
        $this->data['hasmanagerdata'] = false;
        if (util::validate_user($includeoptions['manager'])) {
            // Always set $this->manager first.
            $this->manager = $includeoptions['manager'];
            // Next let's auto include all the other methods in the $includeoptions array.
            util::include_methods($this, $includeoptions);
        }
    }

    /**
     * The manager user data function.
     * Use this function to add extra manager data.
     */
    public function include_manager() {
        // Get the user data from the userdata utility class.
        $userdata = new \theme_university\local\utilities\userdata(['user' => $this->manager]);
        $this->manager = $userdata->user;

        // Add additional manager specific data here...
        $myteamurlobj = new \moodle_url('/my/teammembers.php');
        $this->manager->myteamurl = $myteamurlobj->out();

        // Assign the manager data to the ouput array.
        $this->data['manager'] = $this->manager;
        $this->data['hasmanagerdata'] = true;

    }

    /**
     * The manager's team statistics data function.
     * NOTE: Totara Only.
     */
    public function include_teamstats() {
        global $DB;

        $this->data['hasteamstats'] = false;

        if (util::is_totara()) {
            $teamdata = [];
            $teamids = \totara_job\job_assignment::get_staff_userids($this->manager->id);
            if (!empty($teamids)) {
                // Count the team.
                if ($teamids) {
                    $teamdata['staffnum'] = count($teamids);
                }

                // Get the team stats from the Core Totara Stats block.
                $config = (object)[
                    'statlearnerhours_hours' => 1,
                    'statlearnerhours'       => 1,
                    'statcoursesstarted'     => 1,
                    'statcompachieved'       => 1,
                    'statobjachieved'        => 1,
                    'statcoursescompleted'   => 1,
                ];
                $totarastats = totara_stats_manager_stats($this->manager, $config);
                if (!empty($totarastats)) {
                    foreach ($totarastats as $stat) {
                        $teamdata[$stat->string] = $DB->count_records_sql($stat->sql, $stat->sqlparams);
                    }
                }
                $this->teamstats = $teamdata;

                if (!empty($this->teamstats)) {
                    $this->data['teamstats'] = $this->teamstats;
                    $this->data['hasteamstats'] = true;
                }
            }
        }
    }

}
