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
 * MUC cache definitions for local_emailclient.
 *
 * SESSION mode: data is stored in the user's PHP session, automatically
 * isolated per user, purged on logout - no cross-user data leakage.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [

    // Folder list + unseen counts. Also stores the generation counter used
    // to invalidate all caches after write operations (delete, flag change).
    'folders' => [
        'mode'       => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl'        => 60,
    ],

    // Paginated message overview (from/subject/date/seen flag).
    'messagelist' => [
        'mode'       => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl'        => 60,
    ],

    // Full message body + attachment list. Longer TTL: message content
    // never changes, only the seen-flag might, which invalidate_cache() handles.
    'message' => [
        'mode'       => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl'        => 300,
    ],
];
