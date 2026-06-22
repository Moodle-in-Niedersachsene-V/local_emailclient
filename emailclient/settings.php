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
 * Admin settings for local_emailclient.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // Use a category so we can have both the settings page and the
    // role management page as separate entries under "Local plugins".
    $ADMIN->add('localplugins', new admin_category(
        'local_emailclient_cat',
        get_string('pluginname', 'local_emailclient')
    ));

    $settings = new admin_settingpage('local_emailclient', get_string('settings:heading', 'local_emailclient'));
    $ADMIN->add('local_emailclient_cat', $settings);

    $ADMIN->add('local_emailclient_cat', new admin_externalpage(
        'local_emailclient_roles',
        get_string('page:adminroles', 'local_emailclient'),
        new moodle_url('/local/emailclient/adminroles.php'),
        'moodle/site:config'
    ));

    $settings->add(new admin_setting_heading(
        'local_emailclient/heading',
        get_string('settings:heading', 'local_emailclient'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_emailclient/enableplugin',
        get_string('settings:enableplugin', 'local_emailclient'),
        get_string('settings:enableplugin_desc', 'local_emailclient'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_emailclient/maxattachmentsize',
        get_string('settings:maxattachmentsize', 'local_emailclient'),
        get_string('settings:maxattachmentsize_desc', 'local_emailclient'),
        25 * 1024 * 1024,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_emailclient/allowedimaphosts',
        get_string('settings:allowedimaphosts', 'local_emailclient'),
        get_string('settings:allowedimaphosts_desc', 'local_emailclient'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_emailclient/messagesperpage',
        get_string('settings:messagesperpage', 'local_emailclient'),
        get_string('settings:messagesperpage_desc', 'local_emailclient'),
        25,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_emailclient/connectiontimeout',
        get_string('settings:connectiontimeout', 'local_emailclient'),
        get_string('settings:connectiontimeout_desc', 'local_emailclient'),
        15,
        PARAM_INT
    ));

    // Default server settings – pre-fill the account form for new users.
    $settings->add(new admin_setting_heading(
        'local_emailclient/defaultsheading',
        get_string('settings:defaultsheading', 'local_emailclient'),
        get_string('settings:defaultsheading_desc', 'local_emailclient')
    ));

    $settings->add(new admin_setting_configtext(
        'local_emailclient/default_imaphost',
        get_string('settings:default_imaphost', 'local_emailclient'),
        get_string('settings:default_imaphost_desc', 'local_emailclient'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_emailclient/default_imapport',
        get_string('settings:default_imapport', 'local_emailclient'),
        get_string('settings:default_imapport_desc', 'local_emailclient'),
        993,
        PARAM_INT
    ));

    $securitychoices = [
        'ssl'  => get_string('account:security_ssl', 'local_emailclient'),
        'tls'  => get_string('account:security_tls', 'local_emailclient'),
        'none' => get_string('account:security_none', 'local_emailclient'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_emailclient/default_imapsecurity',
        get_string('settings:default_imapsecurity', 'local_emailclient'),
        '',
        'ssl',
        $securitychoices
    ));

    $settings->add(new admin_setting_configtext(
        'local_emailclient/default_smtphost',
        get_string('settings:default_smtphost', 'local_emailclient'),
        get_string('settings:default_smtphost_desc', 'local_emailclient'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_emailclient/default_smtpport',
        get_string('settings:default_smtpport', 'local_emailclient'),
        get_string('settings:default_smtpport_desc', 'local_emailclient'),
        587,
        PARAM_INT
    ));

    $smtpsecuritychoices = [
        'tls'  => get_string('account:security_tls', 'local_emailclient'),
        'ssl'  => get_string('account:security_ssl', 'local_emailclient'),
        'none' => get_string('account:security_none', 'local_emailclient'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_emailclient/default_smtpsecurity',
        get_string('settings:default_smtpsecurity', 'local_emailclient'),
        '',
        'tls',
        $smtpsecuritychoices
    ));
}
