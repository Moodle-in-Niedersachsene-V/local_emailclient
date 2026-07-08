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

function xmldb_local_emailclient_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026070801) {
        // Add the contacts table for existing installations.
        $table = new xmldb_table('local_emailclient_contacts');

        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');
        $table->add_field('shared',       XMLDB_TYPE_INTEGER, '1',   null, XMLDB_NOTNULL, null, '0');
        $table->add_field('firstname',    XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastname',     XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email',        XMLDB_TYPE_CHAR,    '254', null, XMLDB_NOTNULL);
        $table->add_field('phone',        XMLDB_TYPE_CHAR,    '50',  null, null);
        $table->add_field('organisation', XMLDB_TYPE_CHAR,    '200', null, null);
        $table->add_field('notes',        XMLDB_TYPE_TEXT,    null,  null, null);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary',   XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        $table->add_index('idx_shared', XMLDB_INDEX_NOTUNIQUE, ['shared']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026070801, 'local', 'emailclient');
    }

    return true;
}
