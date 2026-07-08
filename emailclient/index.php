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
 * Mailbox: folder list + message list, with search, pagination and
 * mark read/unread/delete actions.
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

$folderparam = optional_param('folder', '', PARAM_RAW);
$page        = optional_param('page', 0, PARAM_INT);
$search      = optional_param('search', '', PARAM_RAW_TRIMMED);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/emailclient/index.php', ['folder' => $folderparam, 'page' => $page, 'search' => $search]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('page:inbox', 'local_emailclient'));
$PAGE->set_heading(get_string('pluginname', 'local_emailclient'));
$PAGE->requires->css('/local/emailclient/styles.css');

$account = account_manager::get_for_user($USER->id);

if (!$account) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'local_emailclient'));
    echo $OUTPUT->notification(get_string('messages:noaccount', 'local_emailclient'), 'info');
    echo $OUTPUT->single_button(
        new moodle_url('/local/emailclient/account.php'),
        get_string('account:setupnow', 'local_emailclient')
    );
    echo $OUTPUT->footer();
    exit;
}

$perpage = (int) (get_config('local_emailclient', 'messagesperpage') ?: 25);

// Handle bulk actions (mark read/unread, delete) posted from the message list.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $uids = optional_param_array('uid', [], PARAM_INT);
    $bulkaction = optional_param('bulkaction', '', PARAM_ALPHA);
    $postfolder = required_param('postfolder', PARAM_RAW);

    if (!empty($uids) && $bulkaction !== '') {
        $client = new imap_client($account);
        try {
            foreach ($uids as $uid) {
                switch ($bulkaction) {
                    case 'markread':
                        $client->set_flag($postfolder, $uid, '\\Seen', true);
                        break;
                    case 'markunread':
                        $client->set_flag($postfolder, $uid, '\\Seen', false);
                        break;
                    case 'delete':
                        $client->delete_message($postfolder, $uid);
                        break;
                }
            }
        } catch (Throwable $e) {
            // Fall through - the redirect below will simply show the
            // mailbox again; transient errors are not worth blocking on.
            unset($e);
        }
    }

    redirect(new moodle_url('/local/emailclient/index.php', ['folder' => $postfolder, 'page' => $page, 'search' => $search]));
}

$client = new imap_client($account);

$foldererror = null;
$listerror = null;
$folders = [];
$result = (object) ['messages' => [], 'total' => 0];

try {
    $folders = $client->get_folders();
} catch (Throwable $e) {
    $foldererror = $e->getMessage();
}

$folder = $folderparam;
if ($folder === '') {
    foreach ($folders as $f) {
        if (strcasecmp($f->name, 'INBOX') === 0) {
            $folder = $f->rawname;
            break;
        }
    }
    if ($folder === '' && !empty($folders)) {
        $folder = $folders[0]->rawname;
    }
    if ($folder === '') {
        $folder = 'INBOX';
    }
}

try {
    $result = $client->get_messages($folder, $page, $perpage, $search);
} catch (Throwable $e) {
    $listerror = $e->getMessage();
}

/**
 * Maps a folder's display name to a translated label for well-known
 * special folders (Inbox/Sent/Drafts/Trash/Junk across EN/DE servers).
 *
 * @param string $name
 * @return string
 */
function local_emailclient_folder_label(string $name): string {
    // Use only the last path segment so that 'INBOX.Gesendet' is compared
    // as 'Gesendet', not as the full string (which would falsely match
    // 'inbox' for every sub-folder on Dovecot/Hetzner servers).
    $parts = preg_split('/[.\\/]/', $name);
    $leaf  = end($parts);

    // mb_strtolower handles umlauts correctly (ü→ü, Ä→ä, etc.) unlike
    // strtolower, which is ASCII-only and would leave 'Entwürfe' → 'entwÜrfe'.
    $lower = mb_strtolower($leaf, 'UTF-8');

    // Needles include both the English RFC names and the German names used
    // by Dovecot (Hetzner, Strato, …). Exact German names are listed
    // explicitly because 'entwurf' would not match 'entwürfe' even with
    // mb_strtolower (ü ≠ u).
    $map = [
        'folders:inbox'  => ['inbox', 'posteingang'],
        'folders:sent'   => ['sent', 'gesendet'],
        'folders:drafts' => ['draft', 'entwurf', 'entwürfe'],
        'folders:trash'  => ['trash', 'papierkorb', 'deleted', 'gelöscht'],
        'folders:junk'   => ['junk', 'spam'],
    ];
    foreach ($map as $stringkey => $needles) {
        foreach ($needles as $needle) {
            if (mb_strpos($lower, $needle, 0, 'UTF-8') !== false) {
                return get_string($stringkey, 'local_emailclient');
            }
        }
    }

    // For unknown folders show only the leaf name, not the full INBOX.Xxx path.
    return $leaf !== '' ? $leaf : $name;
}

echo $OUTPUT->header();

echo html_writer::start_div('row');

// Folder list (left column).
echo html_writer::start_div('col-md-3 emailclient-folders');
echo $OUTPUT->heading(get_string('folders:heading', 'local_emailclient'), 4);
echo $OUTPUT->single_button(
    new moodle_url('/local/emailclient/compose.php'),
    get_string('messages:compose', 'local_emailclient'),
    'get'
);
echo $OUTPUT->single_button(
    new moodle_url('/local/emailclient/contacts.php'),
    get_string('page:contacts', 'local_emailclient'),
    'get'
);

if ($foldererror !== null) {
    echo $OUTPUT->notification(get_string('error:folderlistfailed', 'local_emailclient', $foldererror), 'error');
} else {
    echo html_writer::start_div('list-group mt-2 emailclient-folder-list');
    foreach ($folders as $f) {
        $active = ($f->rawname === $folder);
        $url = new moodle_url('/local/emailclient/index.php', ['folder' => $f->rawname]);
        $label = local_emailclient_folder_label($f->name);
        $badge = $f->unseen > 0
            ? html_writer::span($f->unseen, 'badge badge-primary ms-auto')
            : '';
        // Using <a> directly as list-group-item (Bootstrap 4/5 pattern) so
        // text colour is correctly set to white when active, without relying
        // on theme-specific link-colour overrides.
        echo html_writer::tag(
            'a',
            s($label) . $badge,
            [
                'href'  => $url->out(false),
                'class' => 'list-group-item list-group-item-action d-flex justify-content-between align-items-center'
                          . ($active ? ' active' : ''),
            ]
        );
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Message list (right column).
echo html_writer::start_div('col-md-9 emailclient-messages');

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/emailclient/index.php'), 'class' => 'form-inline mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'folder', 'value' => $folder]);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'search', 'value' => s($search), 'class' => 'form-control mr-2',
    'placeholder' => get_string('messages:searchplaceholder', 'local_emailclient'),
]);
echo html_writer::tag('button', get_string('messages:search', 'local_emailclient'), ['type' => 'submit', 'class' => 'btn btn-secondary']);
if ($search !== '') {
    echo html_writer::link(
        new moodle_url('/local/emailclient/index.php', ['folder' => $folder]),
        get_string('messages:clearsearch', 'local_emailclient'),
        ['class' => 'btn btn-link']
    );
}
echo html_writer::end_tag('form');

if ($search !== '') {
    echo html_writer::tag('p', get_string('messages:searchresultsfor', 'local_emailclient', s($search)));
}

if ($listerror !== null) {
    echo $OUTPUT->notification(get_string('error:messagelistfailed', 'local_emailclient', $listerror), 'error');
} else if (empty($result->messages)) {
    echo $OUTPUT->notification(
        $search !== '' ? get_string('messages:noresults', 'local_emailclient') : get_string('messages:empty', 'local_emailclient'),
        'info'
    );
} else {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/local/emailclient/index.php')]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'postfolder', 'value' => $folder]);

    echo html_writer::start_tag('table', ['class' => 'table table-hover emailclient-message-list']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', html_writer::checkbox('selectall', 1, false, '', ['id' => 'emailclient-selectall']));
    echo html_writer::tag('th', get_string('messages:from', 'local_emailclient'));
    echo html_writer::tag('th', get_string('messages:subject', 'local_emailclient'));
    echo html_writer::tag('th', get_string('messages:date', 'local_emailclient'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($result->messages as $message) {
        $rowclass = $message->seen ? '' : 'font-weight-bold';
        $viewurl = new moodle_url('/local/emailclient/view.php', ['folder' => $folder, 'uid' => $message->uid]);
        $subjecttext = $message->subject !== '' ? $message->subject : ('(' . get_string('messages:subject', 'local_emailclient') . ')');
        echo html_writer::start_tag('tr', ['class' => $rowclass]);
        echo html_writer::tag('td', html_writer::checkbox('uid[]', $message->uid, false));
        echo html_writer::tag('td', s($message->from));
        echo html_writer::tag('td', html_writer::link($viewurl, s($subjecttext)));
        echo html_writer::tag('td', $message->timestamp ? userdate($message->timestamp, get_string('strftimedatetimeshort', 'langconfig')) : s($message->date));
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    echo html_writer::start_div('btn-group');
    echo html_writer::tag('button', get_string('messages:markread', 'local_emailclient'), [
        'type' => 'submit', 'name' => 'bulkaction', 'value' => 'markread', 'class' => 'btn btn-outline-secondary',
    ]);
    echo html_writer::tag('button', get_string('messages:markunread', 'local_emailclient'), [
        'type' => 'submit', 'name' => 'bulkaction', 'value' => 'markunread', 'class' => 'btn btn-outline-secondary',
    ]);
    echo html_writer::tag('button', get_string('messages:delete', 'local_emailclient'), [
        'type' => 'submit', 'name' => 'bulkaction', 'value' => 'delete', 'class' => 'btn btn-outline-danger',
        'onclick' => 'return confirm(' . json_encode(get_string('messages:deleteconfirm', 'local_emailclient')) . ');',
    ]);
    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    $totalpages = max(1, (int) ceil($result->total / $perpage));
    if ($totalpages > 1) {
        echo html_writer::start_div('mt-3 emailclient-pagination');
        echo $OUTPUT->paging_bar($result->total, $page, $perpage, $PAGE->url);
        echo html_writer::tag(
            'p',
            get_string('messages:page', 'local_emailclient', (object) ['page' => $page + 1, 'totalpages' => $totalpages]),
            ['class' => 'text-muted']
        );
        echo html_writer::end_div();
    }
}

echo html_writer::end_div();
echo html_writer::end_div();

$PAGE->requires->js_amd_inline("
require(['jquery'], function($) {
    $('#emailclient-selectall').on('change', function() {
        $('input[name=\"uid[]\"]').prop('checked', $(this).is(':checked'));
    });
});
");

echo $OUTPUT->footer();
