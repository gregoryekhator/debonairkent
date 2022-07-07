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
 * abstract parent class for adding settings
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;

abstract class settings implements \renderable, \templatable {

    // Unique string used for generating various strings, generally plural.
    protected static $uniquename;

    // Max number of child items required, set in child class.
    protected static $defaultcount;

    protected static $templatenamespace = 'theme_university';

    // Currently active theme, generally theme settings do nothing when another theme
    // is selected, so we can assume the current theme is where to get settinsg etc. from.
    protected $theme;

    public function __construct($theme = null) {
        global $PAGE;

        if ($theme === null) {
            $this->theme = $PAGE->theme;
        } else {
            $this->theme = $theme;
        }
    }

    /**
     * Get number of child items, done as a static method so it can be called by settings.php too.
     *
     * @return \stdClass
     */
    public static function count() {
        global $PAGE;
        // Look in settings for an override to the settings count, can be used to customise
        // university slightly without code changes.
        $countsetting = $PAGE->theme->name . '_' . static::$uniquename . 'count';
        return $PAGE->theme->settings->$countsetting ?? static::$defaultcount;
    }

    /**
     * Get custom settings data, for parent and each child setting page.
     *
     * @return \stdClass
     */
    public function get_settings() {

        $data = new stdClass();

        // Parent level data
        // TODO add a few standard top level settinsg using same pattern as children.

        // Child level data e.g. $data->customsettings = array of customsetting items.
        $items = $this->get_settingsitems();
        $data->{static::$uniquename} = $items;

        // $data->itemcountX = true. Handy for doing logic in logicless templates.
        $itemcount = 'itemcount' . count($items);
        $data->$itemcount = true;

        if (empty($items)) {
            $data->nocontent = true;
            $data->getstartedurl = $this->generate_settings_tab_link();
        }

        return $data;
    }
    /**
     * Get custom settings data for the numbered child items.
     *
     * @return array of stdClass items
     */
    public function get_settingsitems() {

        $items = array();
        for($i = 1; $i <= static::count(); $i++) {
            $item = $this->get_settingsitem($i);
            if ($this->valid_item($item)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * A stdClass with data from settings, return true if the info looks good, false to skip it.
     * Could check that any required fields are present for example.
     *
     * @return bool
     */
    public function valid_item(stdClass $item) {
        if (!empty((array)$item)) {
            return true;
        }
        return false;
    }
    /**
     * Get custom settings data for the specific child item.
     *
     * @return \stdClass
     */
    public function get_settingsitem($index) {
        $settings = $this->theme->settings;

        $name = static::$uniquename . $index;

        $item = new stdClass();

        $standardsettings = array(
            'title',
            'content',
            'linktext',
            'url',
            'bootstrapcolor',
            'hexcolor',
            'image',
            'fontawesomeicon',
        );
        foreach ($standardsettings as $keyword) {
            $settingname = $keyword . '_' . $name;
            if (!empty($settings->$settingname)) {
                if ($keyword == 'image') {
                    $item->$keyword = $this->theme->setting_file_url($settingname, $settingname);
                } else if (in_array($keyword, ['content'])) {
                    // For language, media filter etc. for content filter.
                    $item->$keyword = format_text($settings->$settingname, FORMAT_HTML);
                } else if (in_array($keyword, ['title', 'linktext'])) {
                    // For language, media filter etc. for heading filter..
                    $item->$keyword = format_string($settings->$settingname);
                } else {
                    $item->$keyword = $settings->$settingname;
                }
            }
        }
        $ignoredefaults = clone($item);
        unset($ignoredefaults->bootstrapcolor);
        unset($ignoredefaults->hexcolor);
        // Check if nothing is set (after removing things with defaults).
        if (!empty((array)$ignoredefaults)) {
            $item->uniqueid = $name;
            $item->index = $index;
            $item->editlink = $this->generate_settings_tab_link($index);
            return $item;
        } else {
            return new stdClass();
        }

    }

    /**
     * A url that points to the numbered child tab in settings e.g.
     * admin/settings.php?section=theme_universitycustom_customsettings#theme_universitycustom_customsettings1
     *
     * @return \moodle_url
     */
    public function generate_settings_tab_link($index = 1) {
        $settingsurl = '/admin/settings.php';
        $params = [
            'section' => 'theme_' . $this->theme->name . '_' . static::$uniquename,
        ];
        $anchor = 'theme_' . $this->theme->name . '_' . static::$uniquename . $index;
        return new \moodle_url($settingsurl, $params, $anchor);
    }

    /**
     * Create an array of the data for use in a mustache template.
     * @param \renderer_base $output
     *
     * @return array|\stdClass
     */
    public function export_for_template(\renderer_base $output) {
        return $this->get_settings();
    }

    /**
     * Create the output via a template
     * @param array|stdClass $data if you need to override the standard data
     * @param string $template if you need to override the standard template name
     *
     * @return string html output
     */
    public function output($data = null, $template = null) {
        global $OUTPUT;
        if ($data === null) {
            $data = $this->export_for_template($OUTPUT);
        }
        if ($template === null) {
            $template = static::$templatenamespace . '/' . static::$uniquename;
        }
        return $OUTPUT->render_from_template($template, $data);
    }

    /**
     * Output a single template
     * @param int $itemid
     *
     * @return string html output
     */
    public function output_single($itemid, $data = null, $template = null) {
        global $OUTPUT;
        if ($data === null) {
            $data = $this->get_settingsitem($itemid);
            $data->editlink = $this->generate_settings_tab_link($itemid);
        }
        if ($template === null) {
            // Do a really hacky job of singularizing the mustache name.
            $singular = rtrim(static::$uniquename, 's');
            $template = static::$templatenamespace . '/' . $singular . '_single';
        }
        return $OUTPUT->render_from_template($template, $data);
    }

}
