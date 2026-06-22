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
 * Compose a new message, or reply/reply-all/forward an existing one.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_emailclient\account_manager;
use local_emailclient\imap_client;
use local_emailclient\mail_sender;
use local_emailclient\html_sanitizer;
use local_emailclient\page_helper;
use local_emailclient\form\compose_form;

page_helper::require_access();

$action = optional_param('action', 'new', PARAM_ALPHA);
$folder = optional_param('folder', '', PARAM_RAW);
$origuid = optional_param('uid', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/emailclient/compose.php', ['action' => $action, 'folder' => $folder, 'uid' => $origuid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

$titlekey = ['new' => 'page:compose', 'reply' => 'page:reply', 'replyall' => 'page:reply', 'forward' => 'page:forward'][$action] ?? 'page:compose';
$PAGE->set_title(get_string($titlekey, 'local_emailclient'));
$PAGE->set_heading(get_string('pluginname', 'local_emailclient'));

$account = account_manager::get_for_user($USER->id);
if (!$account) {
    redirect(new moodle_url('/local/emailclient/account.php'));
}

$maxbytes = (int) (get_config('local_emailclient', 'maxattachmentsize') ?: 25 * 1024 * 1024);

/**
 * Removes the account's own e-mail address from a comma-separated address list.
 *
 * @param string $list
 * @param string $ownemail
 * @return string
 */
function local_emailclient_strip_own_address(string $list, string $ownemail): string {
    if ($ownemail === '' || trim($list) === '') {
        return $list;
    }
    $parts = array_filter(array_map('trim', preg_split('/[,;]+/', $list)));
    $parts = array_filter($parts, fn($addr) => stripos($addr, $ownemail) === false);
    return implode(', ', $parts);
}

$mform = new compose_form(null, ['maxbytes' => $maxbytes]);

if ($mform->is_cancelled()) {
    if ($folder !== '' && $origuid) {
        redirect(new moodle_url('/local/emailclient/view.php', ['folder' => $folder, 'uid' => $origuid]));
    }
    redirect(new moodle_url('/local/emailclient/index.php'));

} else if ($data = $mform->get_data()) {

    $fs = get_file_storage();
    $usercontext = context_user::instance($USER->id);
    $attachments = [];
    if (!empty($data->attachments)) {
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->attachments, 'id', false);
        foreach ($files as $file) {
            $attachments[] = [
                'filename' => $file->get_filename(),
                'mimetype' => $file->get_mimetype(),
                'content'  => $file->get_content(),
            ];
        }
    }

    $bodyhtml = $data->message['text'] ?? '';

    try {
        (new mail_sender($account))->send([
            'to'          => $data->to,
            'cc'          => $data->cc,
            'bcc'         => $data->bcc,
            'subject'     => $data->subject,
            'bodyhtml'    => $bodyhtml,
            'bodyplain'   => '',
            'attachments' => $attachments,
            'inreplyto'   => $data->inreplyto,
        ]);

        if (!empty($data->attachments)) {
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $data->attachments);
        }

        // Invalidate IMAP cache so the sent folder shows the new message.
        (new \local_emailclient\imap_client($account))->invalidate_cache();

        redirect(
            new moodle_url('/local/emailclient/index.php'),
            get_string('compose:sent', 'local_emailclient'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (Throwable $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('compose:senderror', 'local_emailclient', $e->getMessage()), 'error');
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }

} else {
    // Initial display: prefill based on action (reply / reply-all / forward).
    $prefill = (object) [
        'action' => $action,
        'origfolder' => $folder,
        'origuid' => $origuid,
        'to' => '', 'cc' => '', 'bcc' => '', 'subject' => '', 'inreplyto' => '',
        'message' => ['text' => '', 'format' => FORMAT_HTML],
    ];

    if ($folder !== '' && $origuid && in_array($action, ['reply', 'replyall', 'forward'])) {
        try {
            $client = new imap_client($account);
            $orig = $client->get_message($folder, $origuid);

            $origsubject = $orig->subject;
            $quotedheader = $orig->date . ' - ' . $orig->from;
            $quotedbody = $orig->html !== ''
                ? html_sanitizer::sanitize($orig->html)
                : html_writer::tag('pre', s($orig->plain));

            if ($action === 'forward') {
                $prefill->subject = (stripos($origsubject, 'fwd:') === 0)
                    ? $origsubject
                    : get_string('compose:forwardprefix', 'local_emailclient', $origsubject);
                $quoteblock = html_writer::tag('p', s(get_string('compose:forwardedmessage', 'local_emailclient')))
                    . html_writer::tag('p', s($quotedheader))
                    . html_writer::tag('blockquote', $quotedbody, ['style' => 'border-left:2px solid #ccc;padding-left:10px;color:#555;']);
                $prefill->message['text'] = '<p><br></p>' . $quoteblock;
            } else {
                $prefill->to = $orig->replyto !== '' ? $orig->replyto : $orig->from;
                if ($action === 'replyall') {
                    $cc = local_emailclient_strip_own_address($orig->to, $account->fromemail);
                    $cc .= ($cc !== '' && $orig->cc !== '' ? ', ' : '') . local_emailclient_strip_own_address($orig->cc, $account->fromemail);
                    $prefill->cc = $cc;
                }
                $prefill->subject = (stripos($origsubject, 're:') === 0)
                    ? $origsubject
                    : get_string('compose:replyprefix', 'local_emailclient', $origsubject);
                $quoteblock = html_writer::tag('p', s(get_string('compose:originalmessage', 'local_emailclient')))
                    . html_writer::tag('p', s($quotedheader))
                    . html_writer::tag('blockquote', $quotedbody, ['style' => 'border-left:2px solid #ccc;padding-left:10px;color:#555;']);
                $prefill->message['text'] = '<p><br></p>' . $quoteblock;
                $prefill->inreplyto = $orig->messageid;
            }
        } catch (Throwable $e) {
            // Original message could not be loaded - fall back to a blank compose form.
            unset($e);
        }
    }

    if ($account->signature !== '') {
        $prefill->message['text'] .= '<p>&nbsp;</p><p>' . nl2br(s($account->signature)) . '</p>';
    }

    $mform->set_data($prefill);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($titlekey, 'local_emailclient'));
$mform->display();
echo $OUTPUT->footer();
