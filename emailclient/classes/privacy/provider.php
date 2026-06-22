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

namespace local_emailclient\privacy;

use context;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy API implementation for local_emailclient.
 *
 * Personal data is the single account record a user may store
 * (their own IMAP/SMTP server, username, encrypted password, identity).
 * It lives entirely at user context level - there is no cross-user data.
 *
 * Note: actual e-mail messages are never stored by this plugin (they
 * stay on the user's own external mail server and are only streamed
 * through on demand), so there is nothing to export/delete for those.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /** @var string */
    const TABLE = 'local_emailclient_accounts';

    /**
     * @inheritDoc
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            self::TABLE,
            [
                'userid'       => 'privacy:metadata:local_emailclient_accounts:userid',
                'imaphost'     => 'privacy:metadata:local_emailclient_accounts:imaphost',
                'imapusername' => 'privacy:metadata:local_emailclient_accounts:imapusername',
                'imappassword' => 'privacy:metadata:local_emailclient_accounts:imappassword',
                'smtphost'     => 'privacy:metadata:local_emailclient_accounts:smtphost',
                'smtpusername' => 'privacy:metadata:local_emailclient_accounts:smtpusername',
                'smtppassword' => 'privacy:metadata:local_emailclient_accounts:smtppassword',
                'fromemail'    => 'privacy:metadata:local_emailclient_accounts:fromemail',
                'fromname'     => 'privacy:metadata:local_emailclient_accounts:fromname',
                'signature'    => 'privacy:metadata:local_emailclient_accounts:signature',
                'timecreated'  => 'privacy:metadata:local_emailclient_accounts:timecreated',
                'timemodified' => 'privacy:metadata:local_emailclient_accounts:timemodified',
            ],
            'privacy:metadata:local_emailclient_accounts'
        );

        $collection->add_external_location_link(
            'imapserver',
            [
                'imaphost'     => 'privacy:metadata:local_emailclient_accounts:imaphost',
                'imapusername' => 'privacy:metadata:local_emailclient_accounts:imapusername',
                'imappassword' => 'privacy:metadata:local_emailclient_accounts:imappassword',
            ],
            'privacy:metadata:imapserver'
        );

        $collection->add_external_location_link(
            'smtpserver',
            [
                'smtphost'     => 'privacy:metadata:local_emailclient_accounts:smtphost',
                'smtpusername' => 'privacy:metadata:local_emailclient_accounts:smtpusername',
                'smtppassword' => 'privacy:metadata:local_emailclient_accounts:smtppassword',
            ],
            'privacy:metadata:smtpserver'
        );

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists(self::TABLE, ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }
        return $contextlist;
    }

    /**
     * @inheritDoc
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!($context instanceof context_user)) {
            return;
        }
        if ($DB->record_exists(self::TABLE, ['userid' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * @inheritDoc
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!($context instanceof context_user) || (int) $context->instanceid !== (int) $user->id) {
                continue;
            }

            $record = $DB->get_record(self::TABLE, ['userid' => $user->id]);
            if (!$record) {
                continue;
            }

            $data = (object) [
                'imaphost'     => $record->imaphost,
                'imapusername' => $record->imapusername,
                'smtphost'     => $record->smtphost,
                'smtpusername' => $record->smtpusername,
                'fromname'     => $record->fromname,
                'fromemail'    => $record->fromemail,
                'signature'    => $record->signature,
                'timecreated'  => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified),
            ];

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_emailclient')],
                $data
            );
        }
    }

    /**
     * @inheritDoc
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!($context instanceof context_user)) {
            return;
        }
        $DB->delete_records(self::TABLE, ['userid' => $context->instanceid]);
    }

    /**
     * @inheritDoc
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_user && (int) $context->instanceid === (int) $userid) {
                $DB->delete_records(self::TABLE, ['userid' => $userid]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!($context instanceof context_user)) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            if ((int) $userid === (int) $context->instanceid) {
                $DB->delete_records(self::TABLE, ['userid' => $userid]);
            }
        }
    }
}
