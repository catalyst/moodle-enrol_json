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
 * The enrol plugin json is defined here.
 *
 * @package     enrol_json
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// The base class 'enrol_plugin' can be found at lib/enrollib.php. Override
// methods as necessary.

/**
 * Class enrol_json_plugin.
 */
class enrol_json_plugin extends enrol_plugin {

    /**
     * Does this plugin allow manual enrolments?
     *
     * All plugins allowing this must implement 'enrol/json:enrol' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means user with 'enrol/json:enrol' may enrol others freely, false means nobody may add more enrolments manually.
     */
    public function allow_enrol($instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unenrolment of all users?
     *
     * All plugins allowing this must implement 'enrol/json:unenrol' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means user with 'enrol/json:unenrol' may unenrol others freely, false means nobody may touch user_enrolments.
     */
    public function allow_unenrol($instance) {
        return false;
    }

    /**
     * Does this plugin allow manual changes in user_enrolments table?
     *
     * All plugins allowing this must implement 'enrol/json:manage' capability.
     *
     * @param stdClass $instance Course enrol instance.
     * @return bool True means it is possible to change enrol period and status in user_enrolments table.
     */
    public function allow_manage($instance) {
        return false;
    }

    /**
     * Does this plugin allow manual unenrolment of a specific user?
     *
     * All plugins allowing this must implement 'enrol/json:unenrol' capability.
     *
     * This is useful especially for synchronisation plugins that
     * do suspend instead of full unenrolment.
     *
     * @param stdClass $instance Course enrol instance.
     * @param stdClass $ue Record from user_enrolments table, specifies user.
     * @return bool True means user with 'enrol/json:unenrol' may unenrol this user, false means nobody may touch this user enrolment.
     */
    public function allow_unenrol_user($instance, $ue) {
        return false;
    }

    /**
     * Use the standard interface for adding/editing the form.
     *
     * @since Moodle 3.1.
     * @return bool.
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Adds form elements to add/edit instance form.
     *
     * @since Moodle 3.1.
     * @param object $instance Enrol instance or null if does not exist yet.
     * @param MoodleQuickForm $mform.
     * @param context $context.
     * @return void
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        // Do nothing by default.
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @since Moodle 3.1.
     * @param array $data Array of ("fieldname"=>value) of submitted data.
     * @param array $files Array of uploaded files "element_name"=>tmp_file_path.
     * @param object $instance The instance data loaded from the DB.
     * @param context $context The context of the instance we are editing.
     * @return array Array of "element_name"=>"error_description" if there are errors, empty otherwise.
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        // No errors by default.
        debugging('enrol_plugin::edit_instance_validation() is missing. This plugin has no validation!', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Return whether or not, given the current state, it is possible to add a new instance
     * of this enrolment plugin to the course.
     *
     * @param int $courseid.
     * @return bool.
     */
    public function can_add_instance($courseid) {
        return true;
    }
}

/**
 * Helper function used to print locking for auth plugins on admin pages.
 *
 * @param stdclass $settings Moodle admin settings instance
 * @param string $auth authentication plugin shortname
 * @param array $userfields user profile fields
 * @param string $helptext help text to be displayed at top of form
 * @param boolean $mapremotefields Map fields or lock only.
 * @param boolean $updateremotefields Allow remote updates
 * @param array $customfields list of custom profile fields
 * @since Moodle 3.3
 */
function enrol_json_display_auth_options($settings, $auth, $userfields, $helptext) {
    global $CFG;
    require_once($CFG->dirroot . '/user/profile/lib.php');

    // Introductory explanation and help text.
    $settings->add(new admin_setting_heading('enrol_json/data_mapping', new lang_string('user_data_mapping', 'enrol_json'), $helptext));

    // Generate the list of options.
    $updatelocaloptions = array('oncreate'  => get_string('update_oncreate', 'auth'),
        'onlogin'   => get_string('update_onsync', 'enrol_json'));

    // Generate the list of profile fields to allow updates / lock.
    if (!empty($customfields)) {
        $userfields = array_merge($userfields, $customfields);
        $allcustomfields = profile_get_custom_fields();
        $customfieldname = array_combine(array_column($allcustomfields, 'shortname'), $allcustomfields);
    }

    foreach ($userfields as $field) {
        // Define the fieldname we display to the  user.
        // this includes special handling for some profile fields.
        $fieldname = $field;
        $fieldnametoolong = false;
        if ($fieldname === 'lang') {
            $fieldname = get_string('language');
        } else if (!empty($customfields) && in_array($field, $customfields)) {
            // If custom field then pick name from database.
            $fieldshortname = str_replace('profile_field_', '', $fieldname);
            $fieldname = $customfieldname[$fieldshortname]->name;
            if (core_text::strlen($fieldshortname) > 67) {
                // If custom profile field name is longer than 67 characters we will not be able to store the setting
                // such as 'field_updateremote_profile_field_NOTSOSHORTSHORTNAME' in the database because the character
                // limit for the setting name is 100.
                $fieldnametoolong = true;
            }
        } else {
            $fieldname = get_string($fieldname);
        }

        // Generate the list of fields / mappings.
        if ($fieldnametoolong) {
            // Display a message that the field can not be mapped because it's too long.
            $url = new moodle_url('/user/profile/index.php');
            $a = (object)['fieldname' => s($fieldname), 'shortname' => s($field), 'charlimit' => 67, 'link' => $url->out()];
            $settings->add(new admin_setting_heading('enrol_json/field_not_mapped_'.sha1($field), '',
                get_string('cannotmapfield', 'auth', $a)));
        } else {
            // We are mapping to a remote field here.
            // Mapping.
            $settings->add(new admin_setting_configtext("enrol_json/field_map_{$field}",
                get_string('auth_fieldmapping', 'auth', $fieldname), '', '', PARAM_RAW, 30));

            // Update local.
            $settings->add(new admin_setting_configselect("enrol_json/field_updatelocal_{$field}",
                get_string('auth_updatelocalfield', 'auth', $fieldname), '', 'oncreate', $updatelocaloptions));
        }
    }
}