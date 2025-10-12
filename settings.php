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
 * Plugin administration pages are defined here.
 *
 * @package     enrol_json
 * @category    admin
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
        require_once($CFG->dirroot . '/enrol/json/lib.php');
        // General settings.
        $settings->add(new admin_setting_heading('enrol_json_settings', '',
            get_string('pluginname_desc', 'enrol_json')));

        $settings->add(new admin_setting_configtext('enrol_json/apiuser',
            get_string('apiusername', 'enrol_json'), '', ''));

        $settings->add(new admin_setting_configpasswordunmask('enrol_json/apipass',
            get_string('apipassword', 'enrol_json'), '', ''));

        $settings->add(new admin_setting_configtext('enrol_json/userapiurl',
            get_string('userapiurl', 'enrol_json'),
            get_string('userapiurl_description', 'enrol_json'), ''));

        $settings->add(new admin_setting_configtext('enrol_json/enrolmentapiurl',
            get_string('enrolmentapiurl', 'enrol_json'),
            get_string('enrolmentapiurl_description', 'enrol_json'), ''));

        $options = array('id' => 'id', 'idnumber' => 'idnumber', 'email' => 'email', 'username' => 'username');
        $settings->add(new admin_setting_configselect('enrol_json/localuserfield',
            get_string('localuserfield', 'enrol_database'), '', 'idnumber', $options));

        $settings->add(new admin_setting_configtext('enrol_json/remoteuserfield',
            get_string('remoteuserfield', 'enrol_database'), get_string('remoteuserfield_desc', 'enrol_database'), ''));

        // Label and Sync Options.
        $settings->add(new admin_setting_heading('enrol_json/usersyncheader', new lang_string('usersyncsettings', 'enrol_json'), ''));

        $settings->add(new admin_setting_configcheckbox('enrol_json/usersync',
            get_string('usersync', 'enrol_json'), get_string('usersync_desc', 'enrol_json'), 1));

        $authsenabled = get_enabled_auth_plugins();
        $choices = array();
        foreach ($authsenabled as $auth) {
            $authplugin = get_auth_plugin($auth);
            // Get the auth title (from core or own auth lang files)
            $authtitle = $authplugin->get_title();
            $choices[$auth] = $authtitle;
        }
        $settings->add(new admin_setting_configselect('enrol_json/newuserauth',
            get_string('newuserauth', 'enrol_json'), get_string('newuserauth_desc', 'enrol_json'), '', $choices));

        // Sync Options.
        $deleteopt = array();
        $deleteopt[AUTH_REMOVEUSER_KEEP] = get_string('auth_remove_keep', 'auth');
        $deleteopt[AUTH_REMOVEUSER_SUSPEND] = get_string('auth_remove_suspend', 'auth');
        $deleteopt[AUTH_REMOVEUSER_FULLDELETE] = get_string('auth_remove_delete', 'auth');
        $deleteopt[AUTH_REMOVEUSER_SUSPEND_UNENROL] = get_string('auth_remove_suspend_unenrol', 'enrol_json');

        $settings->add(new admin_setting_configselect('enrol_json/removeuser',
            new lang_string('auth_remove_user_key', 'auth'),
            new lang_string('auth_remove_user', 'auth'), AUTH_REMOVEUSER_KEEP, $deleteopt));


        $authplugin = get_auth_plugin('manual');
        enrol_json_display_auth_options($settings, 'enrol_json',
            $authplugin->userfields, '');

        // Label and Sync Options.
        $settings->add(new admin_setting_heading('enrol_json/enrolsyncheader', new lang_string('enrolsyncsettings', 'enrol_json'), ''));

        $options = array('id'=>'id', 'idnumber'=>'idnumber', 'shortname'=>'shortname');
        $settings->add(new admin_setting_configselect('enrol_json/localcoursefield',
            get_string('localcoursefield', 'enrol_database'), '', 'idnumber', $options));

        $settings->add(new admin_setting_configtext('enrol_json/remotecoursefield',
            get_string('remotecoursefield', 'enrol_database'), get_string('remotecoursefield_desc', 'enrol_database'), ''));

        $options = array('name' => 'name', 'idnumber' => 'idnumber');
        $settings->add(new admin_setting_configselect('enrol_json/localgroupfield',
            get_string('localgroupfield', 'enrol_json'), '', 'name', $options));

        $settings->add(new admin_setting_configtext('enrol_json/remotegroupfield',
            get_string('remotegroupfield', 'enrol_json'), '', ''));

        $options = array('id'=>'id', 'shortname'=>'shortname');
        $settings->add(new admin_setting_configselect('enrol_json/localrolefield',
            get_string('localrolefield', 'enrol_database'), '', 'shortname', $options));

        $settings->add(new admin_setting_configtext('enrol_json/remoterolefield',
            get_string('remoterolefield', 'enrol_database'), get_string('remoterolefield_desc', 'enrol_database'), ''));

        if (!during_initial_install()) {
            $options = get_default_enrol_roles(context_system::instance());
            $student = get_archetype_roles('student');
            $student = reset($student);
            $settings->add(new admin_setting_configselect('enrol_json/defaultrole',
                get_string('defaultrole', 'enrol_database'),
                get_string('defaultrole_desc', 'enrol_database'),
                $student->id ?? null,
                $options));
        }

        $settings->add(new admin_setting_configcheckbox('enrol_json/ignorehiddencourses', get_string('ignorehiddencourses', 'enrol_database'), get_string('ignorehiddencourses_desc', 'enrol_database'), 0));

        $options = array(ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
            ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
            ENROL_EXT_REMOVED_SUSPEND        => get_string('extremovedsuspend', 'enrol'),
            ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'));
        $settings->add(new admin_setting_configselect('enrol_json/unenrolaction', get_string('extremovedaction', 'enrol_json'), get_string('extremovedaction_help', 'enrol_json'), ENROL_EXT_REMOVED_UNENROL, $options));

        // Label and Sync Options.
        $settings->add(new admin_setting_heading('enrol_json/rulesyncheader', new lang_string('rulesyncheader', 'enrol_json'), ''));
        $settings->add(new admin_setting_configtextarea('enrol_json/ruleitems', new lang_string('ruleitems', 'enrol_json'),
        new lang_string('ruleitems_desc', 'enrol_json'), '', PARAM_RAW, '50', '10'));

    }
}
