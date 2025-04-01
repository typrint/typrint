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

namespace TP\Formatting;

use TP\Facades\Hook;
use TP\L10n\L10n;

class Formatting
{
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
    public static function number_format_i18n(float $number, int $decimals = 0): string
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
    public static function size_format(int|string $bytes, int $decimals = 0): false|string
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
            return self::number_format_i18n(0, $decimals).' '.L10n::_x('B', 'unit symbol');
        }

        foreach ($quant as $unit => $mag) {
            if ((float) $bytes >= $mag) {
                return self::number_format_i18n($bytes / $mag, $decimals).' '.$unit;
            }
        }

        return false;
    }

    /**
     * Checks to see if a string is utf8 encoded.
     *
     * NOTE: This function checks for 5-Byte sequences, UTF8
     *       has Bytes Sequences with a maximum length of 4.
     *
     * @param string $str the string to be checked
     *
     * @return bool true if $str fits a UTF-8 model, false otherwise
     *
     * @author bmorel at ssi dot fr (modified)
     *
     * @since 1.0.0
     */
    public static function seems_utf8(string $str): bool
    {
        $length = strlen($str);

        for ($i = 0; $i < $length; ++$i) {
            $c = ord($str[$i]);

            if ($c < 0x80) {
                $n = 0; // 0bbbbbbb
            } elseif (($c & 0xE0) === 0xC0) {
                $n = 1; // 110bbbbb
            } elseif (($c & 0xF0) === 0xE0) {
                $n = 2; // 1110bbbb
            } elseif (($c & 0xF8) === 0xF0) {
                $n = 3; // 11110bbb
            } elseif (($c & 0xFC) === 0xF8) {
                $n = 4; // 111110bb
            } elseif (($c & 0xFE) === 0xFC) {
                $n = 5; // 1111110b
            } else {
                return false; // Does not match any model.
            }

            for ($j = 0; $j < $n; ++$j) { // n bytes matching 10bbbbbb follow?
                if ((++$i === $length) || ((ord($str[$i]) & 0xC0) !== 0x80)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks for invalid UTF8 in a string.
     *
     * @param string $text  the text which is to be checked
     * @param bool   $strip Optional. Whether to attempt to strip out invalid UTF8. Default false.
     *
     * @return string the checked text
     *
     * @since 1.0.0
     */
    public static function tp_check_invalid_utf8(string $text, bool $strip = false): string
    {
        if ('' === $text) {
            return '';
        }

        if (1 === @preg_match('/^./us', $text)) {
            return $text;
        }

        // Attempt to strip the bad chars if requested (not recommended).
        if ($strip && function_exists('iconv')) {
            return iconv('utf-8', 'utf-8', $text);
        }

        return '';
    }

    /**
     * Encodes the Unicode values to be used in the URI.
     *
     * @param string $utf8_string             string to encode
     * @param int    $length                  max length of the string
     * @param bool   $encode_ascii_characters Whether to encode ascii characters such as < " '
     *
     * @return string string with Unicode encoded for URI
     *
     * @since 1.0.0
     */
    public static function utf8_uri_encode(string $utf8_string, int $length = 0, bool $encode_ascii_characters = false): string
    {
        $unicode = '';
        $values = [];
        $num_octets = 1;
        $unicode_length = 0;
        $string_length = strlen($utf8_string);

        for ($i = 0; $i < $string_length; ++$i) {
            $value = ord($utf8_string[$i]);

            if ($value < 128) {
                $char = chr($value);
                $encoded_char = $encode_ascii_characters ? rawurlencode($char) : $char;
                $encoded_char_length = strlen($encoded_char);
                if ($length && ($unicode_length + $encoded_char_length) > $length) {
                    break;
                }
                $unicode .= $encoded_char;
                $unicode_length += $encoded_char_length;
            } else {
                if (0 === count($values)) {
                    if ($value < 224) {
                        $num_octets = 2;
                    } elseif ($value < 240) {
                        $num_octets = 3;
                    } else {
                        $num_octets = 4;
                    }
                }

                $values[] = $value;

                if ($length && ($unicode_length + ($num_octets * 3)) > $length) {
                    break;
                }
                if (count($values) === $num_octets) {
                    for ($j = 0; $j < $num_octets; ++$j) {
                        $unicode .= '%'.dechex($values[$j]);
                    }

                    $unicode_length += $num_octets * 3;

                    $values = [];
                    $num_octets = 1;
                }
            }
        }

        return $unicode;
    }

    /**
     * Converts all accent characters to ASCII characters.
     *
     * If there are no accent characters, then the string given is just returned.
     *
     * **Accent characters converted:**
     *
     * Currency signs:
     *
     * |   Code   | Glyph | Replacement |     Description     |
     * | -------- | ----- | ----------- | ------------------- |
     * | U+00A3   | £     | (empty)     | British Pound sign  |
     * | U+20AC   | €     | E           | Euro sign           |
     *
     * Decompositions for Latin-1 Supplement:
     *
     * |  Code   | Glyph | Replacement |               Description              |
     * | ------- | ----- | ----------- | -------------------------------------- |
     * | U+00AA  | ª     | a           | Feminine ordinal indicator             |
     * | U+00BA  | º     | o           | Masculine ordinal indicator            |
     * | U+00C0  | À     | A           | Latin capital letter A with grave      |
     * | U+00C1  | Á     | A           | Latin capital letter A with acute      |
     * | U+00C2  | Â     | A           | Latin capital letter A with circumflex |
     * | U+00C3  | Ã     | A           | Latin capital letter A with tilde      |
     * | U+00C4  | Ä     | A           | Latin capital letter A with diaeresis  |
     * | U+00C5  | Å     | A           | Latin capital letter A with ring above |
     * | U+00C6  | Æ     | AE          | Latin capital letter AE                |
     * | U+00C7  | Ç     | C           | Latin capital letter C with cedilla    |
     * | U+00C8  | È     | E           | Latin capital letter E with grave      |
     * | U+00C9  | É     | E           | Latin capital letter E with acute      |
     * | U+00CA  | Ê     | E           | Latin capital letter E with circumflex |
     * | U+00CB  | Ë     | E           | Latin capital letter E with diaeresis  |
     * | U+00CC  | Ì     | I           | Latin capital letter I with grave      |
     * | U+00CD  | Í     | I           | Latin capital letter I with acute      |
     * | U+00CE  | Î     | I           | Latin capital letter I with circumflex |
     * | U+00CF  | Ï     | I           | Latin capital letter I with diaeresis  |
     * | U+00D0  | Ð     | D           | Latin capital letter Eth               |
     * | U+00D1  | Ñ     | N           | Latin capital letter N with tilde      |
     * | U+00D2  | Ò     | O           | Latin capital letter O with grave      |
     * | U+00D3  | Ó     | O           | Latin capital letter O with acute      |
     * | U+00D4  | Ô     | O           | Latin capital letter O with circumflex |
     * | U+00D5  | Õ     | O           | Latin capital letter O with tilde      |
     * | U+00D6  | Ö     | O           | Latin capital letter O with diaeresis  |
     * | U+00D8  | Ø     | O           | Latin capital letter O with stroke     |
     * | U+00D9  | Ù     | U           | Latin capital letter U with grave      |
     * | U+00DA  | Ú     | U           | Latin capital letter U with acute      |
     * | U+00DB  | Û     | U           | Latin capital letter U with circumflex |
     * | U+00DC  | Ü     | U           | Latin capital letter U with diaeresis  |
     * | U+00DD  | Ý     | Y           | Latin capital letter Y with acute      |
     * | U+00DE  | Þ     | TH          | Latin capital letter Thorn             |
     * | U+00DF  | ß     | s           | Latin small letter sharp s             |
     * | U+00E0  | à     | a           | Latin small letter a with grave        |
     * | U+00E1  | á     | a           | Latin small letter a with acute        |
     * | U+00E2  | â     | a           | Latin small letter a with circumflex   |
     * | U+00E3  | ã     | a           | Latin small letter a with tilde        |
     * | U+00E4  | ä     | a           | Latin small letter a with diaeresis    |
     * | U+00E5  | å     | a           | Latin small letter a with ring above   |
     * | U+00E6  | æ     | ae          | Latin small letter ae                  |
     * | U+00E7  | ç     | c           | Latin small letter c with cedilla      |
     * | U+00E8  | è     | e           | Latin small letter e with grave        |
     * | U+00E9  | é     | e           | Latin small letter e with acute        |
     * | U+00EA  | ê     | e           | Latin small letter e with circumflex   |
     * | U+00EB  | ë     | e           | Latin small letter e with diaeresis    |
     * | U+00EC  | ì     | i           | Latin small letter i with grave        |
     * | U+00ED  | í     | i           | Latin small letter i with acute        |
     * | U+00EE  | î     | i           | Latin small letter i with circumflex   |
     * | U+00EF  | ï     | i           | Latin small letter i with diaeresis    |
     * | U+00F0  | ð     | d           | Latin small letter Eth                 |
     * | U+00F1  | ñ     | n           | Latin small letter n with tilde        |
     * | U+00F2  | ò     | o           | Latin small letter o with grave        |
     * | U+00F3  | ó     | o           | Latin small letter o with acute        |
     * | U+00F4  | ô     | o           | Latin small letter o with circumflex   |
     * | U+00F5  | õ     | o           | Latin small letter o with tilde        |
     * | U+00F6  | ö     | o           | Latin small letter o with diaeresis    |
     * | U+00F8  | ø     | o           | Latin small letter o with stroke       |
     * | U+00F9  | ù     | u           | Latin small letter u with grave        |
     * | U+00FA  | ú     | u           | Latin small letter u with acute        |
     * | U+00FB  | û     | u           | Latin small letter u with circumflex   |
     * | U+00FC  | ü     | u           | Latin small letter u with diaeresis    |
     * | U+00FD  | ý     | y           | Latin small letter y with acute        |
     * | U+00FE  | þ     | th          | Latin small letter Thorn               |
     * | U+00FF  | ÿ     | y           | Latin small letter y with diaeresis    |
     *
     * Decompositions for Latin Extended-A:
     *
     * |  Code   | Glyph | Replacement |                    Description                    |
     * | ------- | ----- | ----------- | ------------------------------------------------- |
     * | U+0100  | Ā     | A           | Latin capital letter A with macron                |
     * | U+0101  | ā     | a           | Latin small letter a with macron                  |
     * | U+0102  | Ă     | A           | Latin capital letter A with breve                 |
     * | U+0103  | ă     | a           | Latin small letter a with breve                   |
     * | U+0104  | Ą     | A           | Latin capital letter A with ogonek                |
     * | U+0105  | ą     | a           | Latin small letter a with ogonek                  |
     * | U+01006 | Ć     | C           | Latin capital letter C with acute                 |
     * | U+0107  | ć     | c           | Latin small letter c with acute                   |
     * | U+0108  | Ĉ     | C           | Latin capital letter C with circumflex            |
     * | U+0109  | ĉ     | c           | Latin small letter c with circumflex              |
     * | U+010A  | Ċ     | C           | Latin capital letter C with dot above             |
     * | U+010B  | ċ     | c           | Latin small letter c with dot above               |
     * | U+010C  | Č     | C           | Latin capital letter C with caron                 |
     * | U+010D  | č     | c           | Latin small letter c with caron                   |
     * | U+010E  | Ď     | D           | Latin capital letter D with caron                 |
     * | U+010F  | ď     | d           | Latin small letter d with caron                   |
     * | U+0110  | Đ     | D           | Latin capital letter D with stroke                |
     * | U+0111  | đ     | d           | Latin small letter d with stroke                  |
     * | U+0112  | Ē     | E           | Latin capital letter E with macron                |
     * | U+0113  | ē     | e           | Latin small letter e with macron                  |
     * | U+0114  | Ĕ     | E           | Latin capital letter E with breve                 |
     * | U+0115  | ĕ     | e           | Latin small letter e with breve                   |
     * | U+0116  | Ė     | E           | Latin capital letter E with dot above             |
     * | U+0117  | ė     | e           | Latin small letter e with dot above               |
     * | U+0118  | Ę     | E           | Latin capital letter E with ogonek                |
     * | U+0119  | ę     | e           | Latin small letter e with ogonek                  |
     * | U+011A  | Ě     | E           | Latin capital letter E with caron                 |
     * | U+011B  | ě     | e           | Latin small letter e with caron                   |
     * | U+011C  | Ĝ     | G           | Latin capital letter G with circumflex            |
     * | U+011D  | ĝ     | g           | Latin small letter g with circumflex              |
     * | U+011E  | Ğ     | G           | Latin capital letter G with breve                 |
     * | U+011F  | ğ     | g           | Latin small letter g with breve                   |
     * | U+0120  | Ġ     | G           | Latin capital letter G with dot above             |
     * | U+0121  | ġ     | g           | Latin small letter g with dot above               |
     * | U+0122  | Ģ     | G           | Latin capital letter G with cedilla               |
     * | U+0123  | ģ     | g           | Latin small letter g with cedilla                 |
     * | U+0124  | Ĥ     | H           | Latin capital letter H with circumflex            |
     * | U+0125  | ĥ     | h           | Latin small letter h with circumflex              |
     * | U+0126  | Ħ     | H           | Latin capital letter H with stroke                |
     * | U+0127  | ħ     | h           | Latin small letter h with stroke                  |
     * | U+0128  | Ĩ     | I           | Latin capital letter I with tilde                 |
     * | U+0129  | ĩ     | i           | Latin small letter i with tilde                   |
     * | U+012A  | Ī     | I           | Latin capital letter I with macron                |
     * | U+012B  | ī     | i           | Latin small letter i with macron                  |
     * | U+012C  | Ĭ     | I           | Latin capital letter I with breve                 |
     * | U+012D  | ĭ     | i           | Latin small letter i with breve                   |
     * | U+012E  | Į     | I           | Latin capital letter I with ogonek                |
     * | U+012F  | į     | i           | Latin small letter i with ogonek                  |
     * | U+0130  | İ     | I           | Latin capital letter I with dot above             |
     * | U+0131  | ı     | i           | Latin small letter dotless i                      |
     * | U+0132  | Ĳ     | IJ          | Latin capital ligature IJ                         |
     * | U+0133  | ĳ     | ij          | Latin small ligature ij                           |
     * | U+0134  | Ĵ     | J           | Latin capital letter J with circumflex            |
     * | U+0135  | ĵ     | j           | Latin small letter j with circumflex              |
     * | U+0136  | Ķ     | K           | Latin capital letter K with cedilla               |
     * | U+0137  | ķ     | k           | Latin small letter k with cedilla                 |
     * | U+0138  | ĸ     | k           | Latin small letter Kra                            |
     * | U+0139  | Ĺ     | L           | Latin capital letter L with acute                 |
     * | U+013A  | ĺ     | l           | Latin small letter l with acute                   |
     * | U+013B  | Ļ     | L           | Latin capital letter L with cedilla               |
     * | U+013C  | ļ     | l           | Latin small letter l with cedilla                 |
     * | U+013D  | Ľ     | L           | Latin capital letter L with caron                 |
     * | U+013E  | ľ     | l           | Latin small letter l with caron                   |
     * | U+013F  | Ŀ     | L           | Latin capital letter L with middle dot            |
     * | U+0140  | ŀ     | l           | Latin small letter l with middle dot              |
     * | U+0141  | Ł     | L           | Latin capital letter L with stroke                |
     * | U+0142  | ł     | l           | Latin small letter l with stroke                  |
     * | U+0143  | Ń     | N           | Latin capital letter N with acute                 |
     * | U+0144  | ń     | n           | Latin small letter N with acute                   |
     * | U+0145  | Ņ     | N           | Latin capital letter N with cedilla               |
     * | U+0146  | ņ     | n           | Latin small letter n with cedilla                 |
     * | U+0147  | Ň     | N           | Latin capital letter N with caron                 |
     * | U+0148  | ň     | n           | Latin small letter n with caron                   |
     * | U+0149  | ŉ     | n           | Latin small letter n preceded by apostrophe       |
     * | U+014A  | Ŋ     | N           | Latin capital letter Eng                          |
     * | U+014B  | ŋ     | n           | Latin small letter Eng                            |
     * | U+014C  | Ō     | O           | Latin capital letter O with macron                |
     * | U+014D  | ō     | o           | Latin small letter o with macron                  |
     * | U+014E  | Ŏ     | O           | Latin capital letter O with breve                 |
     * | U+014F  | ŏ     | o           | Latin small letter o with breve                   |
     * | U+0150  | Ő     | O           | Latin capital letter O with double acute          |
     * | U+0151  | ő     | o           | Latin small letter o with double acute            |
     * | U+0152  | Œ     | OE          | Latin capital ligature OE                         |
     * | U+0153  | œ     | oe          | Latin small ligature oe                           |
     * | U+0154  | Ŕ     | R           | Latin capital letter R with acute                 |
     * | U+0155  | ŕ     | r           | Latin small letter r with acute                   |
     * | U+0156  | Ŗ     | R           | Latin capital letter R with cedilla               |
     * | U+0157  | ŗ     | r           | Latin small letter r with cedilla                 |
     * | U+0158  | Ř     | R           | Latin capital letter R with caron                 |
     * | U+0159  | ř     | r           | Latin small letter r with caron                   |
     * | U+015A  | Ś     | S           | Latin capital letter S with acute                 |
     * | U+015B  | ś     | s           | Latin small letter s with acute                   |
     * | U+015C  | Ŝ     | S           | Latin capital letter S with circumflex            |
     * | U+015D  | ŝ     | s           | Latin small letter s with circumflex              |
     * | U+015E  | Ş     | S           | Latin capital letter S with cedilla               |
     * | U+015F  | ş     | s           | Latin small letter s with cedilla                 |
     * | U+0160  | Š     | S           | Latin capital letter S with caron                 |
     * | U+0161  | š     | s           | Latin small letter s with caron                   |
     * | U+0162  | Ţ     | T           | Latin capital letter T with cedilla               |
     * | U+0163  | ţ     | t           | Latin small letter t with cedilla                 |
     * | U+0164  | Ť     | T           | Latin capital letter T with caron                 |
     * | U+0165  | ť     | t           | Latin small letter t with caron                   |
     * | U+0166  | Ŧ     | T           | Latin capital letter T with stroke                |
     * | U+0167  | ŧ     | t           | Latin small letter t with stroke                  |
     * | U+0168  | Ũ     | U           | Latin capital letter U with tilde                 |
     * | U+0169  | ũ     | u           | Latin small letter u with tilde                   |
     * | U+016A  | Ū     | U           | Latin capital letter U with macron                |
     * | U+016B  | ū     | u           | Latin small letter u with macron                  |
     * | U+016C  | Ŭ     | U           | Latin capital letter U with breve                 |
     * | U+016D  | ŭ     | u           | Latin small letter u with breve                   |
     * | U+016E  | Ů     | U           | Latin capital letter U with ring above            |
     * | U+016F  | ů     | u           | Latin small letter u with ring above              |
     * | U+0170  | Ű     | U           | Latin capital letter U with double acute          |
     * | U+0171  | ű     | u           | Latin small letter u with double acute            |
     * | U+0172  | Ų     | U           | Latin capital letter U with ogonek                |
     * | U+0173  | ų     | u           | Latin small letter u with ogonek                  |
     * | U+0174  | Ŵ     | W           | Latin capital letter W with circumflex            |
     * | U+0175  | ŵ     | w           | Latin small letter w with circumflex              |
     * | U+0176  | Ŷ     | Y           | Latin capital letter Y with circumflex            |
     * | U+0177  | ŷ     | y           | Latin small letter y with circumflex              |
     * | U+0178  | Ÿ     | Y           | Latin capital letter Y with diaeresis             |
     * | U+0179  | Ź     | Z           | Latin capital letter Z with acute                 |
     * | U+017A  | ź     | z           | Latin small letter z with acute                   |
     * | U+017B  | Ż     | Z           | Latin capital letter Z with dot above             |
     * | U+017C  | ż     | z           | Latin small letter z with dot above               |
     * | U+017D  | Ž     | Z           | Latin capital letter Z with caron                 |
     * | U+017E  | ž     | z           | Latin small letter z with caron                   |
     * | U+017F  | ſ     | s           | Latin small letter long s                         |
     * | U+01A0  | Ơ     | O           | Latin capital letter O with horn                  |
     * | U+01A1  | ơ     | o           | Latin small letter o with horn                    |
     * | U+01AF  | Ư     | U           | Latin capital letter U with horn                  |
     * | U+01B0  | ư     | u           | Latin small letter u with horn                    |
     * | U+01CD  | Ǎ     | A           | Latin capital letter A with caron                 |
     * | U+01CE  | ǎ     | a           | Latin small letter a with caron                   |
     * | U+01CF  | Ǐ     | I           | Latin capital letter I with caron                 |
     * | U+01D0  | ǐ     | i           | Latin small letter i with caron                   |
     * | U+01D1  | Ǒ     | O           | Latin capital letter O with caron                 |
     * | U+01D2  | ǒ     | o           | Latin small letter o with caron                   |
     * | U+01D3  | Ǔ     | U           | Latin capital letter U with caron                 |
     * | U+01D4  | ǔ     | u           | Latin small letter u with caron                   |
     * | U+01D5  | Ǖ     | U           | Latin capital letter U with diaeresis and macron  |
     * | U+01D6  | ǖ     | u           | Latin small letter u with diaeresis and macron    |
     * | U+01D7  | Ǘ     | U           | Latin capital letter U with diaeresis and acute   |
     * | U+01D8  | ǘ     | u           | Latin small letter u with diaeresis and acute     |
     * | U+01D9  | Ǚ     | U           | Latin capital letter U with diaeresis and caron   |
     * | U+01DA  | ǚ     | u           | Latin small letter u with diaeresis and caron     |
     * | U+01DB  | Ǜ     | U           | Latin capital letter U with diaeresis and grave   |
     * | U+01DC  | ǜ     | u           | Latin small letter u with diaeresis and grave     |
     *
     * Decompositions for Latin Extended-B:
     *
     * |   Code   | Glyph | Replacement |                Description                |
     * | -------- | ----- | ----------- | ----------------------------------------- |
     * | U+018F   | Ə     | E           | Latin capital letter Ə                    |
     * | U+0259   | ǝ     | e           | Latin small letter ǝ                      |
     * | U+0218   | Ș     | S           | Latin capital letter S with comma below   |
     * | U+0219   | ș     | s           | Latin small letter s with comma below     |
     * | U+021A   | Ț     | T           | Latin capital letter T with comma below   |
     * | U+021B   | ț     | t           | Latin small letter t with comma below     |
     *
     * Vowels with diacritic (Chinese, Hanyu Pinyin):
     *
     * |   Code   | Glyph | Replacement |                      Description                      |
     * | -------- | ----- | ----------- | ----------------------------------------------------- |
     * | U+0251   | ɑ     | a           | Latin small letter alpha                              |
     * | U+1EA0   | Ạ     | A           | Latin capital letter A with dot below                 |
     * | U+1EA1   | ạ     | a           | Latin small letter a with dot below                   |
     * | U+1EA2   | Ả     | A           | Latin capital letter A with hook above                |
     * | U+1EA3   | ả     | a           | Latin small letter a with hook above                  |
     * | U+1EA4   | Ấ     | A           | Latin capital letter A with circumflex and acute      |
     * | U+1EA5   | ấ     | a           | Latin small letter a with circumflex and acute        |
     * | U+1EA6   | Ầ     | A           | Latin capital letter A with circumflex and grave      |
     * | U+1EA7   | ầ     | a           | Latin small letter a with circumflex and grave        |
     * | U+1EA8   | Ẩ     | A           | Latin capital letter A with circumflex and hook above |
     * | U+1EA9   | ẩ     | a           | Latin small letter a with circumflex and hook above   |
     * | U+1EAA   | Ẫ     | A           | Latin capital letter A with circumflex and tilde      |
     * | U+1EAB   | ẫ     | a           | Latin small letter a with circumflex and tilde        |
     * | U+1EA6   | Ậ     | A           | Latin capital letter A with circumflex and dot below  |
     * | U+1EAD   | ậ     | a           | Latin small letter a with circumflex and dot below    |
     * | U+1EAE   | Ắ     | A           | Latin capital letter A with breve and acute           |
     * | U+1EAF   | ắ     | a           | Latin small letter a with breve and acute             |
     * | U+1EB0   | Ằ     | A           | Latin capital letter A with breve and grave           |
     * | U+1EB1   | ằ     | a           | Latin small letter a with breve and grave             |
     * | U+1EB2   | Ẳ     | A           | Latin capital letter A with breve and hook above      |
     * | U+1EB3   | ẳ     | a           | Latin small letter a with breve and hook above        |
     * | U+1EB4   | Ẵ     | A           | Latin capital letter A with breve and tilde           |
     * | U+1EB5   | ẵ     | a           | Latin small letter a with breve and tilde             |
     * | U+1EB6   | Ặ     | A           | Latin capital letter A with breve and dot below       |
     * | U+1EB7   | ặ     | a           | Latin small letter a with breve and dot below         |
     * | U+1EB8   | Ẹ     | E           | Latin capital letter E with dot below                 |
     * | U+1EB9   | ẹ     | e           | Latin small letter e with dot below                   |
     * | U+1EBA   | Ẻ     | E           | Latin capital letter E with hook above                |
     * | U+1EBB   | ẻ     | e           | Latin small letter e with hook above                  |
     * | U+1EBC   | Ẽ     | E           | Latin capital letter E with tilde                     |
     * | U+1EBD   | ẽ     | e           | Latin small letter e with tilde                       |
     * | U+1EBE   | Ế     | E           | Latin capital letter E with circumflex and acute      |
     * | U+1EBF   | ế     | e           | Latin small letter e with circumflex and acute        |
     * | U+1EC0   | Ề     | E           | Latin capital letter E with circumflex and grave      |
     * | U+1EC1   | ề     | e           | Latin small letter e with circumflex and grave        |
     * | U+1EC2   | Ể     | E           | Latin capital letter E with circumflex and hook above |
     * | U+1EC3   | ể     | e           | Latin small letter e with circumflex and hook above   |
     * | U+1EC4   | Ễ     | E           | Latin capital letter E with circumflex and tilde      |
     * | U+1EC5   | ễ     | e           | Latin small letter e with circumflex and tilde        |
     * | U+1EC6   | Ệ     | E           | Latin capital letter E with circumflex and dot below  |
     * | U+1EC7   | ệ     | e           | Latin small letter e with circumflex and dot below    |
     * | U+1EC8   | Ỉ     | I           | Latin capital letter I with hook above                |
     * | U+1EC9   | ỉ     | i           | Latin small letter i with hook above                  |
     * | U+1ECA   | Ị     | I           | Latin capital letter I with dot below                 |
     * | U+1ECB   | ị     | i           | Latin small letter i with dot below                   |
     * | U+1ECC   | Ọ     | O           | Latin capital letter O with dot below                 |
     * | U+1ECD   | ọ     | o           | Latin small letter o with dot below                   |
     * | U+1ECE   | Ỏ     | O           | Latin capital letter O with hook above                |
     * | U+1ECF   | ỏ     | o           | Latin small letter o with hook above                  |
     * | U+1ED0   | Ố     | O           | Latin capital letter O with circumflex and acute      |
     * | U+1ED1   | ố     | o           | Latin small letter o with circumflex and acute        |
     * | U+1ED2   | Ồ     | O           | Latin capital letter O with circumflex and grave      |
     * | U+1ED3   | ồ     | o           | Latin small letter o with circumflex and grave        |
     * | U+1ED4   | Ổ     | O           | Latin capital letter O with circumflex and hook above |
     * | U+1ED5   | ổ     | o           | Latin small letter o with circumflex and hook above   |
     * | U+1ED6   | Ỗ     | O           | Latin capital letter O with circumflex and tilde      |
     * | U+1ED7   | ỗ     | o           | Latin small letter o with circumflex and tilde        |
     * | U+1ED8   | Ộ     | O           | Latin capital letter O with circumflex and dot below  |
     * | U+1ED9   | ộ     | o           | Latin small letter o with circumflex and dot below    |
     * | U+1EDA   | Ớ     | O           | Latin capital letter O with horn and acute            |
     * | U+1EDB   | ớ     | o           | Latin small letter o with horn and acute              |
     * | U+1EDC   | Ờ     | O           | Latin capital letter O with horn and grave            |
     * | U+1EDD   | ờ     | o           | Latin small letter o with horn and grave              |
     * | U+1EDE   | Ở     | O           | Latin capital letter O with horn and hook above       |
     * | U+1EDF   | ở     | o           | Latin small letter o with horn and hook above         |
     * | U+1EE0   | Ỡ     | O           | Latin capital letter O with horn and tilde            |
     * | U+1EE1   | ỡ     | o           | Latin small letter o with horn and tilde              |
     * | U+1EE2   | Ợ     | O           | Latin capital letter O with horn and dot below        |
     * | U+1EE3   | ợ     | o           | Latin small letter o with horn and dot below          |
     * | U+1EE4   | Ụ     | U           | Latin capital letter U with dot below                 |
     * | U+1EE5   | ụ     | u           | Latin small letter u with dot below                   |
     * | U+1EE6   | Ủ     | U           | Latin capital letter U with hook above                |
     * | U+1EE7   | ủ     | u           | Latin small letter u with hook above                  |
     * | U+1EE8   | Ứ     | U           | Latin capital letter U with horn and acute            |
     * | U+1EE9   | ứ     | u           | Latin small letter u with horn and acute              |
     * | U+1EEA   | Ừ     | U           | Latin capital letter U with horn and grave            |
     * | U+1EEB   | ừ     | u           | Latin small letter u with horn and grave              |
     * | U+1EEC   | Ử     | U           | Latin capital letter U with horn and hook above       |
     * | U+1EED   | ử     | u           | Latin small letter u with horn and hook above         |
     * | U+1EEE   | Ữ     | U           | Latin capital letter U with horn and tilde            |
     * | U+1EEF   | ữ     | u           | Latin small letter u with horn and tilde              |
     * | U+1EF0   | Ự     | U           | Latin capital letter U with horn and dot below        |
     * | U+1EF1   | ự     | u           | Latin small letter u with horn and dot below          |
     * | U+1EF2   | Ỳ     | Y           | Latin capital letter Y with grave                     |
     * | U+1EF3   | ỳ     | y           | Latin small letter y with grave                       |
     * | U+1EF4   | Ỵ     | Y           | Latin capital letter Y with dot below                 |
     * | U+1EF5   | ỵ     | y           | Latin small letter y with dot below                   |
     * | U+1EF6   | Ỷ     | Y           | Latin capital letter Y with hook above                |
     * | U+1EF7   | ỷ     | y           | Latin small letter y with hook above                  |
     * | U+1EF8   | Ỹ     | Y           | Latin capital letter Y with tilde                     |
     * | U+1EF9   | ỹ     | y           | Latin small letter y with tilde                       |
     *
     * German (`de_DE`), German formal (`de_DE_formal`), German (Switzerland) formal (`de_CH`),
     * German (Switzerland) informal (`de_CH_informal`), and German (Austria) (`de_AT`) locales:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+00C4   | Ä     | Ae          | Latin capital letter A with diaeresis   |
     * | U+00E4   | ä     | ae          | Latin small letter a with diaeresis     |
     * | U+00D6   | Ö     | Oe          | Latin capital letter O with diaeresis   |
     * | U+00F6   | ö     | oe          | Latin small letter o with diaeresis     |
     * | U+00DC   | Ü     | Ue          | Latin capital letter U with diaeresis   |
     * | U+00FC   | ü     | ue          | Latin small letter u with diaeresis     |
     * | U+00DF   | ß     | ss          | Latin small letter sharp s              |
     *
     * Danish (`da_DK`) locale:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+00C6   | Æ     | Ae          | Latin capital letter AE                 |
     * | U+00E6   | æ     | ae          | Latin small letter ae                   |
     * | U+00D8   | Ø     | Oe          | Latin capital letter O with stroke      |
     * | U+00F8   | ø     | oe          | Latin small letter o with stroke        |
     * | U+00C5   | Å     | Aa          | Latin capital letter A with ring above  |
     * | U+00E5   | å     | aa          | Latin small letter a with ring above    |
     *
     * Catalan (`ca`) locale:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+00B7   | l·l   | ll          | Flown dot (between two Ls)              |
     *
     * Serbian (`sr_RS`) and Bosnian (`bs_BA`) locales:
     *
     * |   Code   | Glyph | Replacement |               Description               |
     * | -------- | ----- | ----------- | --------------------------------------- |
     * | U+0110   | Đ     | DJ          | Latin capital letter D with stroke      |
     * | U+0111   | đ     | dj          | Latin small letter d with stroke        |
     *
     * @param string $text   text that might have accent characters
     * @param string $locale Optional. The locale to use for accent removal. Some character
     *                       replacements depend on the locale being used (e.g. 'de_DE').
     *                       Defaults to the current locale.
     *
     * @return string filtered string with replaced "nice" characters
     *
     * @since 1.0.0
     */
    public static function remove_accents(string $text, string $locale = ''): string
    {
        if (!preg_match('/[\x80-\xff]/', $text)) {
            return $text;
        }

        if (self::seems_utf8($text)) {
            /*
             * Unicode sequence normalization from NFD (Normalization Form Decomposed)
             * to NFC (Normalization Form [Pre]Composed), the encoding used in this function.
             */
            if (!normalizer_is_normalized($text)) {
                $text = normalizer_normalize($text);
            }

            $chars = [
                // Decompositions for Latin-1 Supplement.
                'ª' => 'a',
                'º' => 'o',
                'À' => 'A',
                'Á' => 'A',
                'Â' => 'A',
                'Ã' => 'A',
                'Ä' => 'A',
                'Å' => 'A',
                'Æ' => 'AE',
                'Ç' => 'C',
                'È' => 'E',
                'É' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Ì' => 'I',
                'Í' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ð' => 'D',
                'Ñ' => 'N',
                'Ò' => 'O',
                'Ó' => 'O',
                'Ô' => 'O',
                'Õ' => 'O',
                'Ö' => 'O',
                'Ù' => 'U',
                'Ú' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'Ý' => 'Y',
                'Þ' => 'TH',
                'ß' => 's',
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'æ' => 'ae',
                'ç' => 'c',
                'è' => 'e',
                'é' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ð' => 'd',
                'ñ' => 'n',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ý' => 'y',
                'þ' => 'th',
                'ÿ' => 'y',
                'Ø' => 'O',
                // Decompositions for Latin Extended-A.
                'Ā' => 'A',
                'ā' => 'a',
                'Ă' => 'A',
                'ă' => 'a',
                'Ą' => 'A',
                'ą' => 'a',
                'Ć' => 'C',
                'ć' => 'c',
                'Ĉ' => 'C',
                'ĉ' => 'c',
                'Ċ' => 'C',
                'ċ' => 'c',
                'Č' => 'C',
                'č' => 'c',
                'Ď' => 'D',
                'ď' => 'd',
                'Đ' => 'D',
                'đ' => 'd',
                'Ē' => 'E',
                'ē' => 'e',
                'Ĕ' => 'E',
                'ĕ' => 'e',
                'Ė' => 'E',
                'ė' => 'e',
                'Ę' => 'E',
                'ę' => 'e',
                'Ě' => 'E',
                'ě' => 'e',
                'Ĝ' => 'G',
                'ĝ' => 'g',
                'Ğ' => 'G',
                'ğ' => 'g',
                'Ġ' => 'G',
                'ġ' => 'g',
                'Ģ' => 'G',
                'ģ' => 'g',
                'Ĥ' => 'H',
                'ĥ' => 'h',
                'Ħ' => 'H',
                'ħ' => 'h',
                'Ĩ' => 'I',
                'ĩ' => 'i',
                'Ī' => 'I',
                'ī' => 'i',
                'Ĭ' => 'I',
                'ĭ' => 'i',
                'Į' => 'I',
                'į' => 'i',
                'İ' => 'I',
                'ı' => 'i',
                'Ĳ' => 'IJ',
                'ĳ' => 'ij',
                'Ĵ' => 'J',
                'ĵ' => 'j',
                'Ķ' => 'K',
                'ķ' => 'k',
                'ĸ' => 'k',
                'Ĺ' => 'L',
                'ĺ' => 'l',
                'Ļ' => 'L',
                'ļ' => 'l',
                'Ľ' => 'L',
                'ľ' => 'l',
                'Ŀ' => 'L',
                'ŀ' => 'l',
                'Ł' => 'L',
                'ł' => 'l',
                'Ń' => 'N',
                'ń' => 'n',
                'Ņ' => 'N',
                'ņ' => 'n',
                'Ň' => 'N',
                'ň' => 'n',
                'ŉ' => 'n',
                'Ŋ' => 'N',
                'ŋ' => 'n',
                'Ō' => 'O',
                'ō' => 'o',
                'Ŏ' => 'O',
                'ŏ' => 'o',
                'Ő' => 'O',
                'ő' => 'o',
                'Œ' => 'OE',
                'œ' => 'oe',
                'Ŕ' => 'R',
                'ŕ' => 'r',
                'Ŗ' => 'R',
                'ŗ' => 'r',
                'Ř' => 'R',
                'ř' => 'r',
                'Ś' => 'S',
                'ś' => 's',
                'Ŝ' => 'S',
                'ŝ' => 's',
                'Ş' => 'S',
                'ş' => 's',
                'Š' => 'S',
                'š' => 's',
                'Ţ' => 'T',
                'ţ' => 't',
                'Ť' => 'T',
                'ť' => 't',
                'Ŧ' => 'T',
                'ŧ' => 't',
                'Ũ' => 'U',
                'ũ' => 'u',
                'Ū' => 'U',
                'ū' => 'u',
                'Ŭ' => 'U',
                'ŭ' => 'u',
                'Ů' => 'U',
                'ů' => 'u',
                'Ű' => 'U',
                'ű' => 'u',
                'Ų' => 'U',
                'ų' => 'u',
                'Ŵ' => 'W',
                'ŵ' => 'w',
                'Ŷ' => 'Y',
                'ŷ' => 'y',
                'Ÿ' => 'Y',
                'Ź' => 'Z',
                'ź' => 'z',
                'Ż' => 'Z',
                'ż' => 'z',
                'Ž' => 'Z',
                'ž' => 'z',
                'ſ' => 's',
                // Decompositions for Latin Extended-B.
                'Ə' => 'E',
                'ǝ' => 'e',
                'Ș' => 'S',
                'ș' => 's',
                'Ț' => 'T',
                'ț' => 't',
                // Euro sign.
                '€' => 'E',
                // GBP (Pound) sign.
                '£' => '',
                // Vowels with diacritic (Vietnamese). Unmarked.
                'Ơ' => 'O',
                'ơ' => 'o',
                'Ư' => 'U',
                'ư' => 'u',
                // Grave accent.
                'Ầ' => 'A',
                'ầ' => 'a',
                'Ằ' => 'A',
                'ằ' => 'a',
                'Ề' => 'E',
                'ề' => 'e',
                'Ồ' => 'O',
                'ồ' => 'o',
                'Ờ' => 'O',
                'ờ' => 'o',
                'Ừ' => 'U',
                'ừ' => 'u',
                'Ỳ' => 'Y',
                'ỳ' => 'y',
                // Hook.
                'Ả' => 'A',
                'ả' => 'a',
                'Ẩ' => 'A',
                'ẩ' => 'a',
                'Ẳ' => 'A',
                'ẳ' => 'a',
                'Ẻ' => 'E',
                'ẻ' => 'e',
                'Ể' => 'E',
                'ể' => 'e',
                'Ỉ' => 'I',
                'ỉ' => 'i',
                'Ỏ' => 'O',
                'ỏ' => 'o',
                'Ổ' => 'O',
                'ổ' => 'o',
                'Ở' => 'O',
                'ở' => 'o',
                'Ủ' => 'U',
                'ủ' => 'u',
                'Ử' => 'U',
                'ử' => 'u',
                'Ỷ' => 'Y',
                'ỷ' => 'y',
                // Tilde.
                'Ẫ' => 'A',
                'ẫ' => 'a',
                'Ẵ' => 'A',
                'ẵ' => 'a',
                'Ẽ' => 'E',
                'ẽ' => 'e',
                'Ễ' => 'E',
                'ễ' => 'e',
                'Ỗ' => 'O',
                'ỗ' => 'o',
                'Ỡ' => 'O',
                'ỡ' => 'o',
                'Ữ' => 'U',
                'ữ' => 'u',
                'Ỹ' => 'Y',
                'ỹ' => 'y',
                // Acute accent.
                'Ấ' => 'A',
                'ấ' => 'a',
                'Ắ' => 'A',
                'ắ' => 'a',
                'Ế' => 'E',
                'ế' => 'e',
                'Ố' => 'O',
                'ố' => 'o',
                'Ớ' => 'O',
                'ớ' => 'o',
                'Ứ' => 'U',
                'ứ' => 'u',
                // Dot below.
                'Ạ' => 'A',
                'ạ' => 'a',
                'Ậ' => 'A',
                'ậ' => 'a',
                'Ặ' => 'A',
                'ặ' => 'a',
                'Ẹ' => 'E',
                'ẹ' => 'e',
                'Ệ' => 'E',
                'ệ' => 'e',
                'Ị' => 'I',
                'ị' => 'i',
                'Ọ' => 'O',
                'ọ' => 'o',
                'Ộ' => 'O',
                'ộ' => 'o',
                'Ợ' => 'O',
                'ợ' => 'o',
                'Ụ' => 'U',
                'ụ' => 'u',
                'Ự' => 'U',
                'ự' => 'u',
                'Ỵ' => 'Y',
                'ỵ' => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin).
                'ɑ' => 'a',
                // Macron.
                'Ǖ' => 'U',
                'ǖ' => 'u',
                // Acute accent.
                'Ǘ' => 'U',
                'ǘ' => 'u',
                // Caron.
                'Ǎ' => 'A',
                'ǎ' => 'a',
                'Ǐ' => 'I',
                'ǐ' => 'i',
                'Ǒ' => 'O',
                'ǒ' => 'o',
                'Ǔ' => 'U',
                'ǔ' => 'u',
                'Ǚ' => 'U',
                'ǚ' => 'u',
                // Grave accent.
                'Ǜ' => 'U',
                'ǜ' => 'u',
            ];

            /*
             * German has various locales (de_DE, de_CH, de_AT, ...) with formal and informal variants.
             * There is no 3-letter locale like 'def', so checking for 'de' instead of 'de_' is safe,
             * since 'de' itself would be a valid locale too.
             */
            if (str_starts_with($locale, 'de')) {
                $chars['Ä'] = 'Ae';
                $chars['ä'] = 'ae';
                $chars['Ö'] = 'Oe';
                $chars['ö'] = 'oe';
                $chars['Ü'] = 'Ue';
                $chars['ü'] = 'ue';
                $chars['ß'] = 'ss';
            } elseif ('da_DK' === $locale) {
                $chars['Æ'] = 'Ae';
                $chars['æ'] = 'ae';
                $chars['Ø'] = 'Oe';
                $chars['ø'] = 'oe';
                $chars['Å'] = 'Aa';
                $chars['å'] = 'aa';
            } elseif ('ca' === $locale) {
                $chars['l·l'] = 'll';
            } elseif ('sr_RS' === $locale || 'bs_BA' === $locale) {
                $chars['Đ'] = 'DJ';
                $chars['đ'] = 'dj';
            }

            $text = strtr($text, $chars);
        } else {
            $chars = [];
            // Assume ISO-8859-1 if not UTF-8.
            $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
                ."\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
                ."\xc3\xc4\xc5\xc7\xc8\xc9\xca"
                ."\xcb\xcc\xcd\xce\xcf\xd1\xd2"
                ."\xd3\xd4\xd5\xd6\xd8\xd9\xda"
                ."\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
                ."\xe4\xe5\xe7\xe8\xe9\xea\xeb"
                ."\xec\xed\xee\xef\xf1\xf2\xf3"
                ."\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
                ."\xfc\xfd\xff";

            $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';

            $text = strtr($text, $chars['in'], $chars['out']);
            $double_chars = [];
            $double_chars['in'] = ["\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe"];
            $double_chars['out'] = ['OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th'];
            $text = str_replace($double_chars['in'], $double_chars['out'], $text);
        }

        return $text;
    }

    /**
     * Sanitizes a string key.
     *
     * Keys are used as internal identifiers. Lowercase alphanumeric characters,
     * dashes, and underscores are allowed.
     *
     * @param string $key string key
     *
     * @return string sanitized key
     *
     * @since 1.0.0
     */
    public static function sanitize_key(string $key): string
    {
        $sanitized_key = strtolower($key);
        $sanitized_key = preg_replace('/[^a-z0-9_\-]/', '', $sanitized_key);

        /**
         * Filters a sanitized key string.
         *
         * @param string $sanitized_key sanitized key
         * @param string $key           the key prior to sanitization
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('sanitize_key', $sanitized_key, $key);
    }

    /**
     * Sanitizes a string into a slug, which can be used in URLs or HTML attributes.
     *
     * By default, converts accent characters to ASCII characters and further
     * limits the output to alphanumeric characters, underscore (_) and dash (-)
     * through the {@see 'sanitize_title'} filter.
     *
     * If `$title` is empty and `$fallback_title` is set, the latter will be used.
     *
     * @param string $title          the string to be sanitized
     * @param string $fallback_title Optional. A title to use if $title is empty. Default empty.
     * @param string $context        Optional. The operation for which the string is sanitized.
     *                               When set to 'save', the string runs through remove_accents().
     *                               Default 'query'.
     *
     * @return string the sanitized string
     *
     * @since 1.0.0
     */
    public static function sanitize_title(string $title, string $fallback_title = '', string $context = 'query'): string
    {
        $raw_title = $title;

        if ('save' === $context) {
            $title = self::remove_accents($title);
        }

        /**
         * Filters a sanitized title string.
         *
         * @param string $title     sanitized title
         * @param string $raw_title the title prior to sanitization
         * @param string $context   the context for which the title is being sanitized
         *
         * @since 1.0.0
         */
        $title = Hook::applyFilter('sanitize_title', $title, $raw_title, $context);

        if ('' === $title || false === $title) {
            $title = $fallback_title;
        }

        return $title;
    }

    /**
     * Sanitizes a title, replacing whitespace and a few other characters with dashes.
     *
     * Limits the output to alphanumeric characters, underscore (_) and dash (-).
     * Whitespace becomes a dash.
     *
     * @param string $title   the title to be sanitized
     * @param string $context Optional. The operation for which the string is sanitized.
     *                        When set to 'save', additional entities are converted to hyphens
     *                        or stripped entirely. Default 'display'.
     *
     * @return string the sanitized title
     *
     * @since 1.0.0
     */
    public static function sanitize_title_with_dashes(string $title, string $context = 'display'): string
    {
        $title = strip_tags($title);
        // Preserve escaped octets.
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        // Remove percent signs that are not part of an octet.
        $title = str_replace('%', '', $title);
        // Restore octets.
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

        $title = mb_strtolower($title, 'UTF-8');
        $title = self::utf8_uri_encode($title, 200);
        $title = strtolower($title);

        if ('save' === $context) {
            // Convert &nbsp, &ndash, and &mdash to hyphens.
            $title = str_replace(['%c2%a0', '%e2%80%93', '%e2%80%94'], '-', $title);
            // Convert &nbsp, &ndash, and &mdash HTML entities to hyphens.
            $title = str_replace(['&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;'], '-', $title);
            // Convert forward slash to hyphen.
            $title = str_replace('/', '-', $title);

            // Strip these characters entirely.
            $title = str_replace(
                [
                    // Soft hyphens.
                    '%c2%ad',
                    // &iexcl and &iquest.
                    '%c2%a1',
                    '%c2%bf',
                    // Angle quotes.
                    '%c2%ab',
                    '%c2%bb',
                    '%e2%80%b9',
                    '%e2%80%ba',
                    // Curly quotes.
                    '%e2%80%98',
                    '%e2%80%99',
                    '%e2%80%9c',
                    '%e2%80%9d',
                    '%e2%80%9a',
                    '%e2%80%9b',
                    '%e2%80%9e',
                    '%e2%80%9f',
                    // Bullet.
                    '%e2%80%a2',
                    // &copy, &reg, &deg, &hellip, and &trade.
                    '%c2%a9',
                    '%c2%ae',
                    '%c2%b0',
                    '%e2%80%a6',
                    '%e2%84%a2',
                    // Acute accents.
                    '%c2%b4',
                    '%cb%8a',
                    '%cc%81',
                    '%cd%81',
                    // Grave accent, macron, caron.
                    '%cc%80',
                    '%cc%84',
                    '%cc%8c',
                    // Non-visible characters that display without a width.
                    '%e2%80%8b', // Zero width space.
                    '%e2%80%8c', // Zero width non-joiner.
                    '%e2%80%8d', // Zero width joiner.
                    '%e2%80%8e', // Left-to-right mark.
                    '%e2%80%8f', // Right-to-left mark.
                    '%e2%80%aa', // Left-to-right embedding.
                    '%e2%80%ab', // Right-to-left embedding.
                    '%e2%80%ac', // Pop directional formatting.
                    '%e2%80%ad', // Left-to-right override.
                    '%e2%80%ae', // Right-to-left override.
                    '%ef%bb%bf', // Byte order mark.
                    '%ef%bf%bc', // Object replacement character.
                ],
                '',
                $title
            );

            // Convert non-visible characters that display with a width to hyphen.
            $title = str_replace(
                [
                    '%e2%80%80', // En quad.
                    '%e2%80%81', // Em quad.
                    '%e2%80%82', // En space.
                    '%e2%80%83', // Em space.
                    '%e2%80%84', // Three-per-em space.
                    '%e2%80%85', // Four-per-em space.
                    '%e2%80%86', // Six-per-em space.
                    '%e2%80%87', // Figure space.
                    '%e2%80%88', // Punctuation space.
                    '%e2%80%89', // Thin space.
                    '%e2%80%8a', // Hair space.
                    '%e2%80%a8', // Line separator.
                    '%e2%80%a9', // Paragraph separator.
                    '%e2%80%af', // Narrow no-break space.
                ],
                '-',
                $title
            );

            // Convert &times to 'x'.
            $title = str_replace('%c3%97', 'x', $title);
        }

        // Remove HTML entities.
        $title = preg_replace('/&.+?;/', '', $title);
        $title = str_replace('.', '-', $title);

        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');

        return $title;
    }

    /**
     * Sanitizes an HTML classname to ensure it only contains valid characters.
     *
     * Strips the string down to A-Z,a-z,0-9,_,-. If this results in an empty
     * string then it will return the alternative value supplied.
     *
     * @param string $classname the classname to be sanitized
     * @param string $fallback  Optional. The value to return if the sanitization ends up as an empty string.
     *                          Default empty string.
     *
     * @return string the sanitized value
     *
     * @todo Expand to support the full range of CDATA that a class attribute can contain.
     *
     * @since 1.0.0
     */
    public static function sanitize_html_class(string $classname, string $fallback = ''): string
    {
        // Strip out any percent-encoded characters.
        $sanitized = preg_replace('|%[a-fA-F0-9][a-fA-F0-9]|', '', $classname);

        // Limit to A-Z, a-z, 0-9, '_', '-'.
        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', $sanitized);

        if ('' === $sanitized && $fallback) {
            return self::sanitize_html_class($fallback);
        }

        /**
         * Filters a sanitized HTML class string.
         *
         * @param string $sanitized the sanitized HTML class
         * @param string $classname HTML class before sanitization
         * @param string $fallback  the fallback string
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('sanitize_html_class', $sanitized, $classname, $fallback);
    }

    /**
     * Strips out all characters not allowed in a locale name.
     *
     * @param string $locale_name the locale name to be sanitized
     *
     * @return string the sanitized value
     *
     * @since 1.0.0
     */
    public static function sanitize_locale_name(string $locale_name): string
    {
        // Limit to A-Z, a-z, 0-9, '_', '-'.
        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', $locale_name);

        /**
         * Filters a sanitized locale name string.
         *
         * @param string $sanitized   the sanitized locale name
         * @param string $locale_name the locale name before sanitization
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('sanitize_locale_name', $sanitized, $locale_name);
    }

    /**
     * Converts invalid Unicode references range to valid range.
     *
     * @param string $content string with entities that need converting
     *
     * @return string converted string
     *
     * @since 1.0.0
     */
    public static function convert_invalid_entities(string $content): string
    {
        $tp_htmltranswinuni = [
            '&#128;' => '&#8364;', // The Euro sign.
            '&#129;' => '',
            '&#130;' => '&#8218;', // These are Windows CP1252 specific characters.
            '&#131;' => '&#402;',  // They would look weird on non-Windows browsers.
            '&#132;' => '&#8222;',
            '&#133;' => '&#8230;',
            '&#134;' => '&#8224;',
            '&#135;' => '&#8225;',
            '&#136;' => '&#710;',
            '&#137;' => '&#8240;',
            '&#138;' => '&#352;',
            '&#139;' => '&#8249;',
            '&#140;' => '&#338;',
            '&#141;' => '',
            '&#142;' => '&#381;',
            '&#143;' => '',
            '&#144;' => '',
            '&#145;' => '&#8216;',
            '&#146;' => '&#8217;',
            '&#147;' => '&#8220;',
            '&#148;' => '&#8221;',
            '&#149;' => '&#8226;',
            '&#150;' => '&#8211;',
            '&#151;' => '&#8212;',
            '&#152;' => '&#732;',
            '&#153;' => '&#8482;',
            '&#154;' => '&#353;',
            '&#155;' => '&#8250;',
            '&#156;' => '&#339;',
            '&#157;' => '',
            '&#158;' => '&#382;',
            '&#159;' => '&#376;',
        ];

        if (str_contains($content, '&#1')) {
            $content = strtr($content, $tp_htmltranswinuni);
        }

        return $content;
    }

    /**
     * Normalizes EOL characters and strips duplicate whitespace.
     *
     * @param string $str the string to normalize
     *
     * @return string the normalized string
     *
     * @since 1.0.0
     */
    public static function normalize_whitespace(string $str): string
    {
        $str = trim($str);
        $str = str_replace("\r", "\n", $str);
        $str = preg_replace(['/\n+/', '/[ \t]+/'], ["\n", ' '], $str);

        return $str;
    }

    /**
     * Properly strips all HTML tags including 'script' and 'style'.
     *
     * This differs from strip_tags() because it removes the contents of
     * the `<script>` and `<style>` tags. E.g. `strip_tags( '<script>something</script>' )`
     * will return 'something'. tp_strip_all_tags() will return an empty string.
     *
     * @param string $text          String containing HTML tags
     * @param bool   $remove_breaks Optional. Whether to remove left over line breaks and white space chars
     *
     * @return string the processed string
     *
     * @since 1.0.0
     */
    public static function tp_strip_all_tags(string $text, bool $remove_breaks = false): string
    {
        $text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text);
        $text = strip_tags($text);

        if ($remove_breaks) {
            $text = @preg_replace('/[\r\n\t ]+/', ' ', $text);
        }

        return trim($text);
    }

    /**
     * Verifies that an email is valid.
     *
     * Does not grok i18n domains. Not RFC compliant.
     *
     * @param string $email email address to verify
     *
     * @return string|false valid email address on success, false on failure
     *
     * @since 1.0.0
     */
    public static function is_email(string $email): false|string
    {
        // Test for the minimum length the email can be.
        if (strlen($email) < 6) {
            /**
             * Filters whether an email address is valid.
             *
             * This filter is evaluated under several different contexts, such as 'email_too_short',
             * 'email_no_at', 'local_invalid_chars', 'domain_period_sequence', 'domain_period_limits',
             * 'domain_no_periods', 'sub_hyphen_limits', 'sub_invalid_chars', or no specific context.
             *
             * @param string|false $is_email the email address if successfully passed the is_email() checks, false otherwise
             * @param string       $email    the email address being checked
             * @param string       $context  context under which the email was tested
             *
             * @since 1.0.0
             */
            return Hook::applyFilter('is_email', false, $email, 'email_too_short');
        }

        // Test for an @ character after the first position.
        if (false === strpos($email, '@', 1)) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('is_email', false, $email, 'email_no_at');
        }

        // Split out the local and domain parts.
        [$local, $domain] = explode('@', $email, 2);

        /*
         * LOCAL PART
         * Test for invalid characters.
         */
        if (!preg_match('/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]+$/', $local)) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('is_email', false, $email, 'local_invalid_chars');
        }

        /*
         * DOMAIN PART
         * Test for sequences of periods.
         */
        if (preg_match('/\.{2,}/', $domain)) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('is_email', false, $email, 'domain_period_sequence');
        }

        // Test for leading and trailing periods and whitespace.
        if (trim($domain, " \t\n\r\0\x0B.") !== $domain) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('is_email', false, $email, 'domain_period_limits');
        }

        // Split the domain into subs.
        $subs = explode('.', $domain);

        // Assume the domain will have at least two subs.
        if (2 > count($subs)) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('is_email', false, $email, 'domain_no_periods');
        }

        // Loop through each sub.
        foreach ($subs as $sub) {
            // Test for leading and trailing hyphens and whitespace.
            if (trim($sub, " \t\n\r\0\x0B-") !== $sub) {
                /* This filter is documented in wp-includes/formatting.php */
                return Hook::applyFilter('is_email', false, $email, 'sub_hyphen_limits');
            }

            // Test for invalid characters.
            if (!preg_match('/^[a-z0-9-]+$/i', $sub)) {
                /* This filter is documented in wp-includes/formatting.php */
                return Hook::applyFilter('is_email', false, $email, 'sub_invalid_chars');
            }
        }

        // Congratulations, your email made it!
        /* This filter is documented in wp-includes/formatting.php */
        return Hook::applyFilter('is_email', $email, $email, null);
    }

    /**
     * Strips out all characters that are not allowable in an email.
     *
     * @param string $email email address to filter
     *
     * @return string filtered email address
     *
     * @since 1.0.0
     */
    public static function sanitize_email(string $email): string
    {
        // Test for the minimum length the email can be.
        if (strlen($email) < 6) {
            /**
             * Filters a sanitized email address.
             *
             * This filter is evaluated under several contexts, including 'email_too_short',
             * 'email_no_at', 'local_invalid_chars', 'domain_period_sequence', 'domain_period_limits',
             * 'domain_no_periods', 'domain_no_valid_subs', or no context.
             *
             * @param string      $sanitized_email the sanitized email address
             * @param string      $email           the email address, as provided to sanitize_email()
             * @param string|null $message         A message to pass to the user. null if email is sanitized.
             *
             * @since 1.0.0
             */
            return Hook::applyFilter('sanitize_email', '', $email, 'email_too_short');
        }

        // Test for an @ character after the first position.
        if (false === strpos($email, '@', 1)) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('sanitize_email', '', $email, 'email_no_at');
        }

        // Split out the local and domain parts.
        [$local, $domain] = explode('@', $email, 2);

        /*
         * LOCAL PART
         * Test for invalid characters.
         */
        $local = preg_replace('/[^a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]/', '', $local);
        if ('' === $local) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('sanitize_email', '', $email, 'local_invalid_chars');
        }

        /*
         * DOMAIN PART
         * Test for sequences of periods.
         */
        $domain = preg_replace('/\.{2,}/', '', $domain);
        if ('' === $domain) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('sanitize_email', '', $email, 'domain_period_sequence');
        }

        // Test for leading and trailing periods and whitespace.
        $domain = trim($domain, " \t\n\r\0\x0B.");
        if ('' === $domain) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('sanitize_email', '', $email, 'domain_period_limits');
        }

        // Split the domain into subs.
        $subs = explode('.', $domain);

        // Assume the domain will have at least two subs.
        if (2 > count($subs)) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('sanitize_email', '', $email, 'domain_no_periods');
        }

        // Create an array that will contain valid subs.
        $new_subs = [];

        // Loop through each sub.
        foreach ($subs as $sub) {
            // Test for leading and trailing hyphens.
            $sub = trim($sub, " \t\n\r\0\x0B-");

            // Test for invalid characters.
            $sub = preg_replace('/[^a-z0-9-]+/i', '', $sub);

            // If there's anything left, add it to the valid subs.
            if ('' !== $sub) {
                $new_subs[] = $sub;
            }
        }

        // If there aren't 2 or more valid subs.
        if (2 > count($new_subs)) {
            /* This filter is documented in wp-includes/formatting.php */
            return Hook::applyFilter('sanitize_email', '', $email, 'domain_no_valid_subs');
        }

        // Join valid subs into the new domain.
        $domain = implode('.', $new_subs);

        // Put the email back together.
        $sanitized_email = $local.'@'.$domain;

        // Congratulations, your email made it!
        /* This filter is documented in wp-includes/formatting.php */
        return Hook::applyFilter('sanitize_email', $sanitized_email, $email, null);
    }

    /**
     * Checks and cleans a URL.
     *
     * A number of characters are removed from the URL. If the URL is for displaying
     * (the default behavior) ampersands are also replaced. The {@see 'clean_url'} filter
     * is applied to the returned cleaned URL.
     *
     * @param string        $url       the URL to be cleaned
     * @param string[]|null $protocols Optional. An array of acceptable protocols.
     *                                 Defaults to return value of tp_allowed_protocols().
     * @param string        $_context  Private. Use sanitize_url() for database usage.
     *
     * @return string The cleaned URL after the {@see 'clean_url'} filter is applied.
     *                An empty string is returned if `$url` specifies a protocol other than
     *                those in `$protocols`, or if `$url` contains an empty string.
     *
     * @since 1.0.0
     */
    public static function esc_url(string $url, ?array $protocols = null, string $_context = 'display'): string
    {
        $original_url = $url;

        if ('' === $url) {
            return $url;
        }

        $url = str_replace(' ', '%20', ltrim($url));
        $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);

        if ('' === $url) {
            return $url;
        }

        if (0 !== stripos($url, 'mailto:')) {
            $strip = ['%0d', '%0a', '%0D', '%0A'];
            $url = self::_deep_replace($strip, $url);
        }

        $url = str_replace(';//', '://', $url);
        /*
         * If the URL doesn't appear to contain a scheme, we presume
         * it needs https:// prepended (unless it's a relative link
         * starting with /, # or ?, or a PHP file).
         */
        if (!str_contains($url, ':') && !in_array($url[0], ['/', '#', '?'], true)
            && !preg_match('/^[a-z0-9-]+?\.php/i', $url)
        ) {
            $url = 'https://'.$url;
        }

        // Replace ampersands and single quotes only when displaying.
        if ('display' === $_context) {
            $url = str_replace('&amp;', '&#038;', $url);
            $url = str_replace("'", '&#039;', $url);
        }

        if (str_contains($url, '[') || str_contains($url, ']')) {
            $parsed = parse_url($url);
            $front = '';

            if (isset($parsed['scheme'])) {
                $front .= $parsed['scheme'].'://';
            } elseif ('/' === $url[0]) {
                $front .= '//';
            }

            if (isset($parsed['user'])) {
                $front .= $parsed['user'];
            }

            if (isset($parsed['pass'])) {
                $front .= ':'.$parsed['pass'];
            }

            if (isset($parsed['user']) || isset($parsed['pass'])) {
                $front .= '@';
            }

            if (isset($parsed['host'])) {
                $front .= $parsed['host'];
            }

            if (isset($parsed['port'])) {
                $front .= ':'.$parsed['port'];
            }

            $end_dirty = str_replace($front, '', $url);
            $end_clean = str_replace(['[', ']'], ['%5B', '%5D'], $end_dirty);
            $url = str_replace($end_dirty, $end_clean, $url);
        }

        $good_protocol_url = $url;
        if (is_array($protocols) && isset($parsed['scheme']) && !in_array($parsed['scheme'], $protocols, true)) {
            $good_protocol_url = '';
        }

        /**
         * Filters a string cleaned and escaped for output as a URL.
         *
         * @param string $good_protocol_url the cleaned URL to be returned
         * @param string $original_url      the URL prior to cleaning
         * @param string $_context          if 'display', replace ampersands and single quotes only
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('esc_url', $good_protocol_url, $original_url, $_context);
    }

    /**
     * Sanitizes a URL for database or redirect usage.
     *
     * @param string        $url       the URL to be cleaned
     * @param string[]|null $protocols Optional. An array of acceptable protocols.
     *                                 Defaults to return value of tp_allowed_protocols().
     *
     * @return string the cleaned URL after esc_url() is run with the 'db' context
     *
     * @since 1.0.0
     * @see esc_url()
     */
    public static function sanitize_url(string $url, ?array $protocols = null): string
    {
        return self::esc_url($url, $protocols, 'db');
    }

    /**
     * Escapes single quotes, `"`, `<`, `>`, `&`, and fixes line endings.
     *
     * Escapes text strings for echoing in JS. It is intended to be used for inline JS
     * (in a tag attribute, for example `onclick="..."`). Note that the strings have to
     * be in single quotes. The {@see 'js_escape'} filter is also applied here.
     *
     * @param string $text the text to be escaped
     *
     * @return string escaped text
     *
     * @since 1.0.0
     */
    public static function esc_js(string $text): string
    {
        $safe_text = self::tp_check_invalid_utf8($text);
        $safe_text = self::_tp_specialchars($safe_text, ENT_COMPAT);
        $safe_text = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes($safe_text));
        $safe_text = str_replace("\r", '', $safe_text);
        $safe_text = str_replace("\n", '\\n', addslashes($safe_text));

        /**
         * Filters a string cleaned and escaped for output in JavaScript.
         *
         * Text passed to esc_js() is stripped of invalid or special characters,
         * and properly slashed for output.
         *
         * @param string $safe_text the text after it has been escaped
         * @param string $text      the text prior to being escaped
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('esc_js', $safe_text, $text);
    }

    /**
     * Escaping for HTML blocks.
     *
     * @since 1.0.0
     */
    public static function esc_html(string $text): string
    {
        $safe_text = self::tp_check_invalid_utf8($text);
        $safe_text = self::_tp_specialchars($safe_text, ENT_QUOTES);

        /**
         * Filters a string cleaned and escaped for output in HTML.
         *
         * Text passed to esc_html() is stripped of invalid or special characters
         * before output.
         *
         * @param string $safe_text the text after it has been escaped
         * @param string $text      the text prior to being escaped
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('esc_html', $safe_text, $text);
    }

    /**
     * Escaping for HTML attributes.
     *
     * @since 1.0.0
     */
    public static function esc_attr(string $text): string
    {
        $safe_text = self::tp_check_invalid_utf8($text);
        $safe_text = self::_tp_specialchars($safe_text, ENT_QUOTES);

        /**
         * Filters a string cleaned and escaped for output in an HTML attribute.
         *
         * Text passed to esc_attr() is stripped of invalid or special characters
         * before output.
         *
         * @param string $safe_text the text after it has been escaped
         * @param string $text      the text prior to being escaped
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('esc_attr', $safe_text, $text);
    }

    /**
     * Escaping for textarea values.
     *
     * @since 1.0.0
     */
    public static function esc_textarea(string $text): string
    {
        $safe_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        /**
         * Filters a string cleaned and escaped for output in a textarea element.
         *
         * @param string $safe_text the text after it has been escaped
         * @param string $text      the text prior to being escaped
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('esc_textarea', $safe_text, $text);
    }

    /**
     * Escaping for XML blocks.
     *
     * @param string $text text to escape
     *
     * @return string escaped text
     *
     * @since 1.0.0
     */
    public static function esc_xml(string $text): string
    {
        $safe_text = self::tp_check_invalid_utf8($text);

        $cdata_regex = '\<\!\[CDATA\[.*?\]\]\>';
        $regex = <<<EOF
            /
            	(?=.*?{$cdata_regex})                 # lookahead that will match anything followed by a CDATA Section
            	(?<non_cdata_followed_by_cdata>(.*?)) # the "anything" matched by the lookahead
            	(?<cdata>({$cdata_regex}))            # the CDATA Section matched by the lookahead

            |	                                      # alternative

            	(?<non_cdata>(.*))                    # non-CDATA Section
            /sx
            EOF;

        $safe_text = (string) preg_replace_callback(
            $regex,
            static function ($matches) {
                if (!isset($matches[0])) {
                    return '';
                }

                if (isset($matches['non_cdata'])) {
                    // escape HTML entities in the non-CDATA Section.
                    return self::_tp_specialchars($matches['non_cdata'], ENT_XML1);
                }

                // Return the CDATA Section unchanged, escape HTML entities in the rest.
                return self::_tp_specialchars($matches['non_cdata_followed_by_cdata'], ENT_XML1).$matches['cdata'];
            },
            $safe_text
        );

        /**
         * Filters a string cleaned and escaped for output in XML.
         *
         * Text passed to esc_xml() is stripped of invalid or special characters
         * before output. HTML named character references are converted to their
         * equivalent code points.
         *
         * @param string $safe_text the text after it has been escaped
         * @param string $text      the text prior to being escaped
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('esc_xml', $safe_text, $text);
    }

    /**
     * Escapes an HTML tag name.
     *
     * @since 1.0.0
     */
    public static function escape_tag(string $tag_name): string
    {
        $safe_tag = strtolower(preg_replace('/[^a-zA-Z0-9-_:]/', '', $tag_name));

        /**
         * Filters a string cleaned and escaped for output as an HTML tag.
         *
         * @param string $safe_tag the tag name after it has been escaped
         * @param string $tag_name the text before it was escaped
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('escape_tag', $safe_tag, $tag_name);
    }

    /**
     * Sanitizes a string from user input or from the database.
     *
     * - Checks for invalid UTF-8,
     * - Converts single `<` characters to entities
     * - Strips all tags
     * - Removes line breaks, tabs, and extra whitespace
     * - Strips percent-encoded characters
     *
     * @param string $str string to sanitize
     *
     * @return string sanitized string
     *
     * @since 1.0.0
     * @see sanitize_textarea_field()
     * @see tp_check_invalid_utf8()
     * @see tp_strip_all_tags()
     */
    public static function sanitize_text_field(string $str): string
    {
        $filtered = self::_sanitize_text_fields($str, false);

        /**
         * Filters a sanitized text field string.
         *
         * @param string $filtered the sanitized string
         * @param string $str      the string prior to being sanitized
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('sanitize_text_field', $filtered, $str);
    }

    /**
     * Sanitizes a multiline string from user input or from the database.
     *
     * The function is like sanitize_text_field(), but preserves
     * new lines (\n) and other whitespace, which are legitimate
     * input in textarea elements.
     *
     * @param string $str string to sanitize
     *
     * @return string sanitized string
     *
     * @since 1.0.0
     * @see sanitize_text_field()
     */
    public static function sanitize_textarea_field(string $str): string
    {
        $filtered = self::_sanitize_text_fields($str, true);

        /**
         * Filters a sanitized textarea field string.
         *
         * @param string $filtered the sanitized string
         * @param string $str      the string prior to being sanitized
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('sanitize_textarea_field', $filtered, $str);
    }

    /**
     * Internal helper function to sanitize a string from user input or from the database.
     *
     * @param string $str           string to sanitize
     * @param bool   $keep_newlines Optional. Whether to keep newlines. Default: false.
     *
     * @return string sanitized string
     *
     * @since 1.0.0
     */
    public static function _sanitize_text_fields(string $str, bool $keep_newlines = false): string
    {
        $filtered = self::tp_check_invalid_utf8($str);

        if (str_contains($filtered, '<')) {
            // This will strip extra whitespace for us.
            $filtered = self::tp_strip_all_tags($filtered, false);

            /*
             * Use HTML entities in a special case to make sure that
             * later newline stripping stages cannot lead to a functional tag.
             */
            $filtered = str_replace("<\n", "&lt;\n", $filtered);
        }

        if (!$keep_newlines) {
            $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
        }
        $filtered = trim($filtered);

        // Remove percent-encoded characters.
        $found = false;
        while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
            $filtered = str_replace($match[0], '', $filtered);
            $found = true;
        }

        if ($found) {
            // Strip out the whitespace that may now exist after removing percent-encoded characters.
            $filtered = trim(preg_replace('/ +/', ' ', $filtered));
        }

        return $filtered;
    }

    /**
     * Sanitizes a mime type.
     *
     * @param string $mime_type mime type
     *
     * @return string sanitized mime type
     *
     * @since 1.0.0
     */
    public static function sanitize_mime_type(string $mime_type): string
    {
        $sani_mime_type = preg_replace('/[^-+*.a-zA-Z0-9\/]/', '', $mime_type);

        /**
         * Filters a mime type following sanitization.
         *
         * @param string $sani_mime_type the sanitized mime type
         * @param string $mime_type      the mime type prior to sanitization
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('sanitize_mime_type', $sani_mime_type, $mime_type);
    }

    /**
     * Sanitizes space or carriage return separated URLs that are used to send trackbacks.
     *
     * @param string $to_ping Space or carriage return separated URLs
     *
     * @return string URLs starting with the http or https protocol, separated by a carriage return
     *
     * @since 1.0.0
     */
    public static function sanitize_trackback_urls(string $to_ping): string
    {
        $urls_to_ping = preg_split('/[\r\n\t ]/', trim($to_ping), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($urls_to_ping as $k => $url) {
            if (!preg_match('#^https?://.#i', $url)) {
                unset($urls_to_ping[$k]);
            }
        }
        $urls_to_ping = array_map('sanitize_url', $urls_to_ping);
        $urls_to_ping = implode("\n", $urls_to_ping);

        /**
         * Filters a list of trackback URLs following sanitization.
         *
         * The string returned here consists of a space or carriage return-delimited list
         * of trackback URLs.
         *
         * @param string $urls_to_ping sanitized space or carriage return separated URLs
         * @param string $to_ping      space or carriage return separated URLs before sanitization
         *
         * @since 1.0.0
         */
        return Hook::applyFilter('sanitize_trackback_urls', $urls_to_ping, $to_ping);
    }

    /**
     * Sanitizes a hex color.
     *
     * Returns either '', a 3 or 6 digit hex color (with #), or nothing.
     * For sanitizing values without a #, see sanitize_hex_color_no_hash().
     *
     * @return string|void
     *
     * @since 1.0.0
     */
    public static function sanitize_hex_color(string $color)
    {
        if ('' === $color) {
            return '';
        }

        // 3 or 6 hex digits, or the empty string.
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
    }

    /**
     * Sanitizes a hex color without a hash. Use sanitize_hex_color() when possible.
     *
     * Saving hex colors without a hash puts the burden of adding the hash on the
     * UI, which makes it difficult to use or upgrade to other color types such as
     * rgba, hsl, rgb, and HTML color names.
     *
     * Returns either '', a 3 or 6 digit hex color (without a #), or null.
     *
     * @since 1.0.0
     */
    public static function sanitize_hex_color_no_hash(string $color): ?string
    {
        $color = ltrim($color, '#');

        if ('' === $color) {
            return '';
        }

        return self::sanitize_hex_color('#'.$color) ? $color : null;
    }

    /**
     * Ensures that any hex color is properly hashed.
     * Otherwise, returns value untouched.
     *
     * This method should only be necessary if using sanitize_hex_color_no_hash().
     *
     * @since 1.0.0
     */
    public static function maybe_hash_hex_color(string $color): string
    {
        $unhashed = self::sanitize_hex_color_no_hash($color);
        if ($unhashed) {
            return '#'.$unhashed;
        }

        return $color;
    }

    /**
     * Adds slashes to a string or recursively adds slashes to strings within an array.
     *
     * This should be used when preparing data for core API that expects slashed data.
     * This should not be used to escape data going directly into an SQL query.
     *
     * @param array|string $value string or array of data to slash
     *
     * @return string|array slashed `$value`, in the same type as supplied
     *
     * @since 1.0.0
     */
    public static function tp_slash(array|string $value): array|string
    {
        if (is_array($value)) {
            $value = array_map('tp_slash', $value);
        }

        if (is_string($value)) {
            return addslashes($value);
        }

        return $value;
    }

    /**
     * Removes slashes from a string or recursively removes slashes from strings within an array.
     *
     * This should be used to remove slashes from data passed to core API that
     * expects data to be unslashed.
     *
     * @param array|string $value string or array of data to unslash
     *
     * @return string|array unslashed `$value`, in the same type as supplied
     *
     * @since 1.0.0
     */
    public static function tp_unslash(array|string $value): array|string
    {
        return self::stripslashes_deep($value);
    }

    /**
     * Converts a number of special characters into their HTML entities.
     *
     * Specifically deals with: `&`, `<`, `>`, `"`, and `'`.
     *
     * `$quote_style` can be set to ENT_COMPAT to encode `"` to
     * `&quot;`, or ENT_QUOTES to do both. Default is ENT_NOQUOTES where no quotes are encoded.
     *
     * @param string       $text          the text which is to be encoded
     * @param int|string   $quote_style   Optional. Converts double quotes if set to ENT_COMPAT,
     *                                    both single and double if set to ENT_QUOTES or none if set to ENT_NOQUOTES.
     *                                    Converts single and double quotes, as well as converting HTML
     *                                    named entities (that are not also XML named entities) to their
     *                                    code points if set to ENT_XML1. Also compatible with old values;
     *                                    converting single quotes if set to 'single',
     *                                    double if set to 'double' or both if otherwise set.
     *                                    Default is ENT_NOQUOTES.
     * @param false|string $charset       Optional. The character encoding of the string. Default false.
     * @param bool         $double_encode Optional. Whether to encode existing HTML entities. Default false.
     *
     * @return string the encoded text with HTML entities
     *
     * @since 1.0.0
     */
    public static function _tp_specialchars(string $text, int|string $quote_style = ENT_NOQUOTES, false|string $charset = false, bool $double_encode = false): string
    {
        if ('' === $text) {
            return '';
        }

        // Don't bother if there are no specialchars - saves some processing.
        if (!preg_match('/[&<>"\']/', $text)) {
            return $text;
        }

        // Account for the previous behavior of the function when the $quote_style is not an accepted value.
        if (empty($quote_style)) {
            $quote_style = ENT_NOQUOTES;
        } elseif (ENT_XML1 === $quote_style) {
            $quote_style = ENT_QUOTES | ENT_XML1;
        } elseif (!in_array($quote_style, [ENT_NOQUOTES, ENT_COMPAT, ENT_QUOTES, 'single', 'double'], true)) {
            $quote_style = ENT_QUOTES;
        }

        if ('double' === $quote_style) {
            $quote_style = ENT_COMPAT;
        } elseif ('single' === $quote_style) {
            $quote_style = ENT_NOQUOTES;
        }

        $text = htmlspecialchars($text, $quote_style, 'UTF-8', $double_encode);

        return $text;
    }

    /**
     * Parses a string into variables to be stored in an array.
     *
     * @param string $input_string the string to be parsed
     * @param array  $result       variables will be stored in this array
     *
     * @since 1.0.0
     */
    public static function tp_parse_str(string $input_string, array &$result): void
    {
        parse_str($input_string, $result);

        /**
         * Filters the array of variables derived from a parsed string.
         *
         * @param array $result the array populated with variables
         *
         * @since 1.0.0
         */
        $result = Hook::applyFilter('tp_parse_str', $result);
    }

    /**
     * Extracts and returns the first URL from passed content.
     *
     * @param string $content a string which might contain a URL
     *
     * @return string|false the found URL
     *
     * @since 1.0.0
     */
    public static function get_url_in_content(string $content): false|string
    {
        if (empty($content)) {
            return false;
        }

        if (preg_match('/<a\s[^>]*?href=([\'"])(.+?)\1/is', $content, $matches)) {
            return self::sanitize_url($matches[2]);
        }

        return false;
    }

    /**
     * TyPrint's implementation of PHP sprintf() with filters.
     *
     * @param string $pattern the string which formatted args are inserted
     * @param mixed  ...$args Arguments to be formatted into the $pattern string.
     *
     * @return string the formatted string
     *
     * @since 1.0.0
     * @see https://www.php.net/sprintf
     */
    public static function tp_sprintf(string $pattern, ...$args): string
    {
        $len = strlen($pattern);
        $start = 0;
        $result = '';
        $arg_index = 0;

        while ($len > $start) {
            // Last character: append and break.
            if (strlen($pattern) - 1 === $start) {
                $result .= substr($pattern, -1);
                break;
            }

            // Literal %: append and continue.
            if ('%%' === substr($pattern, $start, 2)) {
                $start += 2;
                $result .= '%';
                continue;
            }

            // Get fragment before next %.
            $end = strpos($pattern, '%', $start + 1);
            if (false === $end) {
                $end = $len;
            }
            $fragment = substr($pattern, $start, $end - $start);

            // Fragment has a specifier.
            if ('%' === $pattern[$start]) {
                // Find numbered arguments or take the next one in order.
                if (preg_match('/^%(\d+)\$/', $fragment, $matches)) {
                    $index = $matches[1] - 1; // 0-based array vs 1-based sprintf() arguments.
                    $arg = $args[$index] ?? '';
                    $fragment = str_replace("%{$matches[1]}$", '%', $fragment);
                } else {
                    $arg = $args[$arg_index] ?? '';
                    ++$arg_index;
                }

                /**
                 * Filters a fragment from the pattern passed to tp_sprintf().
                 *
                 * If the fragment is unchanged, then sprintf() will be run on the fragment.
                 *
                 * @param string $fragment a fragment from the pattern
                 * @param string $arg      the argument
                 *
                 * @since 1.0.0
                 */
                $_fragment = Hook::applyFilter('tp_sprintf', $fragment, $arg);

                if ($_fragment !== $fragment) {
                    $fragment = $_fragment;
                } else {
                    $fragment = sprintf($fragment, (string) $arg);
                }
            }

            // Append to result and move to next fragment.
            $result .= $fragment;
            $start = $end;
        }

        return $result;
    }

    /**
     * Localizes list items before the rest of the content.
     *
     * The '%l' must be at the first characters can then contain the rest of the
     * content. The list items will have ', ', ', and', and ' and ' added depending
     * on the amount of list items in the $args parameter.
     *
     * @param string $pattern content containing '%l' at the beginning
     * @param array  $args    list items to prepend to the content and replace '%l'
     *
     * @return string localized list items and rest of the content
     *
     * @since 1.0.0
     */
    public static function tp_sprintf_l(string $pattern, array $args): string
    {
        // Not a match.
        if (!str_starts_with($pattern, '%l')) {
            return $pattern;
        }

        // Nothing to work with.
        if (empty($args)) {
            return '';
        }

        /**
         * Filters the translated delimiters used by tp_sprintf_l().
         * Placeholders (%s) are included to assist translators and then
         * removed before the array of strings reaches the filter.
         *
         * Please note: Ampersands and entities should be avoided here.
         *
         * @param array $delimiters an array of translated delimiters
         *
         * @since 1.0.0
         */
        $l = Hook::applyFilter(
            'tp_sprintf_l',
            [
                /* translators: Used to join items in a list with more than 2 items. */
                'between' => sprintf(L10n::__('%1$s, %2$s'), '', ''),
                /* translators: Used to join last two items in a list with more than 2 times. */
                'between_last_two' => sprintf(L10n::__('%1$s, and %2$s'), '', ''),
                /* translators: Used to join items in a list with only 2 items. */
                'between_only_two' => sprintf(L10n::__('%1$s and %2$s'), '', ''),
            ]
        );

        $result = array_shift($args);
        if (1 === count($args)) {
            $result .= $l['between_only_two'].array_shift($args);
        }

        // Loop when more than two args.
        $i = count($args);
        while ($i) {
            $arg = array_shift($args);
            --$i;
            if (0 === $i) {
                $result .= $l['between_last_two'].$arg;
            } else {
                $result .= $l['between'].$arg;
            }
        }

        return $result.substr($pattern, 2);
    }

    /**
     * Safely extracts not more than the first $count characters from HTML string.
     *
     * UTF-8, tags and entities safe prefix extraction. Entities inside will *NOT*
     * be counted as one character. For example &amp; will be counted as 4, &lt; as
     * 3, etc.
     *
     * @param string      $str   string to get the excerpt from
     * @param int         $count maximum number of characters to take
     * @param string|null $more  Optional. What to append if $str needs to be trimmed. Defaults to empty string.
     *
     * @return string the excerpt
     *
     * @since 1.0.0
     */
    public static function tp_html_excerpt(string $str, int $count, ?string $more = null): string
    {
        if (null === $more) {
            $more = '';
        }

        $str = self::tp_strip_all_tags($str, true);
        $excerpt = mb_substr($str, 0, $count);

        // Remove part of an entity at the end.
        $excerpt = preg_replace('/&[^;\s]{0,6}$/', '', $excerpt);

        if ($str !== $excerpt) {
            $excerpt = trim($excerpt).$more;
        }

        return $excerpt;
    }

    /**
     * Maps a function to all non-iterable elements of an array or an object.
     *
     * This is similar to `array_walk_recursive()` but acts upon objects too.
     *
     * @param mixed    $value    the array, object, or scalar
     * @param callable $callback the function to map onto $value
     *
     * @return mixed the value with the callback applied to all non-arrays and non-objects inside it
     *
     * @since 1.0.0
     */
    public static function map_deep(mixed $value, callable $callback): mixed
    {
        if (is_array($value)) {
            foreach ($value as $index => $item) {
                $value[$index] = self::map_deep($item, $callback);
            }
        } elseif (is_object($value)) {
            $object_vars = get_object_vars($value);
            foreach ($object_vars as $property_name => $property_value) {
                $value->$property_name = self::map_deep($property_value, $callback);
            }
        } else {
            $value = call_user_func($callback, $value);
        }

        return $value;
    }

    /**
     * Navigates through an array, object, or scalar, and removes slashes from the values.
     *
     * @param mixed $value the value to be stripped
     *
     * @return mixed stripped value
     *
     * @since 1.0.0
     */
    public static function stripslashes_deep(mixed $value): mixed
    {
        return self::map_deep($value, 'stripslashes_from_strings_only');
    }

    /**
     * Callback function for `stripslashes_deep()` which strips slashes from strings.
     *
     * @param mixed $value the array or string to be stripped
     *
     * @return mixed the stripped value
     *
     * @since 1.0.0
     */
    public static function stripslashes_from_strings_only(mixed $value): mixed
    {
        return is_string($value) ? stripslashes($value) : $value;
    }

    /**
     * Navigates through an array, object, or scalar, and encodes the values to be used in a URL.
     *
     * @param mixed $value the array or string to be encoded
     *
     * @return mixed the encoded value
     *
     * @since 1.0.0
     */
    public static function urlencode_deep(mixed $value): mixed
    {
        return self::map_deep($value, 'urlencode');
    }

    /**
     * Navigates through an array, object, or scalar, and raw-encodes the values to be used in a URL.
     *
     * @param mixed $value the array or string to be encoded
     *
     * @return mixed the encoded value
     *
     * @since 1.0.0
     */
    public static function rawurlencode_deep(mixed $value): mixed
    {
        return self::map_deep($value, 'rawurlencode');
    }

    /**
     * Navigates through an array, object, or scalar, and decodes URL-encoded values.
     *
     * @param mixed $value the array or string to be decoded
     *
     * @return mixed the decoded value
     *
     * @since 1.0.0
     */
    public static function urldecode_deep(mixed $value): mixed
    {
        return self::map_deep($value, 'urldecode');
    }

    /**
     * Performs a deep string replace operation to ensure the values in $search are no longer present.
     *
     * Repeats the replacement operation until it no longer replaces anything to remove "nested" values
     * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that
     * str_replace would return
     *
     * @param array|string $search  The value being searched for, otherwise known as the needle.
     *                              An array may be used to designate multiple needles.
     * @param string       $subject the string being searched and replaced on, otherwise known as the haystack
     *
     * @return string the string with the replaced values
     *
     * @since 1.0.0
     */
    public static function _deep_replace(array|string $search, string $subject): string
    {
        $count = 1;
        while ($count) {
            $subject = str_replace($search, '', $subject, $count);
        }

        return $subject;
    }
}
