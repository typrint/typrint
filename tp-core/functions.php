<?php

declare(strict_types=1);

/*
 * This file is part of TyPrint.
 *
 * (c) TyPrint Core Team <https://typrint.org>
 *
 * This source file is subject to the GNU General Public License version 3
 * that is with this source code in the file LICENSE.
 */

use TP\Facades\Hook;
use TP\Formatting\Formatting;
use TP\L10n\L10n;

/**
 * Converts float number to format based on the locale.
 *
 * @param float $number   the number to convert based on locale
 * @param int   $decimals Optional. Precision of the number of decimal places. Default 0.
 *
 * @return string converted number in string format
 *
 * @since 1.0.0
 */
function number_format_i18n(float $number, int $decimals = 0): string
{
    $formatted = number_format($number, abs($decimals));

    /**
     * Filters the number formatted based on the locale.
     *
     * @param string $formatted converted number in string format
     * @param float  $number    the number to convert based on locale
     * @param int    $decimals  precision of the number of decimal places
     *
     * @since 1.0.0
     */
    return Hook::applyFilter('number_format_i18n', $formatted, $number, $decimals);
}

/**
 * Converts a number of bytes to the largest unit the bytes will fit into.
 *
 * It is easier to read 1 KB than 1024 bytes and 1 MB than 1048576 bytes. Converts
 * number of bytes to human readable number by taking the number of that unit
 * that the bytes will go into it. Supports YB value.
 *
 * Please note that integers in PHP are limited to 32 bits, unless they are on
 * 64 bit architecture, then they have 64 bit size. If you need to place the
 * larger size then what PHP integer type will hold, then use a string. It will
 * be converted to a double, which should always have 64 bit length.
 *
 * Technically the correct unit names for powers of 1024 are KiB, MiB etc.
 *
 * @param int|string $bytes    Number of bytes. Note max integer size for integers.
 * @param int        $decimals Optional. Precision of number of decimal places. Default 0.
 *
 * @return string|false number string on success, false on failure
 *
 * @since 1.0.0
 */
function size_format(int|string $bytes, int $decimals = 0): false|string
{
    $quant = [
        /* translators: Unit symbol for yottabyte. */
        L10n::_x('YB', 'unit symbol') => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
        /* translators: Unit symbol for zettabyte. */
        L10n::_x('ZB', 'unit symbol') => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
        /* translators: Unit symbol for exabyte. */
        L10n::_x('EB', 'unit symbol') => 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
        /* translators: Unit symbol for petabyte. */
        L10n::_x('PB', 'unit symbol') => 1024 * 1024 * 1024 * 1024 * 1024,
        /* translators: Unit symbol for terabyte. */
        L10n::_x('TB', 'unit symbol') => 1024 * 1024 * 1024 * 1024,
        /* translators: Unit symbol for gigabyte. */
        L10n::_x('GB', 'unit symbol') => 1024 * 1024 * 1024,
        /* translators: Unit symbol for megabyte. */
        L10n::_x('MB', 'unit symbol') => 1024 * 1024,
        /* translators: Unit symbol for kilobyte. */
        L10n::_x('KB', 'unit symbol') => 1024,
        /* translators: Unit symbol for byte. */
        L10n::_x('B', 'unit symbol') => 1,
    ];

    if (0 === $bytes) {
        /* translators: Unit symbol for byte. */
        return number_format_i18n(0, $decimals).' '.L10n::_x('B', 'unit symbol');
    }

    foreach ($quant as $unit => $mag) {
        if ((float) $bytes >= $mag) {
            return number_format_i18n($bytes / $mag, $decimals).' '.$unit;
        }
    }

    return false;
}

/**
 * Builds URL query based on an associative and, or indexed array.
 *
 * This is a convenient function for easily building url queries. It sets the
 * separator to '&' and uses _http_build_query() function.
 *
 * @param array $data URL-encode key/value pairs
 *
 * @return string URL-encoded string
 *
 * @since 1.0.0
 * @see _http_build_query() Used to build the query
 * @see https://www.php.net/manual/en/function.http-build-query.php for more on what
 *       http_build_query() does.
 */
function build_query(array $data): string
{
    return _http_build_query($data, null, '&', '', false);
}

/**
 * From php.net (modified by Mark Jaquith to behave like the native PHP5 function).
 *
 * @param object|array $data      An array or object of data. Converted to array.
 * @param string|null  $prefix    Optional. Numeric index. If set, start parameter numbering with it.
 *                                Default null.
 * @param string|null  $sep       Optional. Argument separator; defaults to 'arg_separator.output'.
 *                                Default null.
 * @param string       $key       Optional. Used to prefix key name. Default empty string.
 * @param bool         $urlencode Optional. Whether to use urlencode() in the result. Default true.
 *
 * @return string the query string
 *
 * @since 1.0.0
 * @see https://www.php.net/manual/en/function.http-build-query.php
 */
function _http_build_query(object|array $data, ?string $prefix = null, ?string $sep = null, string $key = '', bool $urlencode = true): string
{
    $ret = [];

    foreach ((array) $data as $k => $v) {
        if ($urlencode) {
            $k = urlencode($k);
        }

        if (is_int($k) && null !== $prefix) {
            $k = $prefix.$k;
        }

        if (!empty($key)) {
            $k = $key.'%5B'.$k.'%5D';
        }

        if (null === $v) {
            continue;
        } elseif (false === $v) {
            $v = '0';
        }

        if (is_array($v) || is_object($v)) {
            $ret[] = _http_build_query($v, '', $sep, $k, $urlencode);
        } elseif ($urlencode) {
            $ret[] = $k.'='.urlencode($v);
        } else {
            $ret[] = $k.'='.$v;
        }
    }

    if (null === $sep) {
        $sep = ini_get('arg_separator.output');
    }

    return implode($sep, $ret);
}

/**
 * Retrieves a modified URL query string.
 *
 * You can rebuild the URL and append query variables to the URL query by using this function.
 * There are two ways to use this function; either a single key and value, or an associative array.
 *
 * Using a single key and value:
 *
 *     add_query_arg( 'key', 'value', 'http://example.com' );
 *
 * Using an associative array:
 *
 *     add_query_arg( array(
 *         'key1' => 'value1',
 *         'key2' => 'value2',
 *     ), 'http://example.com' );
 *
 *
 * Values are expected to be encoded appropriately with urlencode() or rawurlencode().
 *
 * Setting any query variable's value to boolean false removes the key (see remove_query_arg()).
 *
 * Important: The return value of add_query_arg() is not escaped by default. Output should be
 * late-escaped with esc_url() or similar to help prevent vulnerability to cross-site scripting
 * (XSS) attacks.
 *
 * @param array $args
 *
 * @return string new URL query string (unescaped)
 *
 * @since 1.0.0
 */
function add_query_arg(...$args): string
{
    if (is_array($args[0])) {
        $uri = $args[1];
    } else {
        $uri = $args[2];
    }

    $frag = strstr($uri, '#');
    if ($frag) {
        $uri = substr($uri, 0, -strlen($frag));
    } else {
        $frag = '';
    }

    if (str_starts_with($uri, 'http://')) {
        $protocol = 'http://';
        $uri = substr($uri, 7);
    } elseif (str_starts_with($uri, 'https://')) {
        $protocol = 'https://';
        $uri = substr($uri, 8);
    } else {
        $protocol = '';
    }

    if (str_contains($uri, '?')) {
        [$base, $query] = explode('?', $uri, 2);
        $base .= '?';
    } elseif ($protocol || !str_contains($uri, '=')) {
        $base = $uri.'?';
        $query = '';
    } else {
        $base = '';
        $query = $uri;
    }

    $qs = [];
    Formatting::tp_parse_str($query, $qs);
    $qs = Formatting::urlencode_deep($qs); // This re-URL-encodes things that were already in the query string.
    if (is_array($args[0])) {
        foreach ($args[0] as $k => $v) {
            $qs[$k] = $v;
        }
    } else {
        $qs[$args[0]] = $args[1];
    }

    foreach ($qs as $k => $v) {
        if (false === $v) {
            unset($qs[$k]);
        }
    }

    $ret = build_query($qs);
    $ret = trim($ret, '?');
    $ret = preg_replace('#=(&|$)#', '$1', $ret);
    $ret = $protocol.$base.$ret.$frag;
    $ret = rtrim($ret, '?');
    $ret = str_replace('?#', '#', $ret);

    return $ret;
}

/**
 * Retrieves the file type based on the extension name.
 *
 * @param string $ext the extension to search
 *
 * @return string|void the file type, example: audio, video, document, spreadsheet, etc
 *
 * @since 1.0.0
 */
function tp_ext2type(string $ext)
{
    $ext = strtolower($ext);

    $ext2type = tp_get_ext_types();
    foreach ($ext2type as $type => $exts) {
        if (in_array($ext, $exts, true)) {
            return $type;
        }
    }
}

/**
 * Returns first matched extension for the mime-type,
 * as mapped from tp_get_mime_types().
 *
 * @since 1.0.0
 */
function tp_get_default_extension_for_mime_type(string $mime_type): false|string
{
    $extensions = explode('|', array_search($mime_type, tp_get_mime_types(), true));

    if (empty($extensions[0])) {
        return false;
    }

    return $extensions[0];
}

/**
 * Retrieves the file type from the file name.
 *
 * You can optionally define the mime array, if needed.
 *
 * @param string        $filename file name or path
 * @param string[]|null $mimes    Optional. Array of allowed mime types keyed by their file extension regex.
 *                                Defaults to the result of get_allowed_mime_types().
 *
 * @return array {
 *               Values for the extension and mime type
 *
 * @since 1.0.0
 *
 * @var string|false $ext  file extension, or false if the file doesn't match a mime type
 *
 * @type string|false $type File mime type, or false if the file doesn't match a mime type.
 *                    }
 */
function tp_check_filetype(string $filename, ?array $mimes = null): array
{
    if (empty($mimes)) {
        $mimes = get_allowed_mime_types();
    }
    $type = false;
    $ext = false;

    foreach ($mimes as $ext_preg => $mime_match) {
        $ext_preg = '!\.('.$ext_preg.')$!i';
        if (preg_match($ext_preg, $filename, $ext_matches)) {
            $type = $mime_match;
            $ext = $ext_matches[1];
            break;
        }
    }

    return compact('ext', 'type');
}

/**
 * Attempts to determine the real file type of a file.
 *
 * If unable to, the file name extension will be used to determine type.
 *
 * If it's determined that the extension does not match the file's real type,
 * then the "proper_filename" value will be set with a proper filename and extension.
 *
 * Currently this function only supports renaming images validated via tp_get_image_mime().
 *
 * @param string        $file     full path to the file
 * @param string        $filename the name of the file (may differ from $file due to $file being
 *                                in a tmp directory)
 * @param string[]|null $mimes    Optional. Array of allowed mime types keyed by their file extension regex.
 *                                Defaults to the result of get_allowed_mime_types().
 *
 * @return array {
 *               Values for the extension, mime type, and corrected filename
 *
 * @since 1.0.0
 *
 * @type string|false $ext             File extension, or false if the file doesn't match a mime type.
 * @type string|false $type            File mime type, or false if the file doesn't match a mime type.
 *
 * @var string|false $proper_filename File name with its correct extension, or false if it cannot be determined.
 *                   }
 */
function tp_check_filetype_and_ext(string $file, string $filename, ?array $mimes = null): array
{
    $proper_filename = false;

    // Do basic extension validation and MIME mapping.
    $tp_filetype = tp_check_filetype($filename, $mimes);
    $ext = $tp_filetype['ext'];
    $type = $tp_filetype['type'];

    // We can't do any further validation without a file to work with.
    if (!file_exists($file)) {
        return compact('ext', 'type', 'proper_filename');
    }

    $real_mime = false;

    // Validate image types.
    if ($type && str_starts_with($type, 'image/')) {
        // Attempt to figure out what type of image it actually is.
        $real_mime = tp_get_image_mime($file);

        $heic_images_extensions = [
            'heif',
            'heics',
            'heifs',
        ];

        if ($real_mime && ($real_mime !== $type || in_array($ext, $heic_images_extensions, true))) {
            /**
             * Filters the list mapping image mime types to their respective extensions.
             *
             * @param array $mime_to_ext array of image mime types and their matching extensions
             *
             * @since 1.0.0
             */
            $mime_to_ext = Hook::applyFilter(
                'getimagesize_mimes_to_exts',
                [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/bmp' => 'bmp',
                    'image/tiff' => 'tif',
                    'image/webp' => 'webp',
                    'image/avif' => 'avif',

                    /*
                     * In theory there are/should be file extensions that correspond to the
                     * mime types: .heif, .heics and .heifs. However it seems that HEIC images
                     * with any of the mime types commonly have a .heic file extension.
                     * Seems keeping the status quo here is best for compatibility.
                     */
                    'image/heic' => 'heic',
                    'image/heif' => 'heic',
                    'image/heic-sequence' => 'heic',
                    'image/heif-sequence' => 'heic',
                ]
            );

            // Replace whatever is after the last period in the filename with the correct extension.
            if (!empty($mime_to_ext[$real_mime])) {
                $filename_parts = explode('.', $filename);

                array_pop($filename_parts);
                $filename_parts[] = $mime_to_ext[$real_mime];
                $new_filename = implode('.', $filename_parts);

                if ($new_filename !== $filename) {
                    $proper_filename = $new_filename; // Mark that it changed.
                }

                // Redefine the extension / MIME.
                $tp_filetype = tp_check_filetype($new_filename, $mimes);
                $ext = $tp_filetype['ext'];
                $type = $tp_filetype['type'];
            } else {
                // Reset $real_mime and try validating again.
                $real_mime = false;
            }
        }
    }

    // Validate files that didn't get validated during previous checks.
    if ($type && !$real_mime) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_mime = finfo_file($finfo, $file);
        finfo_close($finfo);

        // fileinfo often misidentifies obscure files as one of these types.
        $nonspecific_types = [
            'application/octet-stream',
            'application/encrypted',
            'application/CDFV2-encrypted',
            'application/zip',
        ];

        /*
         * If $real_mime doesn't match the content type we're expecting from the file's extension,
         * we need to do some additional vetting. Media types and those listed in $nonspecific_types are
         * allowed some leeway, but anything else must exactly match the real content type.
         */
        if (in_array($real_mime, $nonspecific_types, true)) {
            // File is a non-specific binary type. That's ok if it's a type that generally tends to be binary.
            if (!in_array(substr($type, 0, strcspn($type, '/')), ['application', 'video', 'audio'], true)) {
                $type = false;
                $ext = false;
            }
        } elseif (str_starts_with($real_mime, 'video/') || str_starts_with($real_mime, 'audio/')) {
            /*
             * For these types, only the major type must match the real value.
             * This means that common mismatches are forgiven: application/vnd.apple.numbers is often misidentified as application/zip,
             * and some media files are commonly named with the wrong extension (.mov instead of .mp4)
             */
            if (substr($real_mime, 0, strcspn($real_mime, '/')) !== substr($type, 0, strcspn($type, '/'))) {
                $type = false;
                $ext = false;
            }
        } elseif ('text/plain' === $real_mime) {
            // A few common file types are occasionally detected as text/plain; allow those.
            if (!in_array(
                $type,
                [
                    'text/plain',
                    'text/csv',
                    'application/csv',
                    'text/richtext',
                    'text/tsv',
                    'text/vtt',
                ],
                true
            )
            ) {
                $type = false;
                $ext = false;
            }
        } elseif ('application/csv' === $real_mime) {
            // Special casing for CSV files.
            if (!in_array(
                $type,
                [
                    'text/csv',
                    'text/plain',
                    'application/csv',
                ],
                true
            )
            ) {
                $type = false;
                $ext = false;
            }
        } elseif ('text/rtf' === $real_mime) {
            // Special casing for RTF files.
            if (!in_array(
                $type,
                [
                    'text/rtf',
                    'text/plain',
                    'application/rtf',
                ],
                true
            )
            ) {
                $type = false;
                $ext = false;
            }
        } else {
            if ($type !== $real_mime) {
                /*
                 * Everything else including image/* and application/*:
                 * If the real content type doesn't match the file extension, assume it's dangerous.
                 */
                $type = false;
                $ext = false;
            }
        }
    }

    // The mime type must be allowed.
    if ($type) {
        $allowed = get_allowed_mime_types();

        if (!in_array($type, $allowed, true)) {
            $type = false;
            $ext = false;
        }
    }

    /**
     * Filters the "real" file type of the given file.
     *
     * @param array{
     *     ext: string|false,
     *     type: string|false,
     *     proper_filename: string|false
     * } $tp_check_filetype_and_ext Values for the extension, mime type, and corrected filename
     * @param string        $file      full path to the file
     * @param string        $filename  the name of the file (may differ from $file due to
     *                                 $file being in a tmp directory)
     * @param string[]|null $mimes     array of mime types keyed by their file extension regex, or null if
     *                                 none were provided
     * @param string|false  $real_mime the actual mime type or false if the type cannot be determined
     *
     * @since 1.0.0
     */
    return Hook::applyFilter('tp_check_filetype_and_ext', compact('ext', 'type', 'proper_filename'), $file, $filename, $mimes, $real_mime);
}

/**
 * Retrieves the list of allowed mime types and file extensions.
 *
 * @return string[] array of mime types keyed by the file extension regex corresponding
 *                  to those types
 *
 * @since 1.0.0
 */
function get_allowed_mime_types(): array
{
    $t = tp_get_mime_types();

    unset($t['swf'], $t['exe']);
    unset($t['htm|html'], $t['js']);

    /*
     * Filters the list of allowed mime types and file extensions.
     *
     * @since 1.0.0
     *
     * @param array $t Mime types keyed by the file extension regex corresponding to those types.
     */
    return Hook::applyFilter('upload_mimes', $t);
}

/**
 * Returns the real mime type of an image file.
 *
 * This depends on exif_imagetype() or getimagesize() to determine real mime types.
 *
 * @param string $file full path to the file
 *
 * @return string|false the actual mime type or false if the type cannot be determined
 *
 * @since 1.0.0
 */
function tp_get_image_mime(string $file): false|string
{
    $imagetype = exif_imagetype($file);

    return ($imagetype) ? image_type_to_mime_type($imagetype) : false;
}

/**
 * Checks if a mime type is for a HEIC/HEIF image.
 *
 * @param string $mime_type the mime type to check
 *
 * @return bool whether the mime type is for a HEIC/HEIF image
 *
 * @since 1.0.0
 */
function tp_is_heic_image_mime_type(string $mime_type): bool
{
    $heic_mime_types = [
        'image/heic',
        'image/heif',
        'image/heic-sequence',
        'image/heif-sequence',
    ];

    return in_array($mime_type, $heic_mime_types, true);
}

/**
 * Retrieves the list of mime types and file extensions.
 *
 * @return string[] array of mime types keyed by the file extension regex corresponding to those types
 *
 * @since 1.0.0
 */
function tp_get_mime_types(): array
{
    /*
     * Filters the list of mime types and file extensions.
     *
     * This filter should be used to add, not remove, mime types. To remove
     * mime types, use the {@see 'upload_mimes'} filter.
     *
     * @since 1.0.0
     *
     * @param string[] $tp_get_mime_types Mime types keyed by the file extension regex
     *                                    corresponding to those types.
     */
    return Hook::applyFilter(
        'mime_types',
        [
            // Image formats.
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'tiff|tif' => 'image/tiff',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'ico' => 'image/x-icon',

            // TODO: Needs improvement. All images with the following mime types seem to have .heic file extension.
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            'heics' => 'image/heic-sequence',
            'heifs' => 'image/heif-sequence',

            // Video formats.
            'asf|asx' => 'video/x-ms-asf',
            'wmv' => 'video/x-ms-wmv',
            'wmx' => 'video/x-ms-wmx',
            'wm' => 'video/x-ms-wm',
            'avi' => 'video/avi',
            'divx' => 'video/divx',
            'flv' => 'video/x-flv',
            'mov|qt' => 'video/quicktime',
            'mpeg|mpg|mpe' => 'video/mpeg',
            'mp4|m4v' => 'video/mp4',
            'ogv' => 'video/ogg',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            '3gp|3gpp' => 'video/3gpp',  // Can also be audio.
            '3g2|3gp2' => 'video/3gpp2', // Can also be audio.
            // Text formats.
            'txt|asc|c|cc|h|srt' => 'text/plain',
            'csv' => 'text/csv',
            'tsv' => 'text/tab-separated-values',
            'ics' => 'text/calendar',
            'rtx' => 'text/richtext',
            'css' => 'text/css',
            'htm|html' => 'text/html',
            'vtt' => 'text/vtt',
            'dfxp' => 'application/ttaf+xml',
            // Audio formats.
            'mp3|m4a|m4b' => 'audio/mpeg',
            'aac' => 'audio/aac',
            'ra|ram' => 'audio/x-realaudio',
            'wav|x-wav' => 'audio/wav',
            'ogg|oga' => 'audio/ogg',
            'flac' => 'audio/flac',
            'mid|midi' => 'audio/midi',
            'wma' => 'audio/x-ms-wma',
            'wax' => 'audio/x-ms-wax',
            'mka' => 'audio/x-matroska',
            // Misc application formats.
            'rtf' => 'application/rtf',
            'js' => 'application/javascript',
            'pdf' => 'application/pdf',
            'swf' => 'application/x-shockwave-flash',
            'class' => 'application/java',
            'tar' => 'application/x-tar',
            'zip' => 'application/zip',
            'gz|gzip' => 'application/x-gzip',
            'rar' => 'application/rar',
            '7z' => 'application/x-7z-compressed',
            'exe' => 'application/x-msdownload',
            'psd' => 'application/octet-stream',
            'xcf' => 'application/octet-stream',
            // MS Office formats.
            'doc' => 'application/msword',
            'pot|pps|ppt' => 'application/vnd.ms-powerpoint',
            'wri' => 'application/vnd.ms-write',
            'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
            'mdb' => 'application/vnd.ms-access',
            'mpp' => 'application/vnd.ms-project',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
            'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',
            'oxps' => 'application/oxps',
            'xps' => 'application/vnd.ms-xpsdocument',
            // OpenOffice formats.
            'odt' => 'application/vnd.oasis.opendocument.text',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'odc' => 'application/vnd.oasis.opendocument.chart',
            'odb' => 'application/vnd.oasis.opendocument.database',
            'odf' => 'application/vnd.oasis.opendocument.formula',
            // WordPerfect formats.
            'wp|wpd' => 'application/wordperfect',
            // iWork formats.
            'key' => 'application/vnd.apple.keynote',
            'numbers' => 'application/vnd.apple.numbers',
            'pages' => 'application/vnd.apple.pages',
        ]
    );
}

/**
 * Retrieves the list of common file extensions and their types.
 *
 * @return array[] multi-dimensional array of file extensions types keyed by the type of file
 *
 * @since 1.0.0
 */
function tp_get_ext_types(): array
{
    /*
     * Filters file type based on the extension name.
     *
     * @since 1.0.0
     *
     * @see tp_ext2type()
     *
     * @param array[] $ext2type Multi-dimensional array of file extensions types keyed by the type of file.
     */
    return Hook::applyFilter(
        'ext_types',
        [
            'image' => ['jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'heif', 'webp', 'avif'],
            'audio' => ['aac', 'ac3', 'aif', 'aiff', 'flac', 'm3a', 'm4a', 'm4b', 'mka', 'mp1', 'mp2', 'mp3', 'ogg', 'oga', 'ram', 'wav', 'wma'],
            'video' => ['3g2', '3gp', '3gpp', 'asf', 'avi', 'divx', 'dv', 'flv', 'm4v', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'mpv', 'ogm', 'ogv', 'qt', 'rm', 'vob', 'wmv'],
            'document' => ['doc', 'docx', 'docm', 'dotm', 'odt', 'pages', 'pdf', 'xps', 'oxps', 'rtf', 'wp', 'wpd', 'psd', 'xcf'],
            'spreadsheet' => ['numbers', 'ods', 'xls', 'xlsx', 'xlsm', 'xlsb'],
            'interactive' => ['swf', 'key', 'ppt', 'pptx', 'pptm', 'pps', 'ppsx', 'ppsm', 'sldx', 'sldm', 'odp'],
            'text' => ['asc', 'csv', 'tsv', 'txt'],
            'archive' => ['bz2', 'cab', 'dmg', 'gz', 'rar', 'sea', 'sit', 'sqx', 'tar', 'tgz', 'zip', '7z'],
            'code' => ['css', 'htm', 'html', 'php', 'js'],
        ]
    );
}

/**
 * Marks a function as deprecated and inform when it has been used.
 *
 * There is a {@see 'deprecated_function_run'} hook that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated function.
 *
 * The current behavior is to trigger a user error if `TP_DEBUG` is true.
 *
 * This function is to be used in every function that is deprecated.
 *
 * @param string $function_name the function that was called
 * @param string $version       the version of TyPrint that deprecated the function
 * @param string $replacement   Optional. The function that should have been called. Default empty string.
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function tp_deprecated_function(string $function_name, string $version, string $replacement = ''): void
{
    /*
     * Fires when a deprecated function is called.
     *
     * @since 1.0.0
     *
     * @param string $function_name The function that was called.
     * @param string $replacement   The function that should have been called.
     * @param string $version       The version of TyPrint that deprecated the function.
     */
    Hook::doAction('deprecated_function_run', $function_name, $replacement, $version);

    /*
     * Filters whether to trigger an error for deprecated functions.
     *
     * @since 1.0.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
     */
    if (TP_DEBUG && Hook::applyFilter('deprecated_function_trigger_error', true)) {
        if (function_exists('__')) {
            if ($replacement) {
                $message = sprintf(
                    /* translators: 1: PHP function name, 2: Version number, 3: Alternative function name. */
                    __('Function %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'),
                    $function_name,
                    $version,
                    $replacement
                );
            } else {
                $message = sprintf(
                    /* translators: 1: PHP function name, 2: Version number. */
                    __('Function %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'),
                    $function_name,
                    $version
                );
            }
        } else {
            if ($replacement) {
                $message = sprintf(
                    'Function %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
                    $function_name,
                    $version,
                    $replacement
                );
            } else {
                $message = sprintf(
                    'Function %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $function_name,
                    $version
                );
            }
        }

        tp_trigger_error('', $message, E_USER_DEPRECATED);
    }
}

/**
 * Marks a constructor as deprecated and informs when it has been used.
 *
 * Similar to tp_deprecated_function(), but with different strings. Used to
 * remove PHP4-style constructors.
 *
 * The current behavior is to trigger a user error if `TP_DEBUG` is true.
 *
 * This function is to be used in every PHP4-style constructor method that is deprecated.
 *
 * @param string $class_name   the class containing the deprecated constructor
 * @param string $version      the version of TyPrint that deprecated the function
 * @param string $parent_class Optional. The parent class calling the deprecated constructor.
 *                             Default empty string.
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function tp_deprecated_constructor(string $class_name, string $version, string $parent_class = ''): void
{
    /**
     * Fires when a deprecated constructor is called.
     *
     * @param string $class_name   the class containing the deprecated constructor
     * @param string $version      the version of TyPrint that deprecated the function
     * @param string $parent_class the parent class calling the deprecated constructor
     *
     * @since 1.0.0
     */
    Hook::doAction('deprecated_constructor_run', $class_name, $version, $parent_class);

    /*
     * Filters whether to trigger an error for deprecated functions.
     *
     * `TP_DEBUG` must be true in addition to the filter evaluating to true.
     *
     * @since 1.0.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
     */
    if (TP_DEBUG && Hook::applyFilter('deprecated_constructor_trigger_error', true)) {
        if (function_exists('__')) {
            if ($parent_class) {
                $message = sprintf(
                    /* translators: 1: PHP class name, 2: PHP parent class name, 3: Version number, 4: __construct() method. */
                    __('The called constructor method for %1$s class in %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.'),
                    $class_name,
                    $parent_class,
                    $version,
                    '<code>__construct()</code>'
                );
            } else {
                $message = sprintf(
                    /* translators: 1: PHP class name, 2: Version number, 3: __construct() method. */
                    __('The called constructor method for %1$s class is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'),
                    $class_name,
                    $version,
                    '<code>__construct()</code>'
                );
            }
        } else {
            if ($parent_class) {
                $message = sprintf(
                    'The called constructor method for %1$s class in %2$s is <strong>deprecated</strong> since version %3$s! Use %4$s instead.',
                    $class_name,
                    $parent_class,
                    $version,
                    '<code>__construct()</code>'
                );
            } else {
                $message = sprintf(
                    'The called constructor method for %1$s class is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
                    $class_name,
                    $version,
                    '<code>__construct()</code>'
                );
            }
        }

        tp_trigger_error('', $message, E_USER_DEPRECATED);
    }
}

/**
 * Marks a class as deprecated and informs when it has been used.
 *
 * There is a {@see 'deprecated_class_run'} hook that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated class.
 *
 * The current behavior is to trigger a user error if `TP_DEBUG` is true.
 *
 * This function is to be used in the class constructor for every deprecated class.
 * See {@see tp_deprecated_constructor()} for deprecating PHP4-style constructors.
 *
 * @param string $class_name  the name of the class being instantiated
 * @param string $version     the version of TyPrint that deprecated the class
 * @param string $replacement Optional. The class or function that should have been called.
 *                            Default empty string.
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function tp_deprecated_class(string $class_name, string $version, string $replacement = ''): void
{
    /**
     * Fires when a deprecated class is called.
     *
     * @param string $class_name  the name of the class being instantiated
     * @param string $replacement the class or function that should have been called
     * @param string $version     the version of TyPrint that deprecated the class
     *
     * @since 1.0.0
     */
    Hook::doAction('deprecated_class_run', $class_name, $replacement, $version);

    /*
     * Filters whether to trigger an error for a deprecated class.
     *
     * @since 1.0.0
     *
     * @param bool $trigger Whether to trigger an error for a deprecated class. Default true.
     */
    if (TP_DEBUG && Hook::applyFilter('deprecated_class_trigger_error', true)) {
        if (function_exists('__')) {
            if ($replacement) {
                $message = sprintf(
                    /* translators: 1: PHP class name, 2: Version number, 3: Alternative class or function name. */
                    __('Class %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'),
                    $class_name,
                    $version,
                    $replacement
                );
            } else {
                $message = sprintf(
                    /* translators: 1: PHP class name, 2: Version number. */
                    __('Class %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'),
                    $class_name,
                    $version
                );
            }
        } else {
            if ($replacement) {
                $message = sprintf(
                    'Class %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
                    $class_name,
                    $version,
                    $replacement
                );
            } else {
                $message = sprintf(
                    'Class %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $class_name,
                    $version
                );
            }
        }

        tp_trigger_error('', $message, E_USER_DEPRECATED);
    }
}

/**
 * Marks a file as deprecated and inform when it has been used.
 *
 * There is a {@see 'deprecated_file_included'} hook that will be called that can be used
 * to get the backtrace up to what file and function included the deprecated file.
 *
 * The current behavior is to trigger a user error if `TP_DEBUG` is true.
 *
 * This function is to be used in every file that is deprecated.
 *
 * @param string $file        the file that was included
 * @param string $version     the version of TyPrint that deprecated the file
 * @param string $replacement Optional. The file that should have been included based on ABSPATH.
 *                            Default empty string.
 * @param string $message     Optional. A message regarding the change. Default empty string.
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function tp_deprecated_file(string $file, string $version, string $replacement = '', string $message = ''): void
{
    /**
     * Fires when a deprecated file is called.
     *
     * @param string $file        the file that was called
     * @param string $replacement the file that should have been included based on ABSPATH
     * @param string $version     the version of TyPrint that deprecated the file
     * @param string $message     a message regarding the change
     *
     * @since 1.0.0
     */
    Hook::doAction('deprecated_file_included', $file, $replacement, $version, $message);

    /*
     * Filters whether to trigger an error for deprecated files.
     *
     * @since 1.0.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated files. Default true.
     */
    if (TP_DEBUG && Hook::applyFilter('deprecated_file_trigger_error', true)) {
        $message = empty($message) ? '' : ' '.$message;

        if (function_exists('__')) {
            if ($replacement) {
                $message = sprintf(
                    /* translators: 1: PHP file name, 2: Version number, 3: Alternative file name. */
                    __('File %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'),
                    $file,
                    $version,
                    $replacement
                ).$message;
            } else {
                $message = sprintf(
                    /* translators: 1: PHP file name, 2: Version number. */
                    __('File %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'),
                    $file,
                    $version
                ).$message;
            }
        } else {
            if ($replacement) {
                $message = sprintf(
                    'File %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
                    $file,
                    $version,
                    $replacement
                );
            } else {
                $message = sprintf(
                    'File %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $file,
                    $version
                ).$message;
            }
        }

        tp_trigger_error('', $message, E_USER_DEPRECATED);
    }
}

/**
 * Marks a function argument as deprecated and inform when it has been used.
 *
 * This function is to be used whenever a deprecated function argument is used.
 * Before this function is called, the argument must be checked for whether it was
 * used by comparing it to its default value or evaluating whether it is empty.
 *
 * For example:
 *
 *     if ( ! empty( $deprecated ) ) {
 *         tp_deprecated_argument( __FUNCTION__, '3.0.0' );
 *     }
 *
 * There is a {@see 'deprecated_argument_run'} hook that will be called that can be used
 * to get the backtrace up to what file and function used the deprecated argument.
 *
 * The current behavior is to trigger a user error if TP_DEBUG is true.
 *
 * @param string $function_name the function that was called
 * @param string $version       the version of TyPrint that deprecated the argument used
 * @param string $message       Optional. A message regarding the change. Default empty string.
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function tp_deprecated_argument(string $function_name, string $version, string $message = ''): void
{
    /**
     * Fires when a deprecated argument is called.
     *
     * @param string $function_name the function that was called
     * @param string $message       a message regarding the change
     * @param string $version       the version of TyPrint that deprecated the argument used
     *
     * @since 1.0.0
     */
    Hook::doAction('deprecated_argument_run', $function_name, $message, $version);

    /*
     * Filters whether to trigger an error for deprecated arguments.
     *
     * @since 1.0.0
     *
     * @param bool $trigger Whether to trigger the error for deprecated arguments. Default true.
     */
    if (TP_DEBUG && Hook::applyFilter('deprecated_argument_trigger_error', true)) {
        if (function_exists('__')) {
            if ($message) {
                $message = sprintf(
                    /* translators: 1: PHP function name, 2: Version number, 3: Optional message regarding the change. */
                    __('Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s'),
                    $function_name,
                    $version,
                    $message
                );
            } else {
                $message = sprintf(
                    /* translators: 1: PHP function name, 2: Version number. */
                    __('Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.'),
                    $function_name,
                    $version
                );
            }
        } else {
            if ($message) {
                $message = sprintf(
                    'Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s',
                    $function_name,
                    $version,
                    $message
                );
            } else {
                $message = sprintf(
                    'Function %1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.',
                    $function_name,
                    $version
                );
            }
        }

        tp_trigger_error('', $message, E_USER_DEPRECATED);
    }
}

/**
 * Marks a deprecated action or filter hook as deprecated and throws a notice.
 *
 * Use the {@see 'deprecated_hook_run'} action to get the backtrace describing where
 * the deprecated hook was called.
 *
 * Default behavior is to trigger a user error if `TP_DEBUG` is true.
 *
 * @param string $hook        the hook that was used
 * @param string $version     the version of TyPrint that deprecated the hook
 * @param string $replacement Optional. The hook that should have been used. Default empty string.
 * @param string $message     Optional. A message regarding the change. Default empty.
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function tp_deprecated_hook(string $hook, string $version, string $replacement = '', string $message = ''): void
{
    /**
     * Fires when a deprecated hook is called.
     *
     * @param string $hook        the hook that was called
     * @param string $replacement the hook that should be used as a replacement
     * @param string $version     the version of TyPrint that deprecated the argument used
     * @param string $message     a message regarding the change
     *
     * @since 1.0.0
     */
    Hook::doAction('deprecated_hook_run', $hook, $replacement, $version, $message);

    /*
     * Filters whether to trigger deprecated hook errors.
     *
     * @since 1.0.0
     *
     * @param bool $trigger Whether to trigger deprecated hook errors. Requires
     *                      `TP_DEBUG` to be defined true.
     */
    if (TP_DEBUG && Hook::applyFilter('deprecated_hook_trigger_error', true)) {
        $message = empty($message) ? '' : ' '.$message;

        if ($replacement) {
            $message = sprintf(
                /* translators: 1: TyPrint hook name, 2: Version number, 3: Alternative hook name. */
                L10n::__('Hook %1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'),
                $hook,
                $version,
                $replacement
            ).$message;
        } else {
            $message = sprintf(
                /* translators: 1: TyPrint hook name, 2: Version number. */
                L10n::__('Hook %1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'),
                $hook,
                $version
            ).$message;
        }

        tp_trigger_error('', $message, E_USER_DEPRECATED);
    }
}

/**
 * Marks something as being incorrectly called.
 *
 * There is a {@see 'doing_it_wrong_run'} hook that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated function.
 *
 * The current behavior is to trigger a user error if `TP_DEBUG` is true.
 *
 * @param string $function_name the function that was called
 * @param string $message       a message explaining what has been done incorrectly
 * @param string $version       the version of TyPrint where the message was added
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function _doing_it_wrong(string $function_name, string $message, string $version): void
{
    /**
     * Fires when the given function is being used incorrectly.
     *
     * @param string $function_name the function that was called
     * @param string $message       a message explaining what has been done incorrectly
     * @param string $version       the version of TyPrint where the message was added
     *
     * @since 1.0.0
     */
    Hook::doAction('doing_it_wrong_run', $function_name, $message, $version);

    /**
     * Filters whether to trigger an error for _doing_it_wrong() calls.
     *
     * @param string $function_name the function that was called
     * @param string $message       a message explaining what has been done incorrectly
     * @param string $version       the version of TyPrint where the message was added
     * @param bool   $trigger       Whether to trigger the error for _doing_it_wrong() calls. Default true.
     *
     * @since 1.0.0
     */
    if (TP_DEBUG && Hook::applyFilter('doing_it_wrong_trigger_error', true, $function_name, $message, $version)) {
        if (function_exists('__')) {
            if ($version) {
                /* translators: %s: Version number. */
                $version = sprintf(__('(This message was added in version %s.)'), $version);
            }

            $message .= ' '.sprintf(
                /* translators: %s: Documentation URL. */
                L10n::__('Please see <a href="%s">Debugging in TyPrint</a> for more information.'),
                L10n::__('https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/')
            );

            $message = sprintf(
                /* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: TyPrint version number. */
                L10n::__('Function %1$s was called <strong>incorrectly</strong>. %2$s %3$s'),
                $function_name,
                $message,
                $version
            );
        } else {
            if ($version) {
                $version = sprintf('(This message was added in version %s.)', $version);
            }

            $message .= sprintf(
                ' Please see <a href="%s">Debugging in TyPrint</a> for more information.',
                'https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/'
            );

            $message = sprintf(
                'Function %1$s was called <strong>incorrectly</strong>. %2$s %3$s',
                $function_name,
                $message,
                $version
            );
        }

        tp_trigger_error('', $message);
    }
}

/**
 * Generates a user-level error/warning/notice/deprecation message.
 *
 * Generates the message when `TP_DEBUG` is true.
 *
 * @param string $function_name the function that triggered the error
 * @param string $message       The message explaining the error.
 *                              The message can contain allowed HTML 'a' (with href), 'code',
 *                              'br', 'em', and 'strong' tags and http or https protocols.
 *                              If it contains other HTML tags or protocols, the message should be escaped
 *                              before passing to this function to avoid being stripped {@see tp_kses()}.
 * @param int    $error_level   Optional. The designated error type for this error.
 *                              Only works with E_USER family of constants. Default E_USER_NOTICE.
 *
 * @throws Exception
 *
 * @since 1.0.0
 */
function tp_trigger_error(string $function_name, string $message, int $error_level = E_USER_NOTICE): void
{
    // Bail out if TP_DEBUG is not turned on.
    if (!TP_DEBUG) {
        return;
    }

    /**
     * Fires when the given function triggers a user-level error/warning/notice/deprecation message.
     *
     * Can be used for debug backtracking.
     *
     * @param string $function_name the function that was called
     * @param string $message       a message explaining what has been done incorrectly
     * @param int    $error_level   the designated error type for this error
     *
     * @since 1.0.0
     */
    Hook::doAction('tp_trigger_error_run', $function_name, $message, $error_level);

    if (!empty($function_name)) {
        $message = sprintf('%s(): %s', $function_name, $message);
    }

    // TODO kses
    /*$message = tp_kses(
        $message,
        [
            'a' => ['href' => true],
            'br' => [],
            'code' => [],
            'em' => [],
            'strong' => [],
        ],
        ['http', 'https']
    );*/

    if (E_USER_ERROR === $error_level) {
        throw new Exception($message);
    }

    trigger_error($message, $error_level);
}

/**
 * Returns true.
 *
 * Useful for returning true to filters easily.
 *
 * @return true true
 *
 * @since 1.0.0
 * @see __return_false()
 */
function __return_true(): true
{
    return true;
}

/**
 * Returns false.
 *
 * Useful for returning false to filters easily.
 *
 * @return false false
 *
 * @since 1.0.0
 * @see __return_true()
 */
function __return_false(): false
{
    return false;
}

/**
 * Returns 0.
 *
 * Useful for returning 0 to filters easily.
 *
 * @return int 0
 *
 * @since 1.0.0
 */
function __return_zero(): int
{
    return 0;
}

/**
 * Returns an empty array.
 *
 * Useful for returning an empty array to filters easily.
 *
 * @return array empty array
 *
 * @since 1.0.0
 */
function __return_empty_array(): array
{
    return [];
}

/**
 * Returns null.
 *
 * Useful for returning null to filters easily.
 *
 * @return null null value
 *
 * @since 1.0.0
 */
function __return_null(): null
{
    return null;
}

/**
 * Returns an empty string.
 *
 * Useful for returning an empty string to filters easily.
 *
 * @return string empty string
 *
 * @since 1.0.0
 * @see __return_null()
 */
function __return_empty_string(): string
{
    return '';
}

/**
 * Generates a random UUID (version 4).
 *
 * @return string UUID
 *
 * @since 1.0.0
 */
function tp_generate_uuid4(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0x0FFF) | 0x4000,
        mt_rand(0, 0x3FFF) | 0x8000,
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF)
    );
}

/**
 * Validates that a UUID is valid.
 *
 * @param mixed    $uuid    UUID to check
 * @param int|null $version Specify which version of UUID to check against. Default is none,
 *                          to accept any UUID version. Otherwise, only version allowed is `4`.
 *
 * @return bool the string is a valid UUID or false on failure
 *
 * @since 1.0.0
 */
function tp_is_uuid(mixed $uuid, ?int $version = null): bool
{
    if (!is_string($uuid)) {
        return false;
    }

    if (is_numeric($version)) {
        if (4 !== (int) $version) {
            // _doing_it_wrong( __FUNCTION__, __( 'Only UUID V4 is supported at this time.' ), '4.9.0' );
            return false;
        }
        $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
    } else {
        $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
    }

    return (bool) preg_match($regex, $uuid);
}

/**
 * Returns a cryptographically secure hash of a message using a fast generic hash function.
 *
 * Use the tp_verify_fast_hash() function to verify the hash.
 *
 * This function does not salt the value prior to being hashed, therefore input to this function must originate from
 * a random generator with sufficiently high entropy, preferably greater than 128 bits. This function is used internally
 * in TyPrint to hash security keys and application passwords which are generated with high entropy.
 *
 * Important:
 *
 *  - This function must not be used for hashing user-generated passwords. Use tp_hash_password() for that.
 *  - This function must not be used for hashing other low-entropy input. Use tp_hash() for that.
 *
 * The BLAKE2b algorithm is used by Sodium to hash the message.
 *
 * @param string $message the message to hash
 *
 * @return string the hash of the message
 *
 * @since 1.0.0
 */
function tp_fast_hash(
    #[SensitiveParameter]
    string $message
): string {
    try {
        $hashed = sodium_crypto_generichash($message, 'tp_fast_hash', 30);
    } catch (SodiumException) {
        // If the Sodium extension is not available, fall back to a generic hash function.
        $hashed = hash('sha256', $message, true);
    }

    // Encode the hash in a URL-safe base64 format without padding.
    try {
        $encoded = sodium_bin2base64($hashed, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    } catch (SodiumException) {
        // If the Sodium extension is not available, fall back to a generic base64 encoding.
        $encoded = base64_encode($hashed);
    }

    return '$generic$'.$encoded;
}

/**
 * Checks whether a plaintext message matches the hashed value. Used to verify values hashed via tp_fast_hash().
 *
 * The function uses Sodium to hash the message and compare it to the hashed value.
 *
 * @param string $message the plaintext message
 * @param string $hash    hash of the message to check against
 *
 * @return bool whether the message matches the hashed message
 *
 * @since 1.0.0
 */
function tp_verify_fast_hash(
    #[SensitiveParameter]
    string $message,
    string $hash
): bool {
    return hash_equals($hash, tp_fast_hash($message));
}

/**
 * Creates a hash of a plain text password.
 *
 * @param string $password plain text user password to hash
 *
 * @return string the hash string of the password
 *
 * @since 1.0.0
 */
function tp_hash_password(
    #[SensitiveParameter]
    string $password
): string {
    if (strlen($password) > 4096) {
        return '*';
    }

    /**
     * Filters the hashing algorithm to use in the password_hash() and password_needs_rehash() functions.
     *
     * The default is the value of the `PASSWORD_ARGON2ID` constant which means bcrypt is used.
     *
     * **Important:** The only password hashing algorithm that is guaranteed to be available across PHP
     * installations is bcrypt. If you use any other algorithm you must make sure that it is available on
     * the server. The `password_algos()` function can be used to check which hashing algorithms are available.
     *
     * The hashing options can be controlled via the {@see 'tp_hash_password_options'} filter.
     *
     * Other available constants include:
     *
     * - `PASSWORD_ARGON2I`
     * - `PASSWORD_ARGON2ID`
     * - `PASSWORD_DEFAULT`
     *
     * @param string $algorithm The hashing algorithm. Default is the value of the `PASSWORD_ARGON2ID` constant.
     *
     * @since 1.0.0
     */
    $algorithm = Hook::applyFilter('tp_hash_password_algorithm', PASSWORD_ARGON2ID);

    /**
     * Filters the options passed to the password_hash() and password_needs_rehash() functions.
     *
     * The default hashing algorithm is argon2id, but this can be changed via the {@see 'tp_hash_password_algorithm'}
     * filter. You must ensure that the options are appropriate for the algorithm in use.
     *
     * @param string $algorithm the hashing algorithm in use
     * @param array  $options   Array of options to pass to the password hashing functions.
     *                          By default this is an empty array which means the default
     *                          options will be used.
     *
     * @since 1.0.0
     */
    $options = Hook::applyFilter('tp_hash_password_options', [], $algorithm);

    return password_hash($password, $algorithm, $options);
}

/**
 * Checks a plaintext password against a hashed password.
 *
 * @param string     $password plaintext password
 * @param string     $hash     hash of the password to check against
 * @param int|string $user_id  Optional. ID of a user associated with the password.
 *
 * @return bool false, if the $password does not match the hashed password
 *
 * @since 1.0.0
 */
function tp_check_password(
    #[SensitiveParameter]
    string $password,
    string $hash,
    int|string $user_id = ''
): bool {
    if (strlen($password) > 4096) {
        // Passwords longer than 4096 characters are not supported.
        $check = false;
    } else {
        // Check the password using compat support for any non-prefixed hash.
        $check = password_verify($password, $hash);
    }

    /*
     * Filters whether the plaintext password matches the hashed password.
     *
     * @since 1.0.0
     *
     * @param bool       $check    Whether the passwords match.
     * @param string     $password The plaintext password.
     * @param string     $hash     The hashed password.
     * @param string|int $user_id  Optional ID of a user associated with the password.
     *                             Can be empty.
     */
    return Hook::applyFilter('check_password', $check, $password, $hash, $user_id);
}

/**
 * Checks whether a password hash needs to be rehashed.
 *
 * Passwords are hashed with argon2id using the default cost. If the default cost or algorithm
 * is changed in PHP or TyPrint then a password hashed in a previous version will need to
 * be rehashed.
 *
 * @param string     $hash    hash of a password to check
 * @param int|string $user_id Optional. ID of a user associated with the password.
 *
 * @return bool whether the hash needs to be rehashed
 *
 * @since 1.0.0
 */
function tp_password_needs_rehash(string $hash, int|string $user_id = ''): bool
{
    global $tp_hasher;

    if (!empty($tp_hasher)) {
        return false;
    }

    /** This filter is documented in tp-core/functions.php */
    $algorithm = Hook::applyFilter('tp_hash_password_algorithm', PASSWORD_ARGON2ID);

    /** This filter is documented in tp-core/functions.php */
    $options = Hook::applyFilter('tp_hash_password_options', [], $algorithm);

    $needs_rehash = password_needs_rehash($hash, $algorithm, $options);

    /*
     * Filters whether the password hash needs to be rehashed.
     *
     * @since 1.0.0
     *
     * @param bool       $needs_rehash Whether the password hash needs to be rehashed.
     * @param string     $hash         The password hash.
     * @param string|int $user_id      Optional. ID of a user associated with the password.
     */
    return Hook::applyFilter('password_needs_rehash', $needs_rehash, $hash, $user_id);
}

/**
 * Generates a random non-negative number.
 *
 * @param int|null $min Optional. Lower limit for the generated number.
 *                      Accepts positive integers or zero. Defaults to 0.
 * @param int|null $max Optional. Upper limit for the generated number.
 *                      Accepts positive integers. Defaults to 4294967295.
 *
 * @return int a random non-negative number between min and max
 *
 * @global string $rnd_value
 *
 * @since 1.0.0
 */
function tp_rand(?int $min = null, ?int $max = null): int
{
    if (null === $min) {
        $min = 0;
    }
    if (null === $max) {
        $max = PHP_INT_MAX;
    }
    $_max = max($min, $max);
    $_min = min($min, $max);
    $max = $_max;
    $min = $_min;

    // Use PHP's CSPRNG, or a compatible method.
    try {
        $val = random_int($min, $max);

        return abs($val);
    } catch (Error|Exception) {
        $rnd_value = md5(uniqid(microtime().mt_rand(), true));
        // Take the first 8 digits for our value.
        $value = substr($rnd_value, 0, 8);
        $value = abs(hexdec($value));

        // Reduce the value to be within the min - max range.
        $value = $min + ($max - $min + 1) * $value / ($max_random_number + 1);

        return abs($value);
    }
}

/**
 * Generates a random password drawn from the defined set of characters.
 *
 * Uses tp_rand() to create passwords with far less predictability
 * than similar native PHP functions like `rand()` or `mt_rand()`.
 *
 * @param int  $length              Optional. The length of password to generate. Default 12.
 * @param bool $special_chars       Optional. Whether to include standard special characters.
 *                                  Default true.
 * @param bool $extra_special_chars Optional. Whether to include other special characters.
 *                                  Used when generating secret keys and salts. Default false.
 *
 * @return string the random password
 *
 * @since 1.0.0
 */
function tp_generate_password(int $length = 12, bool $special_chars = true, bool $extra_special_chars = false): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($special_chars) {
        $chars .= '!@#$%^&*()';
    }
    if ($extra_special_chars) {
        $chars .= '-_ []{}<>~`+=,.;:/?|';
    }

    $password = '';
    for ($i = 0; $i < $length; ++$i) {
        $password .= substr($chars, tp_rand(0, strlen($chars) - 1), 1);
    }

    /*
     * Filters the randomly-generated password.
     *
     * @since 1.0.0
     *
     * @param string $password            The generated password.
     * @param int    $length              The length of password to generate.
     * @param bool   $special_chars       Whether to include standard special characters.
     * @param bool   $extra_special_chars Whether to include other special characters.
     */
    return Hook::applyFilter('random_password', $password, $length, $special_chars, $extra_special_chars);
}
