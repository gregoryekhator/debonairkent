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
 * Returns random/fake data for settings for testing purposes.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local;

defined('MOODLE_INTERNAL') || die();


class mock_theme_config  {

    public $settings;

    public $name = "university";

    public function __construct($settings = null, $type = 'randomsettings') {
        if (is_object($settings)) {
            $this->settings = $settings;
        } else {
            if ($type == 'randomsettings') {
                $this->settings = new randomsettings($settings);
            } else if ($type == 'randompeople') {
                $this->settings = new randompeople($settings);
            } else {
                $this->settings = new randomcompanies($settings);
            }
        }
    }

    public function setting_file_url($setting, $filearea) {
        // Normally we'd create a complex url, based on the setting (filename) and filearea
        // but since we're faking it we can just generate store a url in the setting itself and return that.
        return $this->settings->$setting;
    }
}
