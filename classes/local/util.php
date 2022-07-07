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


namespace theme_university\local;

defined('MOODLE_INTERNAL') || die();

use core_component;
use DOMDocument;
use Phar;
use PharData;
use stdClass,
    html_writer,
    moodle_url,
    context_course,
    cm_info;
use theme_config;

/**
 * Auto-loadable utility functions.
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class util {

    /**
     * Generic validate the user.
     */
    public static function validate_user($user = null) {
        // Check user object exists and it's not the guest user.
        if (isset($user->id) && $user->id !== 0) {
            return true;
        }
        // We could also check for deleted and suspended users here...

        return false;
    }

    /**
     * Auto include all the other methods included.
     *
     * @param array $includeoptions
     */
    public static function include_methods($class, $includeoptions = []) {
        foreach ($includeoptions as $property => $value) {
            if (property_exists($class, (string)$property)) {
                $class->{$property} = $value;
                // Class methods are prefixed with `include_` e.g. include_user.
                $includemethodname = 'include_' . $property;
                if (method_exists($class, $includemethodname)) {
                    $class->{$includemethodname}($value);
                }
            }
        }
    }

    /**
     * Check if we are using Totara or not.
     * @return bool
     */
    public static function is_totara() {
        global $CFG;

        $totara = false;
        if (file_exists($CFG->dirroot.'/totara/core/totara.php') && function_exists('totara_major_version')) {
            $totara = true;
        }

        return $totara;
    }

    /**
     * Check if we are using Moodle Workplace or not.
     * @return bool
     */
    public static function is_workplace() {
        global $CFG;

        $workplace = false;
        if (file_exists($CFG->dirroot.'/theme/workplace/classes/workplace.php')) {
            $workplace = true;
        }

        return $workplace;
    }

    /**
     * Return friendly relative time (e.g. "1 min ago", "1 year ago") in a <time> tag
     * @return string
     */
    public static function relative_time($timeinpast, $relativeto = null) {
        if ($relativeto === null) {
            $relativeto = time();
        }
        $secondsago = $relativeto - $timeinpast;
        $secondsago = self::simpler_time($secondsago);

        $relativetext = format_time($secondsago);
        if ($secondsago != 0) {
            $relativetext = get_string('ago', 'message', $relativetext);
        }
        $datetime = date(\DateTime::W3C, $timeinpast);
        return html_writer::tag('time', $relativetext, array(
            'is' => 'relative-time',
            'datetime' => $datetime)
        );
    }

    /**
     * Reduce the precision of the time e.g. 1 min 10 secs ago -> 1 min ago
     * @return int
     */
    public static function simpler_time($seconds) {
        if ($seconds > 59) {
            return intval(round($seconds / 60)) * 60;
        } else {
            return $seconds;
        }
    }

    public static function get_course_image_url($course) {
        global $OUTPUT, $DB;

        if (is_object($course)) {
            if (empty($course->cacherev)) {
                $course = $DB->get_record('course', ['id' => $course->id]);
            }
        } else {
            $course = $DB->get_record('course', ['id' => $course]);
        }

        if (!$course) {
            return $OUTPUT->image_url('course_defaultimage', 'moodle');
        }
        $context = context_course::instance($course->id, IGNORE_MISSING);
        if (!$context) {
            return $OUTPUT->image_url('course_defaultimage', 'moodle');
        }

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
                    return $url;
                }
            }
        }

        return $OUTPUT->image_url('course_defaultimage', 'moodle');
    }


    /**
     * @param $courseid
     * @return bool|\stored_file
     * @throws \coding_exception
     */
    public static function get_course_firstimage($courseid) {
        $fs      = get_file_storage();
        $context = \context_course::instance($courseid);
        $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);

        if (count($files) > 0) {
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    return $file;
                }
            }
        }

        return false;
    }

    /**
     * Extract first image from html
     *
     * @param string $html (must be well formed)
     * @return array | bool (false)
     */
    public static function extract_first_image($html) {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true); // Required for HTML5.
        $doc->loadHTML($html);
        libxml_clear_errors(); // Required for HTML5.
        $imagetags = $doc->getElementsByTagName('img');
        if ($imagetags->item(0)) {
            $src = $imagetags->item(0)->getAttribute('src');
            $alt = $imagetags->item(0)->getAttribute('alt');
            return array('src' => $src, 'alt' => $alt);
        } else {
            return false;
        }
    }

    /**
     * Get supported cover image types.
     * @return array
     */
    public static function supported_coverimage_types() {
        global $CFG;
        $extsstr = strtolower($CFG->courseoverviewfilesext);

        // Supported file extensions.
        $extensions = explode(',', str_replace('.', '', $extsstr));

        array_walk($extensions, function($s) {
                trim($s);
        });

        // Filter out any extensions that might be in the config but not image extensions.
        $imgextensions = ['jpg', 'png', 'gif', 'svg'];
        return array_intersect ($extensions, $imgextensions);
    }
    /**
     * Get course by shortname.
     * @param string $shortname
     * @return mixed
     */
    public static function coursebyshortname($shortname, $fields = '*') {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => $shortname], $fields, MUST_EXIST);
        return $course;
    }

    /**
     * Calls create_file_from_pathname in lib/file_storage after checking if file
     * exists. Not sure why this isn't at least an option in the core function.
     *
     * @param stdClass|array $filerecord object or array describing file
     * @param $filepath
     * @return stored_file
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public static function create_or_replace_file_from_pathname($filerecord, $filepath) {
        // Double cast copied from core method.
        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        $fs = get_file_storage();
        if ($fs->file_exists($filerecord->contextid, $filerecord->component,
                $filerecord->filearea, 0, $filerecord->filepath, $filerecord->filename)) {

            $fs->delete_area_files($filerecord->contextid, $filerecord->component, $filerecord->filearea, 0);
        }

        return $fs->create_file_from_pathname($filerecord, $filepath);
    }

    /**
     * Import theme settings from an uploaded .tar.gz file compatible with
     * moosh theme-settings-export file format.
     *
     * @param string $themename
     * @return stored_file
     */
    public static function import_theme_settings($themename) {
        global $DB;

        $themecomponent = 'theme_' . $themename;
        $theme = \theme_config::load($themename);

        $filename = !empty($theme->settings->importsettings) ? $theme->settings->importsettings : null;

        $fs = get_file_storage();
        $context = \context_system::instance();
        if (!$filename) {
            return;
        }
        $settingstargz = $fs->get_file(
            $context->id,
            $themecomponent,
            'importsettings',
            0,
            '/',
            $filename
        );
        if (!$settingstargz) {
            return;
        }
        $tmp = make_request_directory();
        $packer = get_file_packer('application/x-gzip');
        $settingstargz->extract_to_pathname($packer, $tmp);

        $targetfileglob = "{$tmp}/*_settings.xml";
        $matches = glob($targetfileglob);
        if (count($matches) != 1) {
            \core\notification::error(get_string('importsettingsinvalidfile', 'theme_university'));
            return;
        }
        $settingsfilexml = reset($matches);
        $dom = new \DOMDocument();
        $dom->load($settingsfilexml);
        $themedom = $dom->documentElement;
        $xmlthemename = $themedom->getAttribute('name');
        $xmlthemecomponent = $themedom->getAttribute('component');
        if ($xmlthemename !== $themename
                || $xmlthemecomponent !== $themecomponent) {
            \core\notification::warning(get_string('importsettingsmismatch', 'theme_university', $xmlthemename));
        }

        $settingsdom = $themedom->getElementsByTagName('setting');

        $settingcount = 0;
        $filecount = 0;
        if (!$settingsdom->length) {
            \core\notification::warning(get_string('nosettingstoimport', 'theme_university'));
        }
        foreach ($settingsdom as $setting) {
            $settingname = $setting->getAttribute('name');
            $settingvalue = $setting->nodeValue;

            if ($settingname == 'version') {
                continue;
            }

            if ($setting->hasAttribute('file')) {
                $filename = ltrim($settingvalue, '/');
                $fileinfo = array(
                        'contextid' => $context->id,
                        'component' => $themecomponent,
                        'filearea' => $settingname,
                        'itemid' => 0,
                        'filepath' => '/',
                        'filename' => $filename
                );

                $filepath = $tmp . '/' . $setting->getAttribute('file');
                util::create_or_replace_file_from_pathname($fileinfo, $filepath);
                $filecount++;
            }
            // make sure it is a real change
            $oldvalue = get_config($themecomponent, $settingname);
            $oldvalue = ($oldvalue === false) ? null : $oldvalue; // normalise
            $value = is_null($settingvalue) ? null : (string)$settingvalue;

            if ($oldvalue === $value) {
                // Don't set it if it's the same as alread stored.
                continue;
            }

            set_config($settingname, $settingvalue, $themecomponent);
            $settingcount++;

            add_to_config_log($settingname, $oldvalue, $value, $themecomponent);
        }

        $fs->delete_area_files(
            $context->id,
            $themecomponent,
            'importsettings'
        );

        $info = new stdClass();
        $info->settingcount = $settingcount;
        $info->filecount = $filecount;
        \core\notification::success(get_string('settingsinfo', 'theme_university', $info));
    }

    /**
     * Get detailed information about a course accessed by this user.
     * Useful function to receive detailed course info.
     *
     * @param      $course
     * @param null $userid
     *
     * @return \stdClass
     */
    public static function get_course_details($course, $userid = null, $limit = 600) {
        global $CFG, $DB, $PAGE;

        // Validate the data first.
        if (!$course) {
            return false;
        }
        require_once($CFG->dirroot.'/course/renderer.php');

        // Allow for legacy use of the `get_course_details()` function.
        if (class_exists('core_course_list_element')) {
            $courseinstance = new \core_course_list_element($course);
            $category = \core_course_category::get($courseinstance->category);
        } else if (file_exists($CFG->dirroot . '/lib/coursecatlib.php')) {
            // Allow for legacy use of the `get_course_details()` function.
            include_once($CFG->dirroot . '/lib/coursecatlib.php');
            $courseinstance = new \course_in_list($course);
            $category = \coursecat::get($courseinstance->category);
        }

        if (empty((array) $courseinstance)) {
            return false;
        }


        $courseinfo = new \stdClass();
        $courseinfo->id = $courseinstance->id;
        $courseinfo->name = $courseinstance->fullname;
        $courseinfo->category = format_string($category->name);
        $courselink = new \moodle_url('/course/view.php', array('id'=>$courseinstance->id));
        $courseinfo->url = $courselink->out();
        $chelper = new \coursecat_helper();

        $shortsummary = self::truncate_html($chelper->get_course_formatted_summary($courseinstance), $limit);

        $courseinfo->summary = format_text($shortsummary, $courseinstance->summaryformat);
        $activityprogress = self::completionbar($course);
        if (!empty($activityprogress)) {
            $courseinfo->hascompletion = $activityprogress['hascompletion'];
            $courseinfo->progressbar = $activityprogress['progressbar'];
            $courseinfo->activitytotal = $activityprogress['total'];
            $courseinfo->activitycomplete = $activityprogress['complete'];
            $courseinfo->activitypercentage = $activityprogress['percentage'];
        }
        $courseinfo->image = self::get_course_image_url($courseinstance->id);
        // Get the last accessed time if it exists.
        if (!empty($userid)) {
            $courseinfo->lastaccess = '';
            $sql = "
            SELECT timeaccess
            FROM {user_lastaccess}
            WHERE userid = ? AND courseid = ?
            ORDER BY timeaccess DESC
            LIMIT 1";
            $lastaccess = $DB->get_record_sql($sql, [$userid, $course->id]);
            if (isset($lastaccess->timeaccess)) {
                $courseinfo->lastaccess = date('d/m/y', $lastaccess->timeaccess);
                $courseinfo->lastaccessed = get_string('lastaccessed', 'theme_university', $courseinfo->lastaccess);
            }
        }

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
            $courseinfo->teachers = $teachers;

            if (count($contacts) > 1) {
                $numberleft = count($contacts);
                $names = array();
                foreach ($contacts as $coursecontact) {
                    $names[] = $coursecontact['user']->firstname.' '.$coursecontact['user']->lastname;
                }
                $names = implode("<br />", $names);
                $courseinfo->teachersextra = \html_writer::tag('div', '+'.$numberleft, ['class'=>'teachericon placeholder d-inline-block', 'data-toggle'=>"tooltip", 'data-placement'=>"bottom", 'data-html'=>"true", 'title'=>$names]);
            }
        }

        return $courseinfo;
    }

    /**
     * @param $course
     *
     * @return array
     */
    public static function completionbar($course) {
        global $CFG, $USER;
        require_once($CFG->libdir.'/completionlib.php');

        $activity = [
            'hascompletion' => false,
            'total'         => 0,
            'complete'      => 0,
            'percentage'    => 0,
            'progressbar'   => ''
        ];
        $info = new \completion_info($course);
        $completions = $info->get_completions($USER->id);

        // Check if this course has any criteria.
        if (empty($completions)) {
            return $activity;
        }

        // Check this user is enroled.
        if ($info->is_tracked_user($USER->id)) {
            // For aggregating activity completion.
            $activities = array();
            $activities_complete = 0;

            // Loop through course criteria.
            foreach ($completions as $completion) {
                $criteria = $completion->get_criteria();
                $complete = $completion->is_complete();

                if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                    $activities[$criteria->moduleinstance] = $complete;
                    if ($complete) {
                        $activities_complete++;
                    }
                }
            }
            // Aggregate activities.
            if (!empty($activities)) {
                $per = floor(100 * ($activities_complete / count($activities)));
                $bar = \html_writer::tag('div', '', array('class'=>'progress-bar-animated progress-bar', 'aria-valuemin'=>0, 'aria-valuemax'=>100, 'aria-valuenow'=>$per, 'style'=>"width: $per%"));
                $progressbar = \html_writer::tag('div', $bar, array('class'=>'progress'));

                $activity['hascompletion'] = true;
                $activity['total'] = count($activities);
                $activity['complete'] = $activities_complete;
                $activity['percentage'] = $per;
                $activity['progressbar'] = $progressbar;
            }
        }
        return $activity;
    }

    /**
     * Truncate html.
     */
    public static function truncate_html($text, $length = 150, $ending = '...', $exact = false, $considerHtml = true) {
        if (!is_string($text)) {
            trigger_error('Function \'truncate_html\' expects argument 1 to be an string', E_USER_ERROR);
            return false;
        }
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                    // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                        unset($open_tags[$pos]);
                        }
                    // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length+$content_length> $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1]+1-$entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if($total_length>= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }

    /**
     * Export theme settings to a .tar.gz file compatible with
     * moosh theme-settings-export file format.
     *
     * @param string $themename
     * @return stored_file
     */
    public static function export_theme_settings($themename, $time = '') {
        global $CFG;

        // Increase the server timeout to handle the creation and sending of large zip files.
        \core_php_time_limit::raise();

        $availablethemes = \core_plugin_manager::instance()->get_plugins_of_type('theme');

        if (!empty($availablethemes)) {
            $availablethemenames = array_keys($availablethemes);
        }

        if (in_array($themename, $availablethemenames)) {
            $themetoexport = $themename;
        }
        if (!$themetoexport) {
            die();
        }

        // Load theme settings
        $themecomponent = $availablethemes[$themetoexport]->component;
        $themeconfig = theme_config::load($themetoexport);

        $themesettings = $themeconfig->settings;
        $outputdir = make_request_directory();

        if (!empty($themesettings)) {
            // Export code taken from Moosh for compatability with that tool.

            $time = !empty($time) ? $time : time();

            $tarname = "{$themetoexport}_settings_{$time}.tar";

            $phar = new PharData($outputdir.'/'.$tarname);
            $dom = new DOMDocument('1.0', 'utf-8');
            $root = $dom->createElement('theme');

            $root->setAttribute('name', $themetoexport);
            $root->setAttribute('component', $themecomponent);
            $root->setAttribute('version', $themesettings->version);
            $dom->appendChild($root);

            foreach ($themesettings as $settingname => $settingvalue) {

                if ($settingname == 'version') {
                    continue;
                }
                $element = $dom->createElement('setting');
                $element->appendChild($dom->createTextNode($settingvalue));
                $element->setAttribute('name', $settingname);
                if ($settingvalue && $settingvalue[0] == '/' && strpos($settingvalue, '.') !== false) {
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files(\context_system::instance()->id, $themecomponent, $settingname, $settingvalue)) {
                        foreach ($files as $f) {
                            if (!$f->is_directory()) {
                                $fh = $f->get_content_file_handle();
                                $meta = stream_get_meta_data($fh);
                                $uriparts = explode('/', $meta['uri']);
                                $hash = array_pop($uriparts);
                                $phar->addFile($meta['uri'], $hash);
                                $element->setAttribute('file', $hash);
                                $root->appendChild($element);
                            }
                        }
                    }
                } else {
                    $root->appendChild($element);
                }

            }

            $xmlfilename = "{$themetoexport}_settings.xml";
            $outputxml = "{$outputdir}/{$xmlfilename}";

            if ($dom->save($outputxml)) {
                $phar->addFile($outputxml, $xmlfilename);
                $phar->compress(Phar::GZ);
                $zipname = $tarname . '.gz';
                require_once($CFG->libdir.'/filelib.php');
                send_temp_file($outputdir . '/' . $zipname, $zipname);
            }
        }
    }




}
