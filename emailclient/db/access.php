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
 * Capability definitions for local_emailclient.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Allows a user to open the e-mail client, configure their own
    // IMAP/SMTP account and read/send their own mail. This never grants
    // access to other users' mail accounts - everything is scoped to
    // the logged in user.
    'local/emailclient:use' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'guest'          => CAP_PREVENT,
            'user'           => CAP_ALLOW,
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],

    // Allows configuration of site-wide defaults / restrictions via the
    // plugin's admin settings page (handled automatically by Moodle for
    // moodle/site:config, this capability is kept for completeness in
    // case finer-grained admin delegation is needed in the future).
    'local/emailclient:manage' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
