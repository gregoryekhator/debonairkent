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
 * Render the progress bar for university.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace theme_university\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Class progressbar
 *
 * @package theme_university\local
 */
class progressbar implements \renderable, \templatable {

    /**
     * Declare the class properties.
     */
    protected $data;

    /**
     * progressbar constructor.
     *
     * @param null $total The total number of items.
     * @param null $value The number of items.
     * @param null $percentage An override percentage to be output directly.
     */
    public function __construct($total = null, $value = null, $percentage = null) {
        // Calculate the percentage.
        $this->data = new \stdClass();
        $this->data->percentage = 0;
        if ($percentage != null) {
            $this->data->percentage = $percentage;
        } else {
            if (!empty($total)) {
                $this->data->percentage = floor(100 * ($value / $total));
            }
        }

    }

    /**
     * Create an array of the data for use in a mustache template.
     * @param \renderer_base $output
     *
     * @return array|\stdClass
     */
    public function export_for_template(\renderer_base $output) {
        return $this->data;
    }

    /**
     * Output function to return html.
     * @return bool|string
     */
    public function output() {
        global $OUTPUT;

        $data = $this->export_for_template($OUTPUT);
        return $OUTPUT->render_from_template('theme_university/progressbar', $data);
    }

    /**
     * Return the mustache template output function as a string.
     * @return bool|string
     */
    public function __toString() {
        return $this->output();
    }

}
