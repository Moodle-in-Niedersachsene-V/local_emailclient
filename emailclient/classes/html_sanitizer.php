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

use DOMDocument;
use DOMElement;
use DOMXPath;

defined('MOODLE_INTERNAL') || die();

/**
 * A small, purpose-built HTML sanitizer for displaying e-mail message
 * bodies safely inside a Moodle page.
 *
 * This intentionally does NOT use Moodle's format_text()/HTMLPurifier
 * pipeline, because that is tuned for user-authored forum/assignment
 * content and is too aggressive for typical HTML e-mail (newsletter-style
 * markup, inline styles, etc.) while also not giving us the fine control
 * needed for the "block remote images by default" feature below.
 *
 * What it does:
 * - Removes <script>, <iframe>, <object>, <embed>, <link>, <meta>,
 *   <base>, <form>, <frame>, <frameset> and <applet> entirely.
 * - Strips all "on*" event handler attributes (onclick, onerror, ...).
 * - Strips javascript: URLs from href/src/action/formaction attributes
 *   and from style attributes.
 * - Replaces remote <img> sources with a transparent placeholder, keeping
 *   the original URL in a data-original-src attribute so the "Show
 *   external images" button can restore it client-side without another
 *   server round-trip.
 *
 * This is a reasonable, pragmatic mitigation for a self-hosted mail
 * client - it is NOT a guarantee against every possible XSS vector.
 * Sites with strict security requirements should consider integrating a
 * dedicated, actively-maintained HTML sanitizer library instead.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_sanitizer {

    /** @var string[] Tags removed entirely, including their content. */
    private const BLOCKED_TAGS = [
        'script', 'iframe', 'object', 'embed', 'link', 'meta',
        'base', 'form', 'frame', 'frameset', 'applet',
    ];

    /** @var string 1x1 transparent GIF used to replace blocked remote images. */
    private const TRANSPARENT_PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

    /** @var string CSS class added to blocked <img> elements. */
    public const BLOCKED_IMAGE_CLASS = 'emailclient-blocked-image';

    /**
     * Sanitizes an HTML e-mail body fragment for safe display.
     *
     * @param string $html
     * @return string Sanitized HTML fragment (no <html>/<body> wrapper).
     */
    public static function sanitize(string $html): string {
        if (trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
        $doc->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        self::strip_blocked_tags($doc);
        self::scrub_attributes($doc);

        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return '';
        }

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $doc->saveHTML($child);
        }

        return $result;
    }

    /**
     * Whether a sanitized fragment contains at least one blocked image
     * (used to decide whether to show the "Show external images" button).
     *
     * @param string $sanitizedhtml
     * @return bool
     */
    public static function has_blocked_images(string $sanitizedhtml): bool {
        return strpos($sanitizedhtml, self::BLOCKED_IMAGE_CLASS) !== false;
    }

    /**
     * Removes every instance of the blocked tags, with their content.
     *
     * @param DOMDocument $doc
     * @return void
     */
    private static function strip_blocked_tags(DOMDocument $doc): void {
        foreach (self::BLOCKED_TAGS as $tag) {
            $nodes = [];
            foreach ($doc->getElementsByTagName($tag) as $node) {
                $nodes[] = $node;
            }
            foreach ($nodes as $node) {
                if ($node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    /**
     * Removes event handler attributes, javascript: URLs, and blocks
     * remote image loading by default.
     *
     * @param DOMDocument $doc
     * @return void
     */
    private static function scrub_attributes(DOMDocument $doc): void {
        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query('//*');

        foreach ($nodes as $el) {
            if (!($el instanceof DOMElement)) {
                continue;
            }

            $attributes = [];
            foreach ($el->attributes as $attr) {
                $attributes[] = $attr;
            }

            foreach ($attributes as $attr) {
                $name = strtolower($attr->name);
                $value = $attr->value;

                if (strpos($name, 'on') === 0) {
                    $el->removeAttribute($attr->name);
                    continue;
                }
                if (in_array($name, ['href', 'src', 'action', 'formaction'], true)
                        && preg_match('/^\s*javascript\s*:/i', $value)) {
                    $el->removeAttribute($attr->name);
                    continue;
                }
                if ($name === 'style' && preg_match('/javascript\s*:/i', $value)) {
                    $el->removeAttribute('style');
                    continue;
                }
            }

            if (strtolower($el->tagName) === 'img') {
                $src = $el->getAttribute('src');
                if ($src !== '' && !preg_match('/^data:/i', $src)) {
                    $el->setAttribute('data-original-src', $src);
                    $el->setAttribute('src', self::TRANSPARENT_PIXEL);
                    $class = trim($el->getAttribute('class') . ' ' . self::BLOCKED_IMAGE_CLASS);
                    $el->setAttribute('class', $class);
                }
            }
        }
    }
}
