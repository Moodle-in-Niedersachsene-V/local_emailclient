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

namespace local_emailclient;

use stdClass;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Thin wrapper around PHP's ext-imap, scoped to a single user's account.
 *
 * Uses UID based addressing throughout so message references stay valid
 * even if the mailbox content changes between requests (Moodle's web
 * request model means a new connection is opened for every page load).
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class imap_client {

    /** @var resource|\IMAP\Connection|null */
    private $connection = null;

    /** @var stdClass Decrypted account record. */
    private stdClass $account;

    /** @var string Currently selected mailbox (raw IMAP name). */
    private string $mailbox = 'INBOX';

    /**
     * @param stdClass $account Decrypted account record (see account_manager::get_for_user()).
     */
    public function __construct(stdClass $account) {
        $this->account = $account;
    }

    // -----------------------------------------------------------------------
    // Cache helpers
    // -----------------------------------------------------------------------

    /**
     * Returns the current cache generation counter for this user+account.
     *
     * A generation counter is used instead of explicit key enumeration: every
     * write operation (delete, flag change, send) increments the counter,
     * which makes all previously stored cache entries stale without needing
     * to know their individual keys.
     *
     * @return int
     */
    private function get_cache_generation(): int {
        global $USER;
        $cache  = \cache::make('local_emailclient', 'folders');
        $genkey = 'gen_' . (int)$USER->id . '_' . md5($this->account->imaphost . $this->account->imapusername);
        return (int)($cache->get($genkey) ?: 0);
    }

    /**
     * Returns a cache key that is unique to this user, account and generation.
     *
     * @param string $suffix Differentiates folder vs message-list caches.
     * @return string
     */
    private function cache_key(string $suffix): string {
        global $USER;
        $gen = $this->get_cache_generation();
        return md5((int)$USER->id . '_' . $this->account->imaphost . '_' . $this->account->imapusername . '_' . $gen . '_' . $suffix);
    }

    /**
     * Invalidates all cached IMAP data for the current user's account by
     * incrementing the generation counter.
     *
     * Call this after any write operation: delete, flag change, or send.
     *
     * @return void
     */
    public function invalidate_cache(): void {
        global $USER;
        // Increment the generation counter stored in the folders cache.
        // All other cache keys embed this counter, so incrementing it
        // makes every previously cached entry stale in one operation.
        $foldercache = \cache::make('local_emailclient', 'folders');
        $genkey = 'gen_' . (int)$USER->id . '_' . md5($this->account->imaphost . $this->account->imapusername);
        $foldercache->set($genkey, $this->get_cache_generation() + 1);
        // Also purge message body cache so deleted/moved messages are not
        // served from cache after the operation.
        \cache::make('local_emailclient', 'message')->purge();
        \cache::make('local_emailclient', 'messagelist')->purge();
    }

    /**
     * Confirms ext-imap is available, throws a friendly error otherwise.
     *
     * @return void
     * @throws moodle_exception
     */
    public static function require_extension(): void {
        if (!function_exists('imap_open')) {
            throw new moodle_exception('error:imapextensionmissing', 'local_emailclient');
        }
    }

    /**
     * Builds the {host:port/imap/flags}folder mailbox string expected by imap_open().
     *
     * @param string $folder Raw (server-side) folder name, '' for the server root.
     * @return string
     */
    private function build_mailbox_string(string $folder = 'INBOX'): string {
        $flag = '';
        switch ($this->account->imapsecurity) {
            case 'ssl':
                $flag = '/ssl';
                break;
            case 'tls':
                $flag = '/tls';
                break;
            case 'none':
                $flag = '/notls';
                break;
        }
        $encoded = ($folder === '') ? '' : imap_utf7_encode($folder);
        return '{' . $this->account->imaphost . ':' . $this->account->imapport . '/imap' . $flag . '}' . $encoded;
    }

    /**
     * Opens a connection to the given folder.
     *
     * @param string $folder Raw folder name, defaults to INBOX.
     * @return void
     * @throws moodle_exception
     */
    public function open(string $folder = 'INBOX'): void {
        self::require_extension();

        $timeout = (int) (get_config('local_emailclient', 'connectiontimeout') ?: 15);
        @imap_timeout(IMAP_OPENTIMEOUT, $timeout);
        @imap_timeout(IMAP_READTIMEOUT, $timeout);

        $mailboxstring = $this->build_mailbox_string($folder);
        $conn = @imap_open($mailboxstring, $this->account->imapusername, $this->account->imappassword, 0, 1);

        if ($conn === false) {
            $error = imap_last_error();
            throw new moodle_exception('error:connectionfailed', 'local_emailclient', '', $error ?: 'unknown error');
        }

        $this->connection = $conn;
        $this->mailbox = $folder;
    }

    /**
     * Closes the connection if open.
     *
     * @return void
     */
    public function close(): void {
        if ($this->connection !== null) {
            @imap_close($this->connection);
            $this->connection = null;
        }
    }

    /**
     * Opens and immediately closes a connection, to validate credentials.
     *
     * @return void
     * @throws moodle_exception
     */
    public function test_connection(): void {
        $this->open('INBOX');
        $this->close();
    }

    /**
     * Lists all folders (mailboxes) with message/unread counts.
     *
     * @return stdClass[] Each with: name (decoded, display), rawname (server form),
     *                     delimiter, messages, unseen.
     * @throws moodle_exception
     */
    public function get_folders(): array {
        $cache    = \cache::make('local_emailclient', 'folders');
        $cachekey = $this->cache_key('folders');
        $cached   = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }

        if ($this->connection === null) {
            $this->open('INBOX');
        }

        $ref = $this->build_mailbox_string('');
        // Hetzner/Dovecot uses an INBOX.* sub-folder namespace:
        //   LIST "" "*"        → returns only INBOX
        //   LIST "" "INBOX.*"  → returns INBOX.Gesendet, INBOX.Papierkorb, …
        // We run both queries and merge, so the plugin works on both flat
        // (Gmail-style) and hierarchical (Dovecot-style) servers.
        $list1 = @imap_getmailboxes($this->connection, $ref, '*') ?: [];
        $list2 = @imap_getmailboxes($this->connection, $ref, 'INBOX.*') ?: [];
        // Also cover servers that use / as hierarchy delimiter (e.g. UW-IMAP).
        $list3 = @imap_getmailboxes($this->connection, $ref, 'INBOX/*') ?: [];
        $all = array_merge($list1, $list2, $list3);
        if (empty($all)) {
            $error = imap_last_error();
            throw new moodle_exception('error:folderlistfailed', 'local_emailclient', '', $error ?: 'unknown error');
        }
        // Deduplicate by full mailbox name (different queries may return the
        // same folder, e.g. INBOX appears in both list1 and list2).
        $seen = [];
        $deduped = [];
        foreach ($all as $item) {
            if (!isset($seen[$item->name])) {
                $seen[$item->name] = true;
                $deduped[] = $item;
            }
        }
        $list = $deduped;

        $folders = [];
        foreach ($list as $item) {
            // Strip the server prefix {host:port/flags} from the full mailbox
            // name. We use a regex rather than str_replace($ref, ...) because
            // some servers (e.g. Hetzner) return the prefix with different
            // capitalisation or minor formatting differences, causing
            // str_replace to produce an empty string or leave the prefix in.
            $rawname = preg_replace('/^\{[^}]+\}/', '', $item->name);
            // Skip the root entry (empty name) that some servers include.
            if ($rawname === '') {
                continue;
            }
            $decoded = imap_utf7_decode($rawname);
            // imap_utf7_decode can return false or empty on malformed input;
            // fall back to the raw name so there is always something to show.
            if ($decoded === false || $decoded === '') {
                $decoded = $rawname;
            }
            $status = @imap_status($this->connection, $item->name, SA_MESSAGES | SA_UNSEEN);
            $folders[] = (object) [
                'name'      => $decoded,
                'rawname'   => $rawname,
                'delimiter' => $item->delimiter,
                'messages'  => $status->messages ?? 0,
                'unseen'    => $status->unseen ?? 0,
            ];
        }

        $cache->set($cachekey, $folders);
        return $folders;
    }

    /**
     * Returns the raw folder name of the first folder matching one of the
     * given (case-insensitive) display names, or null if none is found.
     * Used to guess the Trash/Sent/Drafts folder across different servers.
     *
     * @param string[] $candidates
     * @return string|null
     */
    public function find_special_folder(array $candidates): ?string {
        foreach ($this->get_folders() as $folder) {
            foreach ($candidates as $cand) {
                if (strcasecmp($folder->name, $cand) === 0) {
                    return $folder->rawname;
                }
            }
        }
        return null;
    }

    /**
     * Fetches a page of message headers from a folder, newest first,
     * optionally filtered by a free-text search across subject/from/body.
     *
     * @param string $folder Raw folder name.
     * @param int $page 0-based page index.
     * @param int $perpage
     * @param string $search Free text, empty string for no filter.
     * @return stdClass {messages: stdClass[], total: int}
     * @throws moodle_exception
     */
    public function get_messages(string $folder, int $page, int $perpage, string $search = ''): stdClass {
        $cache    = \cache::make('local_emailclient', 'messagelist');
        $cachekey = $this->cache_key('msgs_' . md5($folder . '|' . $page . '|' . $perpage . '|' . $search));
        $cached   = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }

        $this->open($folder);

        $criteria = 'ALL';
        if (trim($search) !== '') {
            $term = str_replace('"', '', trim($search));
            $criteria = 'OR OR SUBJECT "' . $term . '" FROM "' . $term . '" BODY "' . $term . '"';
        }

        $uids = @imap_sort($this->connection, SORTDATE, 1, SE_UID, $criteria, 'UTF-8');
        if ($uids === false) {
            // Some servers reject the UTF-8 charset hint - retry without it.
            $uids = @imap_sort($this->connection, SORTDATE, 1, SE_UID, $criteria);
        }
        if ($uids === false) {
            $error = imap_last_error();
            throw new moodle_exception('error:messagelistfailed', 'local_emailclient', '', $error ?: 'unknown error');
        }

        $total = count($uids);
        $pageuids = array_slice($uids, $page * $perpage, $perpage);

        $messages = [];
        if (!empty($pageuids)) {
            $sequence = implode(',', $pageuids);
            $overviews = @imap_fetch_overview($this->connection, $sequence, FT_UID);
            $byuid = [];
            foreach ($overviews ?: [] as $ov) {
                $byuid[(int) $ov->uid] = $ov;
            }
            // Preserve the sorted (newest-first) order from imap_sort.
            foreach ($pageuids as $uid) {
                if (!isset($byuid[$uid])) {
                    continue;
                }
                $ov = $byuid[$uid];
                $messages[] = (object) [
                    'uid'         => (int) $uid,
                    'subject'     => $this->decode_mime_header($ov->subject ?? ''),
                    'from'        => $this->decode_mime_header($ov->from ?? ''),
                    'date'        => $ov->date ?? '',
                    'timestamp'   => isset($ov->udate) ? (int) $ov->udate : (strtotime($ov->date ?? '') ?: 0),
                    'size'        => (int) ($ov->size ?? 0),
                    'seen'        => !empty($ov->seen),
                    'flagged'     => !empty($ov->flagged),
                    'answered'    => !empty($ov->answered),
                ];
            }
        }

        $result = (object) ['messages' => $messages, 'total' => $total];
        $cache->set($cachekey, $result);
        return $result;
    }

    /**
     * Fetches a single message in full (headers, plain/html body, attachment list).
     *
     * @param string $folder Raw folder name.
     * @param int $uid
     * @return stdClass
     * @throws moodle_exception
     */
    public function get_message(string $folder, int $uid): stdClass {
        $cache    = \cache::make('local_emailclient', 'message');
        $cachekey = $this->cache_key('msg_' . md5($folder . '|' . $uid));
        $cached   = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }

        $this->open($folder);

        $msgno = @imap_msgno($this->connection, $uid);
        if (!$msgno) {
            throw new moodle_exception('error:invalidmessage', 'local_emailclient');
        }
        $headerinfo = @imap_headerinfo($this->connection, $msgno);
        $structure = @imap_fetchstructure($this->connection, $uid, FT_UID);
        if ($headerinfo === false || $structure === false) {
            throw new moodle_exception('error:invalidmessage', 'local_emailclient');
        }

        $parts = $this->flatten_parts($structure);

        $plain = '';
        $html = '';
        $attachments = [];

        foreach ($parts as $num => $part) {
            $type = (int) ($part->type ?? 0);
            $subtype = strtoupper($part->subtype ?? '');

            if ($this->is_attachment_part($part)) {
                $attachments[] = (object) [
                    'partnum'  => $num,
                    'filename' => $this->get_part_filename($part) ?? ('attachment_' . $num),
                    'mimetype' => strtolower($this->type_to_string($type) . '/' . ($subtype !== '' ? strtolower($subtype) : 'octet-stream')),
                    'size'     => (int) ($part->bytes ?? 0),
                ];
                continue;
            }

            if ($type === 0 && $subtype === 'PLAIN' && $plain === '') {
                $raw = imap_fetchbody($this->connection, $uid, $num, FT_UID);
                $plain = $this->decode_part_body($raw, $part);
            } else if ($type === 0 && $subtype === 'HTML' && $html === '') {
                $raw = imap_fetchbody($this->connection, $uid, $num, FT_UID);
                $html = $this->decode_part_body($raw, $part);
            }
        }

        $message = (object) [
            'uid'         => $uid,
            'folder'      => $folder,
            'subject'     => $this->decode_mime_header($headerinfo->subject ?? ''),
            'from'        => $this->format_addresses($headerinfo->from ?? []),
            'to'          => $this->format_addresses($headerinfo->to ?? []),
            'cc'          => $this->format_addresses($headerinfo->cc ?? []),
            'replyto'     => $this->format_addresses($headerinfo->reply_to ?? ($headerinfo->from ?? [])),
            'date'        => $headerinfo->date ?? '',
            'timestamp'   => isset($headerinfo->udate) ? (int) $headerinfo->udate : (strtotime($headerinfo->date ?? '') ?: 0),
            'messageid'   => $headerinfo->message_id ?? '',
            'plain'       => $plain,
            'html'        => $html,
            'attachments' => $attachments,
        ];
        $cache->set($cachekey, $message);
        return $message;
    }

    /**
     * Returns the raw decoded data of one attachment part.
     *
     * @param string $folder Raw folder name.
     * @param int $uid
     * @param string $partnum
     * @return stdClass {data, filename, mimetype}
     * @throws moodle_exception
     */
    public function fetch_attachment(string $folder, int $uid, string $partnum): stdClass {
        $this->open($folder);

        $structure = @imap_fetchstructure($this->connection, $uid, FT_UID);
        if ($structure === false) {
            throw new moodle_exception('error:invalidmessage', 'local_emailclient');
        }
        $parts = $this->flatten_parts($structure);
        if (!isset($parts[$partnum])) {
            throw new moodle_exception('error:invalidmessage', 'local_emailclient');
        }
        $part = $parts[$partnum];

        $raw = imap_fetchbody($this->connection, $uid, $partnum, FT_UID);
        switch ((int) ($part->encoding ?? 0)) {
            case 3: // BASE64.
                $raw = base64_decode($raw);
                break;
            case 4: // QUOTED-PRINTABLE.
                $raw = quoted_printable_decode($raw);
                break;
        }

        $subtype = strtolower($part->subtype ?? 'octet-stream');
        return (object) [
            'data'     => $raw,
            'filename' => $this->get_part_filename($part) ?? 'attachment',
            'mimetype' => strtolower($this->type_to_string((int) ($part->type ?? 0))) . '/' . $subtype,
        ];
    }

    /**
     * Sets or clears an IMAP flag (e.g. '\\Seen', '\\Flagged') on a message.
     *
     * @param string $folder Raw folder name.
     * @param int $uid
     * @param string $flag
     * @param bool $set
     * @return void
     */
    public function set_flag(string $folder, int $uid, string $flag, bool $set): void {
        $this->open($folder);
        if ($set) {
            @imap_setflag_full($this->connection, (string) $uid, $flag, ST_UID);
        } else {
            @imap_clearflag_full($this->connection, (string) $uid, $flag, ST_UID);
        }
        $this->invalidate_cache();
    }

    /**
     * Deletes a message: moves it to the Trash folder if one can be found
     * and we are not already there, otherwise marks it \Deleted and expunges.
     *
     * @param string $folder Raw folder name the message currently lives in.
     * @param int $uid
     * @return bool
     */
    public function delete_message(string $folder, int $uid): bool {
        $this->open($folder);

        $trash = $this->find_special_folder(['Trash', 'Papierkorb', 'Deleted Items', 'Deleted Messages']);
        if ($trash !== null && strcasecmp($trash, $folder) !== 0) {
            $ok = @imap_mail_move($this->connection, (string) $uid, $trash, CP_UID);
            if ($ok) {
                $this->invalidate_cache();
                return true;
            }
        }

        @imap_setflag_full($this->connection, (string) $uid, '\\Deleted', ST_UID);
        $result = (bool) @imap_expunge($this->connection);
        $this->invalidate_cache();
        return $result;
    }

    /**
     * Recursively flattens a BODYSTRUCTURE tree into a flat map of
     * IMAP part-number (e.g. "2.1") to the part object.
     *
     * @param stdClass $structure
     * @param string $prefix
     * @return array<string, stdClass>
     */
    private function flatten_parts(stdClass $structure, string $prefix = ''): array {
        $result = [];

        if (!empty($structure->parts)) {
            foreach ($structure->parts as $idx => $part) {
                $num = $prefix === '' ? (string) ($idx + 1) : $prefix . '.' . ($idx + 1);
                if ((int) ($structure->type ?? 0) === 1 && !empty($part->parts)) {
                    // Nested multipart container - recurse, do not count
                    // the container itself as a leaf part.
                    $result += $this->flatten_parts($part, $num);
                } else {
                    $result[$num] = $part;
                }
            }
        } else {
            // Non-multipart message: the whole body is "part 1".
            $result['1'] = $structure;
        }

        return $result;
    }

    /**
     * Whether a structure part should be treated as a downloadable attachment.
     *
     * @param stdClass $part
     * @return bool
     */
    private function is_attachment_part(stdClass $part): bool {
        $filename = $this->get_part_filename($part);
        $disposition = $part->disposition ?? '';

        if (strcasecmp($disposition, 'attachment') === 0) {
            return true;
        }
        if ($filename !== null && strcasecmp($disposition, 'inline') !== 0) {
            // Has a filename and is not explicitly marked inline -> attachment.
            // (Covers the common case of clients omitting Content-Disposition.)
            $type = (int) ($part->type ?? 0);
            if ($type !== 0) {
                return true; // Non-text part with a filename.
            }
            // A text part with a filename that is not the primary plain/html body.
            $subtype = strtoupper($part->subtype ?? '');
            if (!in_array($subtype, ['PLAIN', 'HTML'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts the filename parameter (RFC 2231/2047 aware) from a structure part.
     *
     * @param stdClass $part
     * @return string|null
     */
    private function get_part_filename(stdClass $part): ?string {
        foreach (['dparameters', 'parameters'] as $propname) {
            if (empty($part->$propname)) {
                continue;
            }
            foreach ($part->$propname as $param) {
                if (in_array(strtolower($param->attribute), ['filename', 'name'])) {
                    return $this->decode_mime_header($param->value);
                }
            }
        }
        return null;
    }

    /**
     * Charset parameter of a structure part, if any.
     *
     * @param stdClass $part
     * @return string|null
     */
    private function get_part_charset(stdClass $part): ?string {
        if (!empty($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strcasecmp($param->attribute, 'charset') === 0) {
                    return $param->value;
                }
            }
        }
        return null;
    }

    /**
     * Decodes a fetched body chunk according to its transfer encoding and charset.
     *
     * @param string $raw
     * @param stdClass $part
     * @return string UTF-8 text.
     */
    private function decode_part_body(string $raw, stdClass $part): string {
        switch ((int) ($part->encoding ?? 0)) {
            case 3: // BASE64.
                $raw = base64_decode($raw);
                break;
            case 4: // QUOTED-PRINTABLE.
                $raw = quoted_printable_decode($raw);
                break;
        }

        $charset = $this->get_part_charset($part);
        if ($charset && strcasecmp($charset, 'UTF-8') !== 0) {
            $converted = @iconv($charset, 'UTF-8//IGNORE', $raw);
            if ($converted !== false) {
                $raw = $converted;
            }
        }

        return $raw;
    }

    /**
     * Decodes an RFC 2047 encoded-word header value (e.g. Subject) to UTF-8.
     *
     * @param string|null $value
     * @return string
     */
    private function decode_mime_header(?string $value): string {
        if ($value === null || $value === '') {
            return '';
        }
        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded !== false) {
                return $decoded;
            }
        }
        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($value);
        }
        return $value;
    }

    /**
     * Formats an array of imap_headerinfo address objects into a display string.
     *
     * @param array $addresses
     * @return string
     */
    private function format_addresses(array $addresses): string {
        $parts = [];
        foreach ($addresses as $addr) {
            $email = ($addr->mailbox ?? '') . '@' . ($addr->host ?? '');
            $personal = isset($addr->personal) ? $this->decode_mime_header($addr->personal) : '';
            $parts[] = $personal !== '' ? "{$personal} <{$email}>" : $email;
        }
        return implode(', ', $parts);
    }

    /**
     * Maps the numeric IMAP body type constant to a MIME top-level type string.
     *
     * @param int $type
     * @return string
     */
    private function type_to_string(int $type): string {
        $map = [
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            7 => 'application',
        ];
        return $map[$type] ?? 'application';
    }
}
