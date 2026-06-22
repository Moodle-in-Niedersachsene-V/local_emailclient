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
 * Streams a single e-mail attachment, fetched live from IMAP (attachments
 * are never stored permanently by this plugin).
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_emailclient\account_manager;
use local_emailclient\imap_client;
use local_emailclient\page_helper;

page_helper::require_access();
require_sesskey();

$folder = required_param('folder', PARAM_RAW);
$uid = required_param('uid', PARAM_INT);
$part = required_param('part', PARAM_RAW);

$account = account_manager::get_for_user($USER->id);
if (!$account) {
    throw new moodle_exception('messages:noaccount', 'local_emailclient');
}

$client = new imap_client($account);
$attachment = $client->fetch_attachment($folder, $uid, $part);

$filename = str_replace(["\r", "\n", '"'], '', $attachment->filename);

\core\session\manager::write_close();

header('Content-Type: ' . $attachment->mimetype);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($attachment->data));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: no-cache');

echo $attachment->data;
exit;
