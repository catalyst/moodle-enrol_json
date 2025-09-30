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
 * Sync enrolments users
 *
 * The users that are present in the User API and not in the Enrolment API
 * are unenroled from all their courses.
 *
 * @package   enrol_json
 * @copyright 2025 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_json\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Class sync_enrolments_users
 *
 * @package   enrol_json
 * @copyright 2021 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_enrolments_users extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncuserstaskenrolments', 'enrol_json');
    }

    /**
     * Run task for synchronising users.
     */
    public function execute() {

        $trace = new \text_progress_trace();

        if (!enrol_is_enabled('json')) {
            $trace->output('Plugin not enabled');
            return;
        }
        if (empty(get_config('enrol_json', 'usersync'))) {
            $trace->output('User sync disabled');
            return;
        }

        $enrol = enrol_get_plugin('json');
        if (!$enrol->is_configured()) {
            $trace->output('Plugin not configured');
            return;
        }
        \core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $enrol->sync_enrolments_users($trace);
    }
}
