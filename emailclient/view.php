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
 * Displays a single message: headers, sanitized body, attachment list,
 * reply/forward/delete actions. Automatically marks the message as read.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_emailclient\account_manager;
use local_emailclient\imap_client;
use local_emailclient\html_sanitizer;
use local_emailclient\page_helper;

page_helper::require_access();

$folder = required_param('folder', PARAM_RAW);
$uid = required_param('uid', PARAM_INT);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/emailclient/view.php', ['folder' => $folder, 'uid' => $uid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('page:message', 'local_emailclient'));
$PAGE->set_heading(get_string('pluginname', 'local_emailclient'));
$PAGE->requires->css('/local/emailclient/styles.css');

$account = account_manager::get_for_user($USER->id);
if (!$account) {
    redirect(new moodle_url('/local/emailclient/account.php'));
}

$backurl = new moodle_url('/local/emailclient/index.php', ['folder' => $folder]);

$client = new imap_client($account);
try {
    $message = $client->get_message($folder, $uid);
    $client->set_flag($folder, $uid, '\\Seen', true);
} catch (Throwable $e) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('view:loaderror', 'local_emailclient'), 'error');
    echo $OUTPUT->single_button($backurl, get_string('view:back', 'local_emailclient'));
    echo $OUTPUT->footer();
    exit;
}

$sanitizedhtml = $message->html !== '' ? html_sanitizer::sanitize($message->html) : '';
$showimagesbutton = $sanitizedhtml !== '' && html_sanitizer::has_blocked_images($sanitizedhtml);

echo $OUTPUT->header();

echo html_writer::start_div('emailclient-message-view');

echo html_writer::link($backurl, '&laquo; ' . get_string('view:back', 'local_emailclient'), ['class' => 'mb-2 d-inline-block']);

echo $OUTPUT->heading(s($message->subject) !== '' ? s($message->subject) : '(' . get_string('messages:subject', 'local_emailclient') . ')', 3);

echo html_writer::start_div('emailclient-headers card card-body mb-3');
echo html_writer::tag('div', '<strong>' . get_string('messages:from', 'local_emailclient') . ':</strong> ' . s($message->from));
echo html_writer::tag('div', '<strong>' . get_string('view:to', 'local_emailclient') . ':</strong> ' . s($message->to));
if ($message->cc !== '') {
    echo html_writer::tag('div', '<strong>' . get_string('view:cc', 'local_emailclient') . ':</strong> ' . s($message->cc));
}
echo html_writer::tag('div', '<strong>' . get_string('view:date', 'local_emailclient') . ':</strong> '
    . ($message->timestamp ? userdate($message->timestamp, get_string('strftimedatetime', 'langconfig')) : s($message->date)));
echo html_writer::end_div();

// Action buttons.
echo html_writer::start_div('btn-group mb-3');
echo html_writer::link(
    new moodle_url('/local/emailclient/compose.php', ['action' => 'reply', 'folder' => $folder, 'uid' => $uid]),
    get_string('view:reply', 'local_emailclient'),
    ['class' => 'btn btn-primary']
);
echo html_writer::link(
    new moodle_url('/local/emailclient/compose.php', ['action' => 'replyall', 'folder' => $folder, 'uid' => $uid]),
    get_string('view:replyall', 'local_emailclient'),
    ['class' => 'btn btn-outline-primary']
);
echo html_writer::link(
    new moodle_url('/local/emailclient/compose.php', ['action' => 'forward', 'folder' => $folder, 'uid' => $uid]),
    get_string('view:forward', 'local_emailclient'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'post', 'action' => new moodle_url('/local/emailclient/index.php'), 'class' => 'd-inline mb-3',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'postfolder', 'value' => $folder]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'uid[]', 'value' => $uid]);
echo html_writer::tag('button', get_string('view:delete', 'local_emailclient'), [
    'type' => 'submit', 'name' => 'bulkaction', 'value' => 'delete', 'class' => 'btn btn-outline-danger',
    'onclick' => 'return confirm(' . json_encode(get_string('messages:deleteconfirm', 'local_emailclient')) . ');',
]);
echo html_writer::end_tag('form');

// Attachments.
if (!empty($message->attachments)) {
    echo $OUTPUT->heading(get_string('view:attachments', 'local_emailclient', count($message->attachments)), 5);
    echo html_writer::start_tag('ul', ['class' => 'list-group mb-3 emailclient-attachments']);
    foreach ($message->attachments as $attachment) {
        $downloadurl = new moodle_url('/local/emailclient/download.php', [
            'folder' => $folder, 'uid' => $uid, 'part' => $attachment->partnum, 'sesskey' => sesskey(),
        ]);
        echo html_writer::tag(
            'li',
            html_writer::link($downloadurl, s($attachment->filename)) . ' '
                . html_writer::span('(' . display_size($attachment->size) . ')', 'text-muted'),
            ['class' => 'list-group-item']
        );
    }
    echo html_writer::end_tag('ul');
}

// Body.
echo html_writer::start_div('emailclient-body card card-body');
if ($sanitizedhtml !== '') {
    if ($showimagesbutton) {
        echo html_writer::tag(
            'button',
            get_string('view:showimages', 'local_emailclient'),
            ['type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary mb-2', 'id' => 'emailclient-showimages']
        );
    }
    echo html_writer::div($sanitizedhtml, 'emailclient-html-body');
} else if ($message->plain !== '') {
    echo html_writer::tag('p', get_string('view:plaintextonly', 'local_emailclient'), ['class' => 'text-muted small']);
    echo format_text($message->plain, FORMAT_PLAIN);
}
echo html_writer::end_div();

echo html_writer::end_div();

$PAGE->requires->js_amd_inline("
require(['jquery'], function($) {
    $('#emailclient-showimages').on('click', function() {
        $('.emailclient-html-body img[data-original-src]').each(function() {
            $(this).attr('src', $(this).attr('data-original-src'));
        });
        $(this).hide();
    });
});
");

echo $OUTPUT->footer();
