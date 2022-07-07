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
 * details.php
 *
 * This is built using the boost template to allow for new theme's using
 * Moodle's new Boost theme engine
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require('../../config.php');
global $DB;

//get the id passed from the customs.js
$id = $_POST['id'];

//used the id to fetch the course name, course summary and category id
$result = $DB->get_record_select('course','id=?', array($id));

//used the id to get the course activity that is completed by joining the course_modules and course_modules_completion tables
$records__per=$DB->get_records_sql("select * from {course_modules_completion}
where completionstate=1 And coursemoduleid IN (select id from
 {course_modules} where course = $id)");
//Count the result of that activity that are completed
$value = (count($records__per));


//used the id to get the course activity that is completed or not
$record=$DB->get_records_sql("select * from {course_modules} where course=$id");

// //Count the result of that activity that are not completed
 $totals = (count($record));
 $total = $totals -1;

//initialize the percentage
$percentage = 0;
//manipulate the completion percentage
if ($percentage != null) {
            $percentage = $percentage;
        } else {
            if (!empty($total)) {
                $percentage = floor(100 * ($value / $total));
            }
        }



//the datas receive from the database
$category = $result->category;
$coursename = $result->fullname;
$summary = $result->summary;

//used the category ID to get the category name
$categories = $DB->get_record_select('course_categories','id=?', array($category));
$categoryname = $categories->name;

//then passed the result as an array
echo $categoryname.',dikko,'.$coursename.',dikko,'.$summary.',dikko,'.$percentage;

?>
