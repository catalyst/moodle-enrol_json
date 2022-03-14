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

    /**
     * Helper to check that plugin is configured.
     *
     * @return bool
     * @throws dml_exception
     */
    public function is_configured() {
        $this->load_config();
        if (!empty($this->config->apipass) && !empty($this->config->apiuser) && !empty($this->config->userapiurl) &&
            !empty($this->config->remotecoursefield)) {
            return true;
        }
        return false;
    }

    /**
     * Get list of all users from external JSON.
     *
     * @return array
     */
    function get_userlist() {
        $studentapiurl = trim($this->config->userapiurl);
        $apipassword = $this->config->apipass;
        $apiusername = trim($this->config->apiuser);

        $curl = new \curl();
        $options = array(
            'CONNECTTIMEOUT' => 5,
            'CURLOPT_TIMEOUT'=> 300,
            'CURLOPT_USERPWD' => "$apiusername:$apipassword"
        );
        $params = array();

        $response = $curl->get($studentapiurl, $params, $options);
        $externaljson = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging("Failed to get JSON from". $studentapiurl);
            print_error('failedapicall', 'enrol_json');
        }
        $users = [];
        foreach ($externaljson as $row) {
            if (!isset($row->{$this->config->remoteuserfield})){
                debugging(print_r($row, true));
                print_error('invalidjsonnomap', 'gradeexport_aebridge');
            }
            if (isset($users[$row->{$this->config->remoteuserfield}])) {
                // Duplicated user found.
                mtrace("duplicate userid found:".$row->{$this->config->remoteuserfield});
                debugging(print_r($row, true));
            } else {
                $users[$row->{$this->config->remoteuserfield}] = $row;
            }
        }

        return $users;
    }

    /**
     * Get list of all users from external JSON.
     *
     * @return array
     */
    function get_userenrolments() {
        $enrolmentapiurl = trim($this->config->enrolmentapiurl);
        $apipassword = $this->config->apipass;
        $apiusername = trim($this->config->apiuser);

        $curl = new \curl();
        $options = array(
            'CONNECTTIMEOUT' => 5,
            'CURLOPT_TIMEOUT'=> 300,
            'CURLOPT_USERPWD' => "$apiusername:$apipassword"
        );
        $params = array();

        $response = $curl->get($enrolmentapiurl, $params, $options);
        $externaljson = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging("Failed to get JSON from". $enrolmentapiurl);
            print_error('failedapicall', 'enrol_json');
        }

        return $externaljson;
    }

    /**
     * Reads user information from DB and return it in an object.
     *
     * @param string $username username
     * @return array
     */
    function get_userinfo_asobj($externaluser) {
        $user = new stdClass();
        $keys = array_keys(get_object_vars($this->config));
        $updatekeys = [];
        foreach ($keys as $key) {
            if (preg_match('/^field_map_(.+)$/', $key, $match)) {
                $field = $match[1];

                if (!empty($this->config->{'field_map_'.$field})) {
                    $user->$field = $externaluser->{$this->config->{'field_map_'.$field}};
                }

            }
        }
        return $user;
    }

    /**
     * Reads any other information for a user from external database,
     * then returns it in an array.
     *
     * @param string $username
     * @return array
     */
    function get_userinfo($username) {

        // Array to map local fieldnames we want, to external fieldnames.
        $selectfields = $this->db_attributes();

        $result = array();
        // If at least one field is mapped from external db, get that mapped data.
        if ($selectfields) {
            $select = array();
            $fieldcount = 0;
            foreach ($selectfields as $localname=>$externalname) {
                // Without aliasing, multiple occurrences of the same external
                // name can coalesce in only occurrence in the result.
                $select[] = "$externalname AS F".$fieldcount;
                $fieldcount++;
            }
            $select = implode(', ', $select);
            $sql = "SELECT $select
                      FROM {$this->config->table}
                     WHERE {$this->config->fielduser} = '".$this->ext_addslashes($extusername)."'";

            if ($rs = $authdb->Execute($sql)) {
                if (!$rs->EOF) {
                    $fields = $rs->FetchRow();
                    // Convert the associative array to an array of its values so we don't have to worry about the case of its keys.
                    $fields = array_values($fields);
                    foreach (array_keys($selectfields) as $index => $localname) {
                        $value = $fields[$index];
                        $result[$localname] = core_text::convert($value, $this->config->extencoding, 'utf-8');
                    }
                }
                $rs->Close();
            }
        }
        $authdb->Close();
        return $result;
    }

    /**
     * Synchronizes user from external json to moodle user table.
     *
     * Sync should be done by using idnumber attribute, not username.
     * You need to pass firstsync parameter to function to fill in
     * idnumbers if they don't exists in moodle user table.
     *
     * Syncing users removes (disables) users that don't exists anymore in external source.
     * Creates new users.
     *
     * @param progress_trace $trace
     * @param bool $do_updates  Optional: set to true to force an update of existing accounts
     * @return int 0 means success, 1 means failure
     */
    function sync_users(progress_trace $trace, $do_updates=false) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->libdir.'/filelib.php');

        // List external users.
        $userlist = $this->get_userlist();
        $userkeys = array_keys($userlist); // list of user keys.

        // Delete obsolete internal users.
        if (!empty($this->config->removeuser)) {

            $suspendselect = "";
            if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                $suspendselect = "AND u.suspended = 0";
            }

            // Find obsolete users.
            if (count($userlist)) {
                $removeusers = array();
                $params['authtype'] = $this->config->newuserauth;
                $sql = "SELECT u.id, u.username, u.idnumber, u.email
                          FROM {user} u
                         WHERE u.auth=:authtype
                           AND u.deleted=0
                           AND u.mnethostid=:mnethostid
                           $suspendselect";
                $params['mnethostid'] = $CFG->mnet_localhost_id;
                $internalusersrs = $DB->get_recordset_sql($sql, $params);

                $usernamelist = array_flip($userlist);
                foreach ($internalusersrs as $internaluser) {
                    if (!array_key_exists($internaluser->{$this->config->localuserfield}, $usernamelist)) {
                        $removeusers[] = $internaluser;
                    }
                }
                $internalusersrs->close();
            }

            if (!empty($removeusers)) {
                $trace->output(get_string('auth_dbuserstoremove', 'auth_db', count($removeusers)));

                foreach ($removeusers as $user) {
                    if ($this->config->removeuser == AUTH_REMOVEUSER_FULLDELETE) {
                        delete_user($user);
                        $trace->output(get_string('auth_dbdeleteuser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)), 1);
                    } else if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                        $updateuser = new stdClass();
                        $updateuser->id   = $user->id;
                        $updateuser->suspended = 1;
                        user_update_user($updateuser, false);
                        $trace->output(get_string('auth_dbsuspenduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)), 1);
                    }
                }
            }
            unset($removeusers);
        }

        if (!count($userlist)) {
            // Exit right here, nothing else to do.
            $trace->output("No users found in external source! - DANGER!");
            $trace->finished();
            return 0;
        }

        // Update existing accounts.
        if ($do_updates) {
            $trace->output("Update existing accounts");
            // Narrow down what fields we need to update.
            $all_keys = array_keys(get_object_vars($this->config));
            $updatekeys = array();
            foreach ($all_keys as $key) {
                if (preg_match('/^field_updatelocal_(.+)$/',$key, $match)) {
                    if ($this->config->{$key} === 'onlogin') {
                        array_push($updatekeys, $match[1]); // The actual key name.
                    }
                }
            }
            unset($all_keys); unset($key);

            // Only go ahead if we actually have fields to update locally.
            if (!empty($updatekeys)) {
                $update_users = array();
                // All the drivers can cope with chunks of 10,000. See line 4491 of lib/dml/tests/dml_est.php
                $userlistchunks = array_chunk($userkeys , 10000);
                foreach($userlistchunks as $userlistchunk) {
                    list($in_sql, $params) = $DB->get_in_or_equal($userlistchunk, SQL_PARAMS_NAMED, 'u', true);
                    $params['mnethostid'] = $CFG->mnet_localhost_id;
                    $sql = "SELECT u.id, u.username, u.suspended, u.idnumber, u.email
                          FROM {user} u
                         WHERE u.deleted = 0 AND u.mnethostid = :mnethostid AND u.{$this->config->localuserfield} {$in_sql}";
                    $update_users = $update_users + $DB->get_records_sql($sql, $params);
                }

                if ($update_users) {
                    $trace->output("Users to check for updates: ".count($update_users));
                    foreach ($update_users as $user) {
                        if ($this->update_user_record($user->username, $updatekeys, $userlist[$user->{$this->config->localuserfield}], false, (bool) $user->suspended)) {
                            $trace->output(get_string('auth_dbupdatinguser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)), 1);
                        }
                    }
                    unset($update_users);
                }
            }
        }


        // Create missing accounts.
        // NOTE: this is very memory intensive and generally inefficient.
        $suspendselect = "";
        if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
            $suspendselect = "AND u.suspended = 0";
        }
        $localuserfield = clean_param($this->config->localuserfield, PARAM_ALPHANUMEXT);

        $sql = "SELECT u.id, u.username, u.idnumber, u.email
                  FROM {user} u
                 WHERE u.deleted='0' AND $localuserfield <> '' AND mnethostid=:mnethostid $suspendselect";

        $users = $DB->get_records_sql($sql, array('mnethostid' => $CFG->mnet_localhost_id));

        // Simplify down to same key as externaljson.
        $usernames = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                array_push($usernames, $user->{$this->config->localuserfield});
            }
            unset($users);
        }
        $add_users = array_diff($userkeys, $usernames);
        unset($usernames);

        if (!empty($add_users)) {
            $trace->output(get_string('auth_dbuserstoadd','auth_db',count($add_users)));
            // Do not use transactions around this foreach, we want to skip problematic users, not revert everything.
            foreach($add_users as $userkey) {
                if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                    if ($olduser = $DB->get_record('user', array($localuserfield => $userkey, 'deleted' => 0, 'suspended' => 1,
                        'mnethostid' => $CFG->mnet_localhost_id))) {
                        $updateuser = new stdClass();
                        $updateuser->id = $olduser->id;
                        $updateuser->suspended = 0;
                        user_update_user($updateuser);
                        $trace->output(get_string('auth_dbreviveduser', 'auth_db', array('name' => $userkey,
                            'id' => $olduser->id)), 1);
                        continue;
                    }
                }

                // Do not try to undelete users here, instead select suspending if you ever expect users will reappear.

                // Prep a few params.
                $user = $this->get_userinfo_asobj($userlist[$userkey]);
                $user->confirmed  = 1;
                $user->auth       = $this->config->newuserauth;
                $user->mnethostid = $CFG->mnet_localhost_id;

                if ($collision = $DB->get_record_select('user', "username = :username AND mnethostid = :mnethostid AND auth <> :auth", array('username'=>$user->username, 'mnethostid'=>$CFG->mnet_localhost_id, 'auth'=>$this->newuserauth), 'id,username,auth')) {
                    $trace->output(get_string('auth_dbinsertuserduplicate', 'auth_db', array('username'=>$user->username, 'auth'=>$collision->auth)), 1);
                    continue;
                }

                try {
                    $id = user_create_user($user, false, false); // It is truly a new user.
                    $trace->output(get_string('auth_dbinsertuser', 'auth_db', array('name'=>$user->username, 'id'=>$id)), 1);
                } catch (moodle_exception $e) {
                    $trace->output(get_string('auth_dbinsertusererror', 'auth_db', $user->username), 1);
                    continue;
                }

                // Save custom profile fields here.
                require_once($CFG->dirroot . '/user/profile/lib.php');
                $user->id = $id;
                profile_save_data($user);

                // Make sure user context is present.
                context_user::instance($id);

                \core\event\user_created::create_from_userid($id)->trigger();
            }
            unset($add_users);
        }
        $trace->finished();
        return 0;
    }

    /**
     * Update a local user record from an external source - copied from authlib.
     * This is a lighter version of the one in moodlelib -- won't do
     * expensive ops such as enrolment.
     *
     * @param string $username username
     * @param array $updatekeys fields to update, false updates all fields.
     * @param stdClass $externalrecord - external record for this user.
     * @param bool $triggerevent set false if user_updated event should not be triggered.
     *             This will not affect user_password_updated event triggering.
     * @param bool $suspenduser Should the user be suspended?
     * @return stdClass|bool updated user record or false if there is no new info to update.
     */
    protected function update_user_record($username, $updatekeys = false, $externalrecord, $triggerevent = false, $suspenduser = false) {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/user/profile/lib.php');

        // Just in case check text case.
        $username = trim(core_text::strtolower($username));

        // Get the current user record.
        $user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id));
        if (empty($user)) { // Trouble.
            error_log($this->errorlogtag . get_string('auth_usernotexist', 'auth', $username));
            print_error('auth_usernotexist', 'auth', '', $username);
            die;
        }

        // Protect the userid from being overwritten.
        $userid = $user->id;

        $needsupdate = false;

        if ($externalrecord) {
            $newinfo = truncate_userinfo((array)$externalrecord);
            if (empty($updatekeys)) { // All keys? this does not support removing values.
                $updatekeys = array_keys($newinfo);
            }

            if (!empty($updatekeys)) {
                $newuser = new stdClass();
                $newuser->id = $userid;
                // The cast to int is a workaround for MDL-53959.
                $newuser->suspended = (int) $suspenduser;
                // Load all custom fields.
                $profilefields = (array) profile_user_record($user->id, false);
                $newprofilefields = [];

                foreach ($updatekeys as $key) {
                    if (!empty($this->config->{'field_map_' . $key}) && isset($externalrecord->{$this->config->{'field_map_' . $key}})) {
                        $value = $externalrecord->{$this->config->{'field_map_' . $key}};
                    } else if (isset($newinfo[$key])) {
                        $value = $newinfo[$key];
                    } else {
                        $value = '';
                    }

                    if (!empty($this->config->{'field_updatelocal_' . $key})) {
                        if (preg_match('/^profile_field_(.*)$/', $key, $match)) {
                            // Custom field.
                            $field = $match[1];
                            $currentvalue = isset($profilefields[$field]) ? $profilefields[$field] : null;
                            $newprofilefields[$field] = $value;
                        } else {
                            // Standard field.
                            $currentvalue = isset($user->$key) ? $user->$key : null;
                            $newuser->$key = $value;
                        }

                        // Only update if it's changed.
                        if ($currentvalue !== $value) {
                            $needsupdate = true;
                        }
                    }
                }
            }

            if ($needsupdate) {
                user_update_user($newuser, false, $triggerevent);
                profile_save_custom_fields($newuser->id, $newprofilefields);
                return $DB->get_record('user', array('id' => $userid, 'deleted' => 0));
            }
        }

        return false;
    }
    /**
     * Forces synchronisation of all enrolments with external database.
     *
     * @param progress_trace $trace
     * @return int 0 means success, 1 db connect failure, 2 db read failure
     */
    public function sync_enrolments(progress_trace $trace) {
        global $DB, $CFG;

        require_once($CFG->libdir.'/filelib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        $trace->output('Starting user enrolment synchronisation...');
        $userstoprocess = $this->get_userenrolments();

        $coursefield      = trim($this->get_config('remotecoursefield'));
        $userfield        = trim($this->get_config('remoteuserfield'));
        $rolefield        = trim($this->get_config('remoterolefield'));
        $groupfield        = trim($this->get_config('remotegroupfield'));


        $localrolefield   = $this->get_config('localrolefield');
        $localuserfield   = $this->get_config('localuserfield');
        $localcoursefield = $this->get_config('localcoursefield');
        $localgroupfield  = $this->get_config('localgroupfield');

        $unenrolaction    = $this->get_config('unenrolaction');
        $defaultrole      = $this->get_config('defaultrole');

        $ignorehidden = $this->get_config('ignorehiddencourses');

        // Create roles mapping.
        $allroles = get_all_roles();
        if (!isset($allroles[$defaultrole])) {
            $defaultrole = 0;
        }
        $roles = array();
        foreach ($allroles as $role) {
            $roles[$role->$localrolefield] = $role->id;
        }

        // First find all existing courses with enrol instance.
        $existingcourses = array();
        $sql = "SELECT c.id, c.visible, c.$localcoursefield AS mapping, e.id AS enrolid, c.shortname
                FROM {course} c
                JOIN {enrol} e ON (e.courseid = c.id AND e.enrol = 'json')";
        $rs = $DB->get_recordset_sql($sql); // Watch out for idnumber duplicates.
        foreach ($rs as $course) {
            if (empty($course->mapping)) {
                continue;
            }
            $existingcourses[$course->mapping] = $course;
        }
        $rs->close();

        $missingcourses = [];
        $missingusers = [];
        $hiddencourses = [];
        foreach ($userstoprocess as $record) {
            // If known missing user, skip.
            if (in_array($record->$userfield, $missingusers)) {
                continue;
            }

            // If user not exist - skip, add to missing users list.
            $user = $DB->get_record('user', [$localuserfield => $record->$userfield, 'deleted' => 0]);
            if (empty($user)) {
                $missingusers[] = $record->$userfield;
                continue;
            }

            // Get list of this users current enrolments with enrol_json.
            $sql = "SELECT ue.id, ue.status, ra.roleid, c.shortname, c.idnumber, c.id as courseid, e.id as enrolid, c.$localcoursefield AS mapping
                      FROM {user} u
                      JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.component = 'enrol_json')
                      JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid = ra.itemid)
                      JOIN {enrol} e on e.id = ue.enrolid
                      JOIN {course} c on c.id = e.courseid
                     WHERE u.deleted = 0 and u.id = :userid";
            $params = ['userid' => $user->id];
            $userenrolments = $DB->get_records_sql($sql, $params);
            $existingenrolments = [];
            foreach ($userenrolments as $ue) {
                $existingenrolments[$ue->mapping] = $ue;
            }
            unset($userenrolments);

            // Get list of this users groups in all courses.
            $sql = "SELECT g.id, g.courseid, g.idnumber, g.name, gm.component
                  FROM {groups} g
                  JOIN {groups_members} gm ON gm.groupid = g.id
                 WHERE gm.userid = ?";

            $rs = $DB->get_recordset_sql($sql, array($user->id));
            $existinggroups = [];
            foreach ($rs as $r) {
                $existinggroups[$r->courseid][$r->$localgroupfield] = $r;
            }
            $rs->close();

            if (empty($record->enrolments)) {
                $trace->output("could not find any enrolments for user:".$user->id);
            }

            // For all courses in the external data for this user.
            foreach ($record->enrolments as $ecourse) {
                $roleid = $defaultrole;
                if (!empty($rolefield) && !empty($ecourse->$rolefield)) {
                    if (!empty($roles[$ecourse->$rolefield])) {
                        $roleid = $roles[$ecourse->$rolefield];
                    }
                }
                if (empty($existingenrolments[$ecourse->$coursefield])) {
                    $enrolcoursecount = 0;
                    // If known as a missing course - skip.
                    if (in_array($ecourse->$coursefield, $missingcourses)) {
                        continue;
                    }

                    // get Json enrolment entry for this course.
                    if (empty($existingcourses[$ecourse->$coursefield])) {
                        // JSON enrolment not added to this course - add it.
                        // Get course record.
                        $course = $DB->get_record('course', [$localcoursefield => $ecourse->$coursefield]);
                        if (empty($course)) {
                            $missingcourses[] = $ecourse->$coursefield;
                            continue;
                        }
                        if (!$course->visible and $ignorehidden) {
                            $hiddencourses[] = $ecourse->$coursefield;
                            continue;
                        }
                        $course->enrolid = $this->add_instance($course);
                        $existingcourses[$ecourse->$coursefield] = $course;
                    }

                    // Sanity check
                    if (empty($existingcourses[$ecourse->$coursefield]) ||
                        (!$existingcourses[$ecourse->$coursefield]->visible and $ignorehidden)) {
                        // Likely a hidden course, but no record so we can't enrol.
                        continue;
                    }

                    // Enrol user in course.
                    $enrol = new stdClass();
                    $enrol->id = $existingcourses[$ecourse->$coursefield]->enrolid;
                    $enrol->courseid = $existingcourses[$ecourse->$coursefield]->id;
                    $enrol->enrol = 'json';

                    $this->enrol_user($enrol, $user->id, $roleid, 0, 0, ENROL_USER_ACTIVE);
                    $enrolcoursecount++;
                    $existingenrolments[$ecourse->$coursefield] = $enrol;
                    $existingenrolments[$ecourse->$coursefield]->inexternal = true;
                } else {
                    $existingenrolments[$ecourse->$coursefield]->inexternal = true;
                    // Reenable enrolment when previously disable enrolment refreshed.
                    if ($existingenrolments[$ecourse->$coursefield]->status == ENROL_USER_SUSPENDED) {
                        $enrol = new stdClass();
                        $enrol->id = $existingcourses[$ecourse->$coursefield]->enrolid;
                        $enrol->courseid = $existingcourses[$ecourse->$coursefield]->id;
                        $enrol->enrol = 'json';
                        $this->update_user_enrol($enrol, $user->id, ENROL_USER_ACTIVE);
                        $trace->output("unsuspending: $user->username ==> $enrol->courseid", 1);
                    }
                    $context = context_course::instance($existingcourses[$ecourse->$coursefield]->id);

                    // Sanity check to make sure user has the correct role and remove any others.
                    $sql = "SELECT ra.id, ra.roleid, ra.itemid
                              FROM {user} u
                              JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.component = 'enrol_json')
                              WHERE u.id = :userid AND ra.contextid = :contextid";
                    $existingroles = $DB->get_records_sql($sql, ['userid' => $user->id,
                                                         'contextid' => $context->id]);

                    // Bug with old version inserted enrolments with itemid = 0, clean them up if found.
                    foreach ($existingroles as $exr) {
                        if ($exr->itemid == 0) {
                            // This is an incorrect role assignment with an empty itemid - remove it.
                            role_unassign($exr->roleid, $user->id, $context->id, 'enrol_json', $exr->itemid);
                            unset($existingroles[$exr->id]);
                        }
                    }

                    $hascorrectrole = false;
                    foreach ($existingroles as $exr) {
                        if ($exr->roleid == $roleid) {
                            $hascorrectrole = true;
                            continue;
                        }
                        $trace->output("remove old role:".$exr->roleid ." from $user->username in courseid: ".
                                       $existingcourses[$ecourse->$coursefield]->id);
                        // Remove any incorrect roles.
                        role_unassign($exr->roleid, $user->id, $context->id, 'enrol_json', $exr->itemid);
                    }
                    // Add back correct role with correct itemid if needed.
                    if (!$hascorrectrole) {
                        role_assign($roleid, $user->id, $context, 'enrol_json', $existingcourses[$ecourse->$coursefield]->enrolid);
                    }
                }

                $courseid = $existingcourses[$ecourse->$coursefield]->id;
                if (!empty($ecourse->groups) && !empty($groupfield)) {
                    foreach ($ecourse->groups as $g) {
                        if (empty($existinggroups[$courseid][$g->$groupfield])) {
                            // TODO - cache group information for this course better?
                            $groups = groups_get_all_groups($courseid);
                            $foundgroup = false;
                            foreach ($groups as $group) {
                                if ($group->$localgroupfield == $g->$groupfield) {
                                    groups_add_member($group->id, $user->id, 'enrol_json');
                                    $foundgroup = true;
                                }
                            }
                            if (!$foundgroup) {
                                $newgroupdata = new \stdClass();
                                $newgroupdata->$localgroupfield = $g->$groupfield;
                                $newgroupdata->name = $newgroupdata->$localgroupfield; // If name not being used.
                                $newgroupdata->courseid = $courseid;
                                $newgroupdata->description = '';
                                $gid = groups_create_group($newgroupdata);
                                groups_add_member($gid, $user->id, 'enrol_json');
                            }
                        } else {
                            // Remove this group from the $existinggroups array for this user as it should remain.
                            unset($existinggroups[$courseid][$g->$groupfield]);
                        }
                    }
                }

                // Remove old json groups.
                if (!empty($existinggroups[$courseid])) {
                    foreach ($existinggroups[$courseid] as $group) {
                        if ($group->component == 'enrol_json') {
                            groups_remove_member($group->id, $user->id);
                        }
                    }
                }
            }
            if (!empty($enrolcoursecount)) {
                $trace->output("enrolled $user->username in $enrolcoursecount courses");
            }
            // If unenrol set - check if user enrolled in places that need to be removed.
            // Deal with enrolments removed from external table.
            if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                foreach ($existingenrolments as $enrolment) {
                    if (!empty($enrolment->inexternal)) {
                        continue;
                    }
                    $enrol = new stdClass();
                    $enrol->id = $enrolment->enrolid;
                    $enrol->courseid = $enrolment->courseid;
                    $enrol->enrol = 'json';
                    $this->unenrol_user($enrol,  $user->id);
                    $trace->output("unenrolling:  $user->username ==> $enrolment->courseid", 1);
                }

            } else if ($unenrolaction == ENROL_EXT_REMOVED_KEEP) {
                // Keep - only adding enrolments.

            } else if ($unenrolaction == ENROL_EXT_REMOVED_SUSPEND or $unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                // Suspend enrolments.
                foreach ($existingenrolments as $enrolment) {
                    if (!empty($enrolment->inexternal)) {
                        continue;
                    }
                    if ($enrolment->status != ENROL_USER_SUSPENDED) {
                        $enrol = new stdClass();
                        $enrol->id = $enrolment->enrolid;
                        $enrol->courseid = $enrolment->courseid;
                        $enrol->enrol = 'json';
                        $this->update_user_enrol($enrol, $user->id, ENROL_USER_SUSPENDED);
                        $trace->output("suspending: $user->username ==> $enrolment->courseid", 1);
                    }
                    if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                        $context = context_course::instance($enrolment->courseid);
                        role_unassign_all(array('contextid' => $context->id, 'userid'=> $user->id, 'component' => 'enrol_json', 'itemid' => $enrol->id));

                        $trace->output("unsassigning all roles: $user->username ==> $enrolment->courseid", 1);
                    }
                }
            } else {
                foreach ($existingenrolments as $enrolment) {
                    if (!empty($enrolment->inexternal)) {
                        continue;
                    }
                    $trace->output("User: $user->username is missing from $enrolment->courseid in the external data but unenrolment is currently disabled.", 1);
                }
            }
        }

        unset($userstoprocess);

        // Print list of missing users.
        if ($missingusers) {
            $list = implode(', ', array_values($missingusers));
            $trace->output("error: following users do not exist - $list", 1);
            unset($list);
        }

        // Print list of missing courses.
        if ($missingcourses) {
            $list = implode(', ', array_values($missingcourses));
            $trace->output("error: following courses do not exist - $list", 1);
            unset($list);
        }

        $trace->output('...user enrolment synchronisation finished.');
        $trace->finished();

        return 0;
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
    array_unshift($userfields, 'username');
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