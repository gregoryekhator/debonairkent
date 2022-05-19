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
 * settings.php
 *
 * This is built using the boost template to allow for new theme's using
 * Moodle's new Boost theme engine
 *
 * @package     theme_university
 * @copyright   2015 LMSACE Dev Team, lmsace.com
 * @author      LMSACE Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$settings = null;

if (is_siteadmin()) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettinguniversity', get_string('configtitle', 'theme_university'));
    $ADMIN->add('themes', new admin_category('theme_university', 'university'));

    /* General Settings */
    $temp = new admin_settingpage('theme_university_general', get_string('themegeneralsettings', 'theme_university'));

    // Logo file setting.
    $name = 'theme_university/logo';
    $title = get_string('logo', 'theme_university');
    $description = get_string('logodesc', 'theme_university');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Custom CSS file.
    $name = 'theme_university/customcss';
    $title = get_string('customcss', 'theme_university');
    $description = get_string('customcssdesc', 'theme_university');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Color schemes.
    $colorscheme = get_string('colorscheme', 'theme_university');
    $defaultcolor = get_string('default_color', 'theme_university');
    $colorhdr = get_string('color_schemes_heading', 'theme_university');

    // Theme Color scheme chooser.
    $name = 'theme_university/patternselect';
    $title = get_string('patternselect', 'theme_university');
    $description = get_string('patternselectdesc', 'theme_university');
    $default = 'default';
    $choices = array(
        'default' => get_string( 'lavender', 'theme_university'),
        '1' => get_string('green', 'theme_university'),
        '2' => get_string('blue', 'theme_university'),
        '3' => get_string('warm_red', 'theme_university'),
        '4' => get_string('dark_cyan', 'theme_university')
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Theme Color scheme static content.
    $pimg = array();
    global $CFG;
    $cp = $CFG->wwwroot.'/theme/university/pix/color/';
    $pimg = array(
            $cp.'default.jpg' , $cp.'colorscheme-1.jpg', $cp.'colorscheme-2.jpg',
            $cp.'colorscheme-3.jpg' , $cp.'colorscheme-4.jpg');

    $themepattern = '<ul class="thumbnails theme-color-schemes"><li class=""><div class="thumbnail">
    <img src="'.$pimg[0].'" alt="default" width="100" height="100"/><h6>'.get_string( "default", 'theme_university' ).
    '</h6></div></li><li class=""><div class="thumbnail"><img src="'.$pimg[1].'" alt="pattern1" width="100" height="100"/><h6>'
    .get_string("color_1", 'theme_university').'</h6></div></li><li class=""><div class="thumbnail">
    <img src="'.$pimg[2].'" alt="pattern2" width="100" height="100"/><h6>'.get_string("color_2", 'theme_university').
    '</h6></div></li><li class=""><div class="thumbnail"><img src="'.$pimg[3].'" alt="pattern3" width="100" height="100"/>
    <h6>'.get_string("color_3", 'theme_university').'</h6></div></li><li class=""><div class="thumbnail">
    <img src="'.$pimg[4].'" alt="pattern4" width="100" height="100"/><h6>'.get_string("color_4", 'theme_university').
    '</h6></div></li></ul>';

    $temp->add(new admin_setting_heading('theme_university_patternheading', '', $themepattern));

    // Promoted Courses Start.
    // Promoted Courses Heading.
    $name = 'theme_university_promotedcoursesheading';
    $heading = get_string('promotedcoursesheading', 'theme_university');
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Enable / Disable Promoted Courses.
    $name = 'theme_university/pcourseenable';
    $title = get_string('pcourseenable', 'theme_university');
    $description = '';
    $default = 1;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $temp->add($setting);

    // Promoted courses Block title.
    $name = 'theme_university/promotedtitle';
    $title = get_string('pcourses', 'theme_university').' '.get_string('title', 'theme_university');
    $description = get_string('promotedtitledesc', 'theme_university');
    $default = 'lang:promotedtitledefault';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_university/promotedcourses';
    $title = get_string('pcourses', 'theme_university');
    $description = get_string('pcoursesdesc', 'theme_university');
    $default = array();

    $courses[0] = '';
    $cnt = 0;
    if ($ccc = get_courses('all', 'c.sortorder ASC', 'c.id,c.shortname,c.visible,c.category')) {
        foreach ($ccc as $cc) {
            if ($cc->visible == "0" || $cc->id == "1") {
                continue;
            }
            $cnt++;
            $courses[$cc->id] = $cc->shortname;
            // Set some courses for default option.
            if ($cnt < 8) {
                $default[] = $cc->id;
            }
        }
    }
    $coursedefault = implode(",", $default);
    $setting = new admin_setting_configtextarea($name, $title, $description, $coursedefault);
    $temp->add($setting);
    $settings->add($temp);
    // Promoted Courses End.

    /*Slideshow Settings Start.*/
    $temp = new admin_settingpage('theme_university_slideshow', get_string('slideshowheading', 'theme_university'));
    $temp->add(new admin_setting_heading('theme_university_slideshow', get_string('slideshowheadingsub', 'theme_university'),
    format_text(get_string('slideshowdesc', 'theme_university'), FORMAT_MARKDOWN)));

    // Display Slideshow.
    $name = 'theme_university/toggleslideshow';
    $title = get_string('toggleslideshow', 'theme_university');
    $description = get_string('toggleslideshowdesc', 'theme_university');
    $yes = get_string('yes');
    $no = get_string('no');
    $default = 1;
    $choices = array(1 => $yes , 0 => $no);
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $temp->add($setting);

    // Number of slides.
    $name = 'theme_university/numberofslides';
    $title = get_string('numberofslides', 'theme_university');
    $description = get_string('numberofslides_desc', 'theme_university');
    $default = 1;
    $choices = array(
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => '10',
        11 => '11',
        12 => '12',
    );
    $temp->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    $numberofslides = get_config('theme_university', 'numberofslides');
    for ($i = 1; $i <= $numberofslides; $i++) {

        // This is the descriptor for Slide One.
        $name = 'theme_university/slide' . $i . 'info';
        $heading = get_string('slideno', 'theme_university', array('slide' => $i));
        $information = get_string('slidenodesc', 'theme_university', array('slide' => $i));
        $setting = new admin_setting_heading($name, $heading, $information);
        $temp->add($setting);

        // Slide Image.
        $name = 'theme_university/slide' . $i . 'image';
        $title = get_string('slideimage', 'theme_university');
        $description = get_string('slideimagedesc', 'theme_university');
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'slide' . $i . 'image');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $temp->add($setting);

        // Slide Caption.
        $name = 'theme_university/slide' . $i . 'caption';
        $title = get_string('slidecaption', 'theme_university');
        $description = get_string('slidecaptiondesc', 'theme_university');
        $default = 'lang:slidecaptiondefault';
        $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
        $temp->add($setting);

        // Slider button.
        $name = 'theme_university/slide' . $i . 'urltext';
        $title = get_string('slidebutton', 'theme_university');
        $description = get_string('slidebuttondesc', 'theme_university');
        $default = 'lang:knowmore';
        $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
        $temp->add($setting);

        // Slide button link.
        $name = 'theme_university/slide'.$i.'url';
        $title = get_string('slidebuttonurl', 'theme_university');
        $description = get_string('slidebuttonurldesc', 'theme_university');
        $default = 'http://www.example.com/';
        $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
        $temp->add($setting);
    }
        $settings->add($temp);
    /* Slideshow Settings End*/

    /* Marketing Spots */
    $temp = new admin_settingpage('theme_university_marketingspots', get_string('marketingspotsheading', 'theme_university'));

    /* Marketing Spot 1*/
    $name = 'theme_university_mspot1heading';
    $heading = get_string('marketingspot', 'theme_university').' 1 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Marketing Spot 1 Icon.
    $name = 'theme_university/mspot1icon';
    $title = get_string('marketingspot', 'theme_university').' 1 - '.get_string('icon', 'theme_university');
    $description = get_string('faicondesc', 'theme_university');
    $default = 'globe';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 1 Title.
    $name = 'theme_university/mspot1title';
    $title = get_string('marketingspot', 'theme_university').' 1 - '.get_string('title', 'theme_university');
    $description = get_string('mspottitledesc', 'theme_university');
    $default = 'lang:mspot1titledefault';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 1 Description.
    $name = 'theme_university/mspot1desc';
    $title = get_string('marketingspot', 'theme_university').' 1 - '.get_string('description');
    $description = get_string('mspotdescdesc', 'theme_university');
    $default = 'lang:mspot1descdefault';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_TEXT);
    $temp->add($setting);
    /* Marketing Spot 1*/

    /* Marketing Spot 2*/
    $name = 'theme_university_mspot2heading';
    $heading = get_string('marketingspot', 'theme_university').' 2 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Marketing Spot 2 Icon.
    $name = 'theme_university/mspot2icon';
    $title = get_string('marketingspot', 'theme_university').' 2 - '.get_string('icon', 'theme_university');
    $description = get_string('faicondesc', 'theme_university');
    $default = 'graduation-cap';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 2 Title.
    $name = 'theme_university/mspot2title';
    $title = get_string('marketingspot', 'theme_university').' 2 - '.get_string('title', 'theme_university');
    $description = get_string('mspottitledesc', 'theme_university');
    $default = 'lang:mspot2titledefault';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 2 Description.
    $name = 'theme_university/mspot2desc';
    $title = get_string('marketingspot', 'theme_university').' 2 - '.get_string('description');
    $description = get_string('mspotdescdesc', 'theme_university');
    $default = 'lang:mspot2descdefault';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_TEXT);
    $temp->add($setting);
    /* Marketing Spot 2*/

    /* Marketing Spot 3*/
    $name = 'theme_university_mspot3heading';
    $heading = get_string('marketingspot', 'theme_university').' 3 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Marketing Spot 3 Icon.
    $name = 'theme_university/mspot3icon';
    $title = get_string('marketingspot', 'theme_university').' 3 - '.get_string('icon', 'theme_university');
    $description = get_string('faicondesc', 'theme_university');
    $default = 'bullhorn';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 3 Title.
    $name = 'theme_university/mspot3title';
    $title = get_string('marketingspot', 'theme_university').' 3 - '.get_string('title', 'theme_university');
    $description = get_string('mspottitledesc', 'theme_university');
    $default = 'lang:mspot3titledefault';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 3 Description.
    $name = 'theme_university/mspot3desc';
    $title = get_string('marketingspot', 'theme_university').' 3 - '.get_string('description');
    $description = get_string('mspotdescdesc', 'theme_university');
    $default = 'lang:mspot3descdefault';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_TEXT);
    $temp->add($setting);
    /* Marketing Spot 3*/

    /* Marketing Spot 4*/
    $name = 'theme_university_mspot4heading';
    $heading = get_string('marketingspot', 'theme_university').' 4 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Marketing Spot 4 Icon.
    $name = 'theme_university/mspot4icon';
    $title = get_string('marketingspot', 'theme_university').' 4 - '.get_string('icon', 'theme_university');
    $description = get_string('faicondesc', 'theme_university');
    $default = 'mobile';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 4 Title.
    $name = 'theme_university/mspot4title';
    $title = get_string('marketingspot', 'theme_university').' 4 - '.get_string('title', 'theme_university');
    $description = get_string('mspottitledesc', 'theme_university');
    $default = 'lang:mspot4titledefault';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Marketing Spot 4 Description.
    $name = 'theme_university/mspot4desc';
    $title = get_string('marketingspot', 'theme_university').' 4 - '.get_string('description');
    $description = get_string('mspotdescdesc', 'theme_university');
    $default = 'lang:mspot4descdefault';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_TEXT);
    $temp->add($setting);
    $settings->add($temp);
    /* Marketing Spot 4*/
    /* Marketing Spots End */

    /* Footer Settings start */
    $temp = new admin_settingpage('theme_university_footer', get_string('footerheading', 'theme_university'));

    // Footer Block1.
    $name = 'theme_university_footerblock1heading';
    $heading = get_string('footerblock', 'theme_university').' 1 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Enable / Disable Footer logo.
    $name = 'theme_university/footerblklogo';
    $title = get_string('footerblklogo', 'theme_university');
    $description = '';
    $default = 1;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $temp->add($setting);

    /* Footer Footnote Content */
    $name = 'theme_university/footnote';
    $title = get_string('footnote', 'theme_university');
    $description = get_string('footnotedesc', 'theme_university');
    $default = 'lang:footnotedefault';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $temp->add($setting);
    /* Footer Block1. */

    /* Footer Block2. */
    $name = 'theme_university_footerblock2heading';
    $heading = get_string('footerblock', 'theme_university').' 2 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    $name = 'theme_university/footerbtitle2';
    $title = get_string('footerblock', 'theme_university').' '.get_string('title', 'theme_university').' 2 ';
    $description = get_string('footerbtitle_desc', 'theme_university');
    $default = 'lang:footerbtitle2default';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_university/footerblink2';
    $title = get_string('footerblink', 'theme_university').' 2';
    $description = get_string('footerblink_desc', 'theme_university');
    $default = get_string('footerblink2default', 'theme_university');
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $temp->add($setting);
    /* Footer Block2 */

    /* Footer Block3 */
    $name = 'theme_university_footerblock3heading';
    $heading = get_string('footerblock', 'theme_university').' 3 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    $name = 'theme_university/footerbtitle3';
    $title = get_string('footerblock', 'theme_university').' '.get_string('title', 'theme_university').' 3 ';
    $description = get_string('footerbtitle_desc', 'theme_university');
    $default = 'lang:footerbtitle3default';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    /* Facebook,Pinterest,Twitter,Google+ Settings */
    $name = 'theme_university/fburl';
    $title = get_string('fburl', 'theme_university');
    $description = get_string('fburldesc', 'theme_university');
    $default = get_string('fburl_default', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_university/twurl';
    $title = get_string('twurl', 'theme_university');
    $description = get_string('twurldesc', 'theme_university');
    $default = get_string('twurl_default', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_university/gpurl';
    $title = get_string('gpurl', 'theme_university');
    $description = get_string('gpurldesc', 'theme_university');
    $default = get_string('gpurl_default', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_university/pinurl';
    $title = get_string('pinurl', 'theme_university');
    $description = get_string('pinurldesc', 'theme_university');
    $default = get_string('pinurl_default', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);
    /* Footer Block3. */

    /* Footer Block4. */
    $name = 'theme_university_footerblock4heading';
    $heading = get_string('footerblock', 'theme_university').' 4 ';
    $information = '';
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Fooer Block Title 4.
    $name = 'theme_university/footerbtitle4';
    $title = get_string('footerblock', 'theme_university').' '.get_string('title', 'theme_university').' 4 ';
    $description = get_string('footerbtitle_desc', 'theme_university');
    $default = 'lang:footerbtitle4default';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    /* Address , Phone No ,Email */
    $name = 'theme_university/address';
    $title = get_string('address', 'theme_university');
    $description = '';
    $default = get_string('defaultaddress', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_university/phoneno';
    $title = get_string('phoneno', 'theme_university');
    $description = '';
    $default = get_string('defaultphoneno', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $name = 'theme_university/emailid';
    $title = get_string('emailid', 'theme_university');
    $description = '';
    $default = get_string('defaultemailid', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Copyright.
    $name = 'theme_university/copyright';
    $title = get_string('copyright', 'theme_university');
    $description = '';
    $default = get_string('copyright_default', 'theme_university');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    $settings->add($temp);
    /* Footer Block4 */
    /*  Footer Settings end */

}
