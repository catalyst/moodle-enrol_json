<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     enrol_json
 * @category    string
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Json enrolment';
$string['pluginname_desc'] = 'The JSON enrolment method allows you to create users and enrolments based on an externally hosted JSON file..';
$string['apipassword'] = 'API password';
$string['apiusername'] = 'API username';
$string['userapiurl'] = 'User API url';
$string['userapiurl_description'] = 'User API url - eg: https://openapi.xjtlu.edu.cn:8000/esb-mdm/v1/students/information/to-learningmall';
$string['enrolmentapiurl'] = 'Enrolment API url';
$string['enrolmentapiurl_description'] = 'Enrolment API url - eg: https://openapi.xjtlu.edu.cn:8000/esb-app/v1/timetable/course-enrolment/to-learningmall';
$string['usersyncsettings'] = 'User sync settings';
$string['enrolsyncsettings'] = 'Enrolment sync settings';
$string['usersync'] = 'Sync user information';
$string['usersync_desc'] = 'If enabled this will sync user profile fields and create new users';
$string['newuserauth'] = 'New user auth';
$string['newuserauth_desc'] = 'When creating a user, which authentication method should be associated with the new user';
$string['update_onsync'] = 'On every sync';
$string['user_data_mapping'] = 'User data mapping';
$string['privacy:metadata'] = 'The JSON enrolment plugin does not store any personal data.';
$string['syncenrolmentstask'] = 'Sync enrolments';
$string['syncuserstask'] = 'Sync users';
$string['failedapicall'] = 'Failed to request api url';
$string['json:config'] = 'Configure json plugin';
$string['remotegroupfield'] = 'Remote group field';
$string['localgroupfield'] = 'Local group field';