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
 * English language strings for local_emailclient.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'E-mail client';
$string['emailclient:use'] = 'Use the e-mail client';
$string['emailclient:manage'] = 'Manage e-mail client site settings';
$string['privacy:metadata:local_emailclient_accounts'] = 'Information about the personal IMAP/SMTP mail account configured by a user.';
$string['privacy:metadata:local_emailclient_accounts:userid'] = 'The ID of the user the account belongs to.';
$string['privacy:metadata:local_emailclient_accounts:imaphost'] = 'The IMAP server hostname.';
$string['privacy:metadata:local_emailclient_accounts:imapusername'] = 'The IMAP login username.';
$string['privacy:metadata:local_emailclient_accounts:imappassword'] = 'The (encrypted) IMAP password.';
$string['privacy:metadata:local_emailclient_accounts:smtphost'] = 'The SMTP server hostname.';
$string['privacy:metadata:local_emailclient_accounts:smtpusername'] = 'The SMTP login username.';
$string['privacy:metadata:local_emailclient_accounts:smtppassword'] = 'The (encrypted) SMTP password.';
$string['privacy:metadata:local_emailclient_accounts:fromemail'] = 'The e-mail address used as sender.';
$string['privacy:metadata:local_emailclient_accounts:fromname'] = 'The display name used as sender.';
$string['privacy:metadata:local_emailclient_accounts:signature'] = 'The personal e-mail signature.';
$string['privacy:metadata:local_emailclient_accounts:timecreated'] = 'The time the account was created.';
$string['privacy:metadata:local_emailclient_accounts:timemodified'] = 'The time the account was last modified.';
$string['privacy:metadata:imapserver'] = 'In order to display your mailbox, your e-mail address, username and password are sent to the external IMAP server you configured.';
$string['privacy:metadata:smtpserver'] = 'In order to send mail on your behalf, your e-mail address, username and password are sent to the external SMTP server you configured.';

// Settings.
$string['settings:heading'] = 'E-mail client settings';
$string['settings:enableplugin'] = 'Enable e-mail client';
$string['settings:enableplugin_desc'] = 'If disabled, the plugin is hidden from the navigation and access is blocked for everyone except administrators.';
$string['settings:maxattachmentsize'] = 'Maximum attachment size (compose)';
$string['settings:maxattachmentsize_desc'] = 'Maximum total size of attachments that may be sent with a single message, in bytes.';
$string['settings:allowedimaphosts'] = 'Allowed IMAP/SMTP hosts';
$string['settings:allowedimaphosts_desc'] = 'Optional. One hostname per line. If set, users may only connect to servers from this allow-list. Leave empty to allow any host.';
$string['settings:messagesperpage'] = 'Messages per page';
$string['settings:messagesperpage_desc'] = 'Number of messages shown per page in the message list.';
$string['settings:connectiontimeout'] = 'Connection timeout (seconds)';
$string['settings:connectiontimeout_desc'] = 'Timeout used for IMAP and SMTP connection attempts.';

// Navigation / page titles.
$string['nav:emailclient'] = 'E-mail';
$string['page:inbox'] = 'Mailbox';
$string['page:message'] = 'Message';
$string['page:compose'] = 'New message';
$string['page:reply'] = 'Reply';
$string['page:replyall'] = 'Reply all';
$string['page:forward'] = 'Forward';
$string['page:account'] = 'E-mail account settings';

// Account form.
$string['account:nosettings'] = 'No e-mail account has been configured yet.';
$string['account:setupnow'] = 'Set up e-mail account';
$string['account:edit'] = 'Edit account settings';
$string['account:imapsection'] = 'Incoming mail (IMAP)';
$string['account:smtpsection'] = 'Outgoing mail (SMTP)';
$string['account:identitysection'] = 'Identity';
$string['account:imaphost'] = 'IMAP host';
$string['account:imapport'] = 'IMAP port';
$string['account:imapsecurity'] = 'IMAP encryption';
$string['account:imapusername'] = 'IMAP username';
$string['account:imappassword'] = 'IMAP password';
$string['account:smtphost'] = 'SMTP host';
$string['account:smtpport'] = 'SMTP port';
$string['account:smtpsecurity'] = 'SMTP encryption';
$string['account:smtpusername'] = 'SMTP username';
$string['account:smtppassword'] = 'SMTP password';
$string['account:samelogindata'] = 'Use the same username/password as IMAP';
$string['account:fromname'] = 'Display name';
$string['account:fromemail'] = 'E-mail address';
$string['account:signature'] = 'Signature';
$string['account:security_none'] = 'None';
$string['account:security_ssl'] = 'SSL/TLS';
$string['account:security_tls'] = 'STARTTLS';
$string['account:testconnection'] = 'Test connection';
$string['account:save'] = 'Save account';
$string['account:saved'] = 'The e-mail account settings have been saved.';
$string['account:deleted'] = 'The e-mail account settings have been deleted.';
$string['account:delete'] = 'Delete account settings';
$string['account:deleteconfirm'] = 'Are you sure you want to delete your stored e-mail account settings? Your mail on the server will not be affected.';
$string['account:testok'] = 'Connection successful: {$a}';
$string['account:testfailed'] = 'Connection failed: {$a}';
$string['account:passwordkepthint'] = 'Leave empty to keep the currently stored password.';
$string['account:hostnotallowed'] = 'This server is not on the administrator\'s allow-list and cannot be used.';

// Mailbox / folders.
$string['folders:heading'] = 'Folders';
$string['folders:refresh'] = 'Refresh';
$string['folders:unreadcount'] = '{$a} unread';
$string['folders:inbox'] = 'Inbox';
$string['folders:sent'] = 'Sent';
$string['folders:drafts'] = 'Drafts';
$string['folders:trash'] = 'Trash';
$string['folders:junk'] = 'Junk';

// Message list.
$string['messages:empty'] = 'This folder is empty.';
$string['messages:from'] = 'From';
$string['messages:subject'] = 'Subject';
$string['messages:date'] = 'Date';
$string['messages:size'] = 'Size';
$string['messages:attachment'] = 'Has attachment';
$string['messages:search'] = 'Search';
$string['messages:searchplaceholder'] = 'Search subject, sender or content…';
$string['messages:searchresultsfor'] = 'Search results for "{$a}"';
$string['messages:clearsearch'] = 'Clear search';
$string['messages:noresults'] = 'No messages match your search.';
$string['messages:page'] = 'Page {$a->page} of {$a->totalpages}';
$string['messages:selectall'] = 'Select all';
$string['messages:markread'] = 'Mark as read';
$string['messages:markunread'] = 'Mark as unread';
$string['messages:delete'] = 'Delete';
$string['messages:deleteconfirm'] = 'Delete the selected message(s)? They will be moved to Trash on the mail server (or permanently deleted if already in Trash).';
$string['messages:nooneselected'] = 'Please select at least one message.';
$string['messages:compose'] = 'New message';
$string['messages:noaccount'] = 'You need to configure your e-mail account before you can use the e-mail client.';

// Single message view.
$string['view:reply'] = 'Reply';
$string['view:replyall'] = 'Reply all';
$string['view:forward'] = 'Forward';
$string['view:delete'] = 'Delete';
$string['view:back'] = 'Back to mailbox';
$string['view:to'] = 'To';
$string['view:cc'] = 'Cc';
$string['view:bcc'] = 'Bcc';
$string['view:date'] = 'Date';
$string['view:attachments'] = 'Attachments ({$a})';
$string['view:download'] = 'Download';
$string['view:showimages'] = 'Show external images';
$string['view:plaintextonly'] = 'This message has no HTML content.';
$string['view:loaderror'] = 'The message could not be loaded. It may have been moved or deleted on the server.';

// Compose.
$string['compose:to'] = 'To';
$string['compose:cc'] = 'Cc';
$string['compose:bcc'] = 'Bcc';
$string['compose:subject'] = 'Subject';
$string['compose:message'] = 'Message';
$string['compose:attachments'] = 'Attachments';
$string['compose:send'] = 'Send';
$string['compose:savedraft'] = 'Save as draft';
$string['compose:discard'] = 'Discard';
$string['compose:sent'] = 'Your message has been sent.';
$string['compose:senderror'] = 'The message could not be sent: {$a}';
$string['compose:invalidrecipient'] = 'Please enter at least one valid recipient address.';
$string['compose:toolarge'] = 'The attachments are too large. Maximum allowed size is {$a}.';
$string['compose:replyprefix'] = 'Re: {$a}';
$string['compose:forwardprefix'] = 'Fwd: {$a}';
$string['compose:forwardedmessage'] = '---------- Forwarded message ----------';
$string['compose:originalmessage'] = '----- Original message -----';

// Errors.
$string['error:imapextensionmissing'] = 'The PHP IMAP extension (ext-imap) is not enabled on this server. Please ask your administrator to enable it.';
$string['error:connectionfailed'] = 'Could not connect to the mail server: {$a}';
$string['error:folderlistfailed'] = 'Could not retrieve the folder list: {$a}';
$string['error:messagelistfailed'] = 'Could not retrieve the message list: {$a}';
$string['error:disabled'] = 'The e-mail client has been disabled by the site administrator.';
$string['error:nopermission'] = 'You do not have permission to use the e-mail client.';
$string['error:invalidfolder'] = 'Invalid or unknown folder.';
$string['error:invalidmessage'] = 'Invalid or unknown message.';

// Admin role management.
$string['page:adminroles'] = 'Access management';
$string['adminroles:desc'] = 'Choose which roles are allowed to use the e-mail client. Ticking a role grants the capability <code>local/emailclient:use</code> at system level. Roles without a tick have no access. Changes take effect immediately.';
$string['adminroles:role'] = 'Role';
$string['adminroles:access'] = 'Access allowed';
$string['adminroles:roletype'] = 'Role type';
$string['adminroles:customrole'] = 'Custom role';
$string['settings:rolessaved'] = 'Role assignments saved.';

// Server defaults.
$string['settings:defaultsheading'] = 'Mail server defaults';
$string['settings:defaultsheading_desc'] = 'These values are pre-filled when a user opens the account form for the first time. Users then only need to enter their personal username and password. Fields left empty are not pre-filled.';
$string['settings:default_imaphost'] = 'Default IMAP server';
$string['settings:default_imaphost_desc'] = 'e.g. mail.school.org';
$string['settings:default_imapport'] = 'Default IMAP port';
$string['settings:default_imapport_desc'] = 'Default: 993 (SSL)';
$string['settings:default_imapsecurity'] = 'Default IMAP encryption';
$string['settings:default_smtphost'] = 'Default SMTP server';
$string['settings:default_smtphost_desc'] = 'Leave empty if same as IMAP.';
$string['settings:default_smtpport'] = 'Default SMTP port';
$string['settings:default_smtpport_desc'] = 'Default: 587 (STARTTLS)';
$string['settings:default_smtpsecurity'] = 'Default SMTP encryption';

// Contacts / address book.
$string['page:contacts'] = 'Contacts';
$string['contact:firstname'] = 'First name';
$string['contact:lastname'] = 'Last name';
$string['contact:email'] = 'E-mail address';
$string['contact:phone'] = 'Phone';
$string['contact:organisation'] = 'Organisation';
$string['contact:notes'] = 'Notes';
$string['contact:shared'] = 'Visible to everyone (shared contact)';
$string['contact:shared_help'] = 'Shared contacts are visible to all users with access to the e-mail client. Only you can edit or delete this contact.';
$string['contact:save'] = 'Save contact';
$string['contact:saved'] = 'Contact saved.';
$string['contact:deleted'] = 'Contact deleted.';
$string['contact:deleteconfirm'] = 'Really delete this contact?';
$string['contact:add'] = 'Add contact';
$string['contact:edit'] = 'Edit contact';
$string['contact:empty'] = 'No contacts yet.';
$string['contact:personal'] = 'My contacts';
$string['contact:name'] = 'Name';
$string['contact:compose'] = 'Write e-mail';
$string['contact:picker'] = '📋 From contacts';
$string['contact:search'] = 'Search contacts…';
$string['settings:contactsheading'] = 'Address book';
$string['settings:contactsheading_desc'] = 'Settings for the address book and shared contacts.';
$string['settings:allowsharedcontacts'] = 'Enable shared contacts';
$string['settings:allowsharedcontacts_desc'] = 'When enabled, users may mark contacts as visible to everyone. Shared contacts appear in the address book of all users with access to the e-mail client.';
