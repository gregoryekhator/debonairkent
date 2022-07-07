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

class randomsettings  {

    protected $max; // number of settings items;
    protected $lorem; // lorem generator

    public function __construct($max = 3) {
        $this->max = $max;
        $this->lorem = new LoremIpsum();
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

        if (strstr($name, 'content_')) {
            return $this->content();
        }
        if (strstr($name, 'title_')) {
            return $this->title();
        }
        if (strstr($name, 'linktext_')) {
            return $this->linktext();
        }
        if (strstr($name, 'url_')) {
            return $this->url();
        }
        if (strstr($name, 'image_')) {
            return $this->image();
        }
        if (strstr($name, 'bootstrapcolor_')) {
            return $this->bootstrapcolor();
        }
        if (strstr($name, 'hexcolor')) {
            return $this->hexcolor();
        }
        return $this->lorem->word();
    }

    protected function content() {
        return $this->lorem->sentence();
    }

    protected function image() {
        return "https://source.unsplash.com/featured/?sig=" . rand();
    }

    protected function title() {
        return $this->lorem->words(rand(2, 7));
    }

    protected function linktext() {
        return $this->lorem->words(rand(1, 3));
    }
    protected function url() {
        return "#fakeurl";
    }
    protected function bootstrapcolor() {
        $colors = [
            'primary',
            'secondary',
            'success',
            'warning',
            'danger',
            'info',
            'light',
            'dark',
        ];
        return $colors[array_rand($colors)];
    }

    protected function hexcolor() {
        return sprintf("#%06x",rand(0, 0xFFFFFF));
    }
}
