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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_emailclient;

use context_system;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared login/capability/enabled checks used by every page script.
 *
 * Access check strategy
 * ---------------------
 * The plugin uses the capability local/emailclient:use, but teachers are
 * typically only enrolled in courses (course context), not assigned roles at
 * system level. A plain has_capability(..., context_system) would therefore
 * deny access to everyone who is "only" a teacher in a course.
 *
 * We therefore use a two-step check:
 *
 * 1. Normal Moodle system-level check (covers admins and users with explicit
 *    system role assignments).
 * 2. Fallback: fetch all roles that have been granted local/emailclient:use
 *    (via our role manager page) and check whether the user holds any of
 *    those roles in ANY context (course, category, …) using
 *    user_has_role_assignment($userid, $roleid, contextid=0).
 *
 * This means: tick "Trainer/in" in the role manager → every user who is a
 * trainer in at least one course automatically gets access, with no need for
 * system role assignments or cohort tricks.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_helper {

    /**
     * Returns true if the current user may use the e-mail client.
     *
     * Safe to call before require_login() (returns false for guests/unauthenticated).
     *
     * @return bool
     */
    public static function has_access(): bool {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if (empty(get_config('local_emailclient', 'enableplugin'))) {
            return false;
        }

        $context = context_system::instance();

        // Step 1: standard system-level capability check.
        // Covers site admins and users with explicit system role assignments.
        if (has_capability('local/emailclient:use', $context)) {
            return true;
        }

        // Step 2: check whether the user holds any of the allowed roles in
        // any context (e.g. as Trainer in a course).
        // user_has_role_assignment($userid, $roleid, $contextid = 0) with
        // contextid = 0 searches across ALL contexts.
        $allowedroles = get_roles_with_capability('local/emailclient:use', CAP_ALLOW, $context);
        foreach ($allowedroles as $role) {
            if (user_has_role_assignment($USER->id, $role->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enforces login, plugin-enabled check and access check.
     * Throws a moodle_exception on failure.
     *
     * @return void
     * @throws moodle_exception
     */
    public static function require_access(): void {
        require_login();

        if (empty(get_config('local_emailclient', 'enableplugin'))) {
            throw new moodle_exception('error:disabled', 'local_emailclient');
        }

        if (!self::has_access()) {
            throw new moodle_exception('error:nopermission', 'local_emailclient');
        }
    }
}
