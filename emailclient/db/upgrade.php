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

/**
 * Upgrade steps for local_emailclient.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Runs upgrade steps for this plugin.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool always true.
 */
function xmldb_local_emailclient_upgrade(int $oldversion): bool {
    // No upgrade steps yet - this is the initial release (1.0.0).
    // Future schema changes go here, each guarded by:
    // if ($oldversion < YYYYMMDDXX) { ... ; upgrade_plugin_savepoint(true, YYYYMMDDXX, 'local', 'emailclient'); }
    return true;
}
