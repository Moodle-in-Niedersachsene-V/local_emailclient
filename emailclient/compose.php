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
use local_emailclient\contact_manager;

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

// Pre-fill To field when launched from the contacts page.
$to_prefill = optional_param('to', '', PARAM_EMAIL);

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
        $rawmessage = (new mail_sender($account))->send([
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

        // Save a copy to the IMAP Sent folder (SMTP itself does not do this).
        $imapforappend = new imap_client($account);
        $imapforappend->append_to_sent($rawmessage);

        // Invalidate IMAP cache so the sent folder shows the new message.
        $imapforappend->invalidate_cache();

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
        'to' => $to_prefill, 'cc' => '', 'bcc' => '', 'subject' => '', 'inreplyto' => '',
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

// Contact picker button + panel.
$contacts = contact_manager::get_all_for_user($USER->id);
if (!empty($contacts)) {
    // Build contacts JSON for JS.
    $contactsjson = json_encode(array_values(array_map(function($c) {
        $name  = trim($c->firstname . ' ' . $c->lastname);
        $label = $name !== '' ? $name . ' <' . $c->email . '>' : $c->email;
        return ['email' => $c->email, 'name' => $name, 'label' => $label];
    }, $contacts)));

    echo html_writer::tag('button', get_string('contact:picker', 'local_emailclient'), [
        'type'  => 'button',
        'class' => 'btn btn-outline-secondary btn-sm mb-3',
        'id'    => 'emailclient-contact-toggle',
    ]);
    echo html_writer::start_div('emailclient-contact-picker card card-body mb-3',
        ['id' => 'emailclient-contact-panel', 'style' => 'display:none;']);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'id' => 'emailclient-contact-search',
        'class' => 'form-control mb-2',
        'placeholder' => get_string('contact:search', 'local_emailclient'),
    ]);
    echo html_writer::start_tag('ul', ['class' => 'list-group emailclient-contact-list',
        'id' => 'emailclient-contact-list']);
    foreach ($contacts as $c) {
        $cname = trim(s($c->firstname) . ' ' . s($c->lastname));
        echo html_writer::tag('li', $cname . ' &lt;' . s($c->email) . '&gt;', [
            'class'      => 'list-group-item list-group-item-action emailclient-contact-item',
            'data-email' => s($c->email),
            'data-name'  => $cname,
            'style'      => 'cursor:pointer;',
        ]);
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

$mform->display();

if (!empty($contacts)) {
    // Inline JS for contact picker and autocomplete.
    // Use heredoc so no PHP escaping issues with quotes inside JS.
    $js = <<<JSEOF
require(['jquery'], function($) {
    var contacts = {$contactsjson};

    $('#emailclient-contact-toggle').on('click', function() {
        $('#emailclient-contact-panel').toggle();
        $('#emailclient-contact-search').val('').trigger('input').focus();
    });

    $('#emailclient-contact-search').on('input', function() {
        var q = $(this).val().toLowerCase();
        $('#emailclient-contact-list .emailclient-contact-item').each(function() {
            $(this).toggle(q === '' || $(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });

    $('#emailclient-contact-list').on('click', '.emailclient-contact-item', function() {
        var email = $(this).data('email');
        var name  = $(this).data('name');
        var entry = name ? name + ' <' + email + '>' : email;
        var toField = $('input[name=to]');
        var cur = toField.val().trim();
        toField.val(cur ? cur + ', ' + entry : entry);
        $('#emailclient-contact-panel').hide();
    });

    function setupAutocomplete(sel) {
        var inp = $(sel);
        if (!inp.length) { return; }
        var drop = $('<ul>').addClass('list-group emailclient-autocomplete').css({
            position: 'absolute', zIndex: 1000, maxHeight: '200px',
            overflowY: 'auto', minWidth: inp.outerWidth()
        }).hide().insertAfter(inp);

        inp.on('input', function() {
            var val  = inp.val();
            var last = val.split(/[,;]/).pop().trim().toLowerCase();
            if (last.length < 2) { drop.hide(); return; }
            var hits = contacts.filter(function(c) {
                return c.label.toLowerCase().indexOf(last) !== -1;
            }).slice(0, 8);
            drop.empty();
            if (!hits.length) { drop.hide(); return; }
            hits.forEach(function(c) {
                $('<li>').addClass('list-group-item list-group-item-action')
                    .css({cursor: 'pointer', fontSize: '0.9em'})
                    .text(c.label)
                    .on('click', function() {
                        var parts = val.split(/[,;]/);
                        parts[parts.length - 1] = ' ' + (c.name ? c.name + ' <' + c.email + '>' : c.email);
                        inp.val(parts.join(',').replace(/^,\s*/, ''));
                        drop.hide();
                    })
                    .appendTo(drop);
            });
            drop.show();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest(inp).length && !$(e.target).closest(drop).length) {
                drop.hide();
            }
        });
    }

    setupAutocomplete('input[name=to]');
    setupAutocomplete('input[name=cc]');
    setupAutocomplete('input[name=bcc]');
});
JSEOF;
    $PAGE->requires->js_amd_inline($js);
}

echo $OUTPUT->footer();
