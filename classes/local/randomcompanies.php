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

class randomcompanies extends randomsettings {

    private $companies;

    public function __construct($max = 3) {
        if ($max > 0) {
            $companyjson = file_get_contents(__DIR__ . "/../../tests/fixtures/logos.json");
            $companies = json_decode($companyjson);
            $randomkeys = array_rand($companies, $max);
            $randomkeys = (array)$randomkeys;
            foreach ($randomkeys as $randomkey) {
                $this->companies[] = $companies[$randomkey];
            }
        } else {
            $this->companies = array();
        }

        parent::__construct($max);
    }

    public function __isset($name) {
        global $PAGE;
        $numbersuffix = (int) substr($name, -1);
        if ($numbersuffix && $numbersuffix > $this->max) {
            return false;
        }
        return true;
    }

    public function __get($name) {
        global $PAGE;

        $numbersuffix = (int) substr($name, -1);
        if (!$numbersuffix) {
            return parent::__get($name);
        }

        $company = $this->companies[$numbersuffix - 1];

        if (strstr($name, 'title_')) {
            return $company->name;
        }
        if (strstr($name, 'linktext_')) {
            return $company->url;
        }
        if (strstr($name, 'image_')) {
            return "https://combinatronics.com/gilbarbara/logos/master/logos/" . $company->files[0];
        }
        return parent::__get($name);
    }
}
