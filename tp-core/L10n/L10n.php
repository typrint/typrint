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

namespace TP\L10n;

use Gettext\Loader\MoLoader;
use Gettext\Translations;
use TP\Facades\Hook;
use TP\Formatting\Formatting;
use TP\Utils\Once;

class L10n
{
    private static L10n $instance;

    private static Once $once;

    public static function init(): void
    {
        self::$once = new Once();
    }

    public static function instance(): self
    {
        if (!isset(self::$instance)) {
            self::$once->do(fn () => self::$instance = new self());
        }

        return self::$instance;
    }

    /**
     * Returns the Translations instance for a text domain.
     *
     * If there isn't one, returns empty Translations instance.
     *
     * @param string $domain Text domain. Unique identifier for retrieving translated strings.
     *
     *@since 1.0.0
     */
    public static function get_translations_for_domain(string $domain): Translations
    {
        $loader = new MoLoader();
        $translations = $loader->loadFile("{$domain}.mo");

        return $translations;
    }

    /**
     * Retrieves the translation of $text.
     *
     * If there is no translation, or the text domain isn't loaded, the original text is returned.
     *
     * *Note:* Don't use translate() directly, use __() or related functions.
     *
     * @param string $text   text to translate
     * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
     *                       Default 'default'.
     *
     * @return string translated text
     *
     * @since 1.0.0
     */
    public static function translate(string $text, string $domain = 'default'): string
    {
        $translations = self::get_translations_for_domain($domain);
        $translation = (string) $translations->find(null, $text)->getTranslation();

        /**
         * Filters text with its translation.
         *
         * @param string $translation translated text
         * @param string $text        text to translate
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter('gettext', $translation, $text, $domain);

        /**
         * Filters text with its translation for a domain.
         *
         * The dynamic portion of the hook name, `$domain`, refers to the text domain.
         *
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         * @param string $translation translated text
         * @param string $text        text to translate
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter("gettext_{$domain}", $translation, $text, $domain);

        return $translation;
    }

    /**
     * Retrieves the translation of $text in the context defined in $context.
     *
     * If there is no translation, or the text domain isn't loaded, the original text is returned.
     *
     * *Note:* Don't use translate_with_context() directly, use _x() or related functions.
     *
     * @param string $text    text to translate
     * @param string $context context information for the translators
     * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
     *                        Default 'default'.
     *
     * @return string translated text on success, original text on failure
     *
     * @since 1.0.0
     */
    public static function translate_with_context(string $text, string $context, string $domain = 'default'): string
    {
        $translations = self::get_translations_for_domain($domain);
        $translation = (string) $translations->find($context, $text)->getTranslation();

        /**
         * Filters text with its translation based on context information.
         *
         * @param string $translation translated text
         * @param string $text        text to translate
         * @param string $context     context information for the translators
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter('gettext_with_context', $translation, $text, $context, $domain);

        /**
         * Filters text with its translation based on context information for a domain.
         *
         * The dynamic portion of the hook name, `$domain`, refers to the text domain.
         *
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         * @param string $translation translated text
         * @param string $text        text to translate
         * @param string $context     context information for the translators
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter("gettext_with_context_{$domain}", $translation, $text, $context, $domain);

        return $translation;
    }

    /**
     * Translates and returns the singular or plural form of a string that's been registered
     * with _n_noop() or _nx_noop().
     *
     * Used when you want to use a translatable plural string once the number is known.
     *
     * Example:
     *
     * $message = _n_noop( '%s post', '%s posts', 'text-domain' );
     * ...
     * printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
     *
     * @param array{
     *       singular: string,
     *       plural: string,
     *       context: null,
     *       domain: string|null
     *   } $nooped_plural Array that is usually a return value from _n_noop() or _nx_noop()
     * @param int    $count  number of objects
     * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings. If $nooped_plural contains
     *                       a text domain passed to _n_noop() or _nx_noop(), it will override this value. Default 'default'.
     *
     * @return string either $singular or $plural translated text
     *
     * @since 1.0.0
     */
    public function translate_nooped_plural(array $nooped_plural, int $count, string $domain = 'default'): string
    {
        if ($nooped_plural['domain']) {
            $domain = $nooped_plural['domain'];
        }

        if ($nooped_plural['context']) {
            return self::_nx($nooped_plural['singular'], $nooped_plural['plural'], $count, $nooped_plural['context'], $domain);
        }

        return self::_n($nooped_plural['singular'], $nooped_plural['plural'], $count, $domain);
    }

    /**
     * Retrieves the translation of $text.
     *
     * If there is no translation, or the text domain isn't loaded, the original text is returned.
     *
     * @param string $text   text to translate
     * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
     *                       Default 'default'.
     *
     * @return string translated text
     *
     * @since 1.0.0
     */
    public static function __(string $text, string $domain = 'default'): string
    {
        return self::translate($text, $domain);
    }

    /**
     * Retrieves the translation of $text and escapes it for safe use in an attribute.
     *
     * If there is no translation, or the text domain isn't loaded, the original text is returned.
     *
     * @param string $text   text to translate
     * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
     *                       Default 'default'.
     *
     * @return string translated text on success, original text on failure
     *
     * @since 1.0.0
     */
    public static function esc_attr__(string $text, string $domain = 'default'): string
    {
        return Formatting::esc_attr(self::translate($text, $domain));
    }

    /**
     * Retrieves the translation of $text and escapes it for safe use in HTML output.
     *
     * If there is no translation, or the text domain isn't loaded, the original text
     * is escaped and returned.
     *
     * @param string $text   text to translate
     * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
     *                       Default 'default'.
     *
     * @return string translated text
     *
     * @since 1.0.0
     */
    public static function esc_html__(string $text, string $domain = 'default'): string
    {
        return Formatting::esc_html(self::translate($text, $domain));
    }

    /**
     * Retrieves translated string with gettext context.
     *
     * Quite a few times, there will be collisions with similar translatable text
     * found in more than two places, but with different translated context.
     *
     * By including the context in the pot file, translators can translate the two
     * strings differently.
     *
     * @param string $text    text to translate
     * @param string $context context information for the translators
     * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
     *                        Default 'default'.
     *
     * @return string translated context string without pipe
     *
     * @since 1.0.0
     */
    public static function _x(string $text, string $context, string $domain = 'default'): string
    {
        return self::translate_with_context($text, $context, $domain);
    }

    /**
     * Displays translated string with gettext context.
     *
     * @param string $text    text to translate
     * @param string $context context information for the translators
     * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
     *                        Default 'default'.
     *
     * @since 1.0.0
     */
    public static function _ex(string $text, string $context, string $domain = 'default'): void
    {
        echo self::_x($text, $context, $domain);
    }

    /**
     * Translates string with gettext context, and escapes it for safe use in an attribute.
     *
     * If there is no translation, or the text domain isn't loaded, the original text
     * is escaped and returned.
     *
     * @param string $text    text to translate
     * @param string $context context information for the translators
     * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
     *                        Default 'default'.
     *
     * @return string translated text
     *
     * @since 1.0.0
     */
    public static function esc_attr_x(string $text, string $context, string $domain = 'default'): string
    {
        return Formatting::esc_attr(self::translate_with_context($text, $context, $domain));
    }

    /**
     * Translates string with gettext context, and escapes it for safe use in HTML output.
     *
     * If there is no translation, or the text domain isn't loaded, the original text
     * is escaped and returned.
     *
     * @param string $text    text to translate
     * @param string $context context information for the translators
     * @param string $domain  Optional. Text domain. Unique identifier for retrieving translated strings.
     *                        Default 'default'.
     *
     * @return string translated text
     *
     * @since 1.0.0
     */
    public static function esc_html_x(string $text, string $context, string $domain = 'default'): string
    {
        return Formatting::esc_html(self::translate_with_context($text, $context, $domain));
    }

    /**
     * Translates and retrieves the singular or plural form based on the supplied number.
     *
     * Used when you want to use the appropriate form of a string based on whether a
     * number is singular or plural.
     *
     * Example:
     *
     *     printf( _n( '%s person', '%s people', $count, 'text-domain' ), number_format_i18n( $count ) );
     *
     * @param string $singular the text to be used if the number is singular
     * @param string $plural   the text to be used if the number is plural
     * @param int    $number   the number to compare against to use either the singular or plural form
     * @param string $domain   Optional. Text domain. Unique identifier for retrieving translated strings.
     *                         Default 'default'.
     *
     * @return string the translated singular or plural form
     *
     * @since 1.0.0
     */
    public static function _n(string $singular, string $plural, int $number, string $domain = 'default'): string
    {
        $translations = self::get_translations_for_domain($domain);
        $index = 1 == $number ? 0 : 1;
        $translated = $translations->find(null, $singular)->getPluralTranslations();
        $translation = $translated[$index] ?? (1 == $number ? $singular : $plural);

        /**
         * Filters the singular or plural form of a string.
         *
         * @param string $translation translated text
         * @param string $singular    the text to be used if the number is singular
         * @param string $plural      the text to be used if the number is plural
         * @param int    $number      the number to compare against to use either the singular or plural form
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter('ngettext', $translation, $singular, $plural, $number, $domain);

        /**
         * Filters the singular or plural form of a string for a domain.
         *
         * The dynamic portion of the hook name, `$domain`, refers to the text domain.
         *
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         * @param string $translation translated text
         * @param string $singular    the text to be used if the number is singular
         * @param string $plural      the text to be used if the number is plural
         * @param int    $number      the number to compare against to use either the singular or plural form
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter("ngettext_{$domain}", $translation, $singular, $plural, $number, $domain);

        return $translation;
    }

    /**
     * Translates and retrieves the singular or plural form based on the supplied number, with gettext context.
     *
     * This is a hybrid of _n() and _x(). It supports context and plurals.
     *
     * Used when you want to use the appropriate form of a string with context based on whether a
     * number is singular or plural.
     *
     * Example of a generic phrase which is disambiguated via the context parameter:
     *
     *     printf( _nx( '%s group', '%s groups', $people, 'group of people', 'text-domain' ), number_format_i18n( $people ) );
     *     printf( _nx( '%s group', '%s groups', $animals, 'group of animals', 'text-domain' ), number_format_i18n( $animals ) );
     *
     * @param string $singular the text to be used if the number is singular
     * @param string $plural   the text to be used if the number is plural
     * @param int    $number   the number to compare against to use either the singular or plural form
     * @param string $context  context information for the translators
     * @param string $domain   Optional. Text domain. Unique identifier for retrieving translated strings.
     *                         Default 'default'.
     *
     * @return string the translated singular or plural form
     *
     * @since 1.0.0
     */
    public static function _nx(string $singular, string $plural, int $number, string $context, string $domain = 'default'): string
    {
        $translations = self::get_translations_for_domain($domain);
        $index = 1 == $number ? 0 : 1;
        $translated = $translations->find($context, $singular)->getPluralTranslations();
        $translation = $translated[$index] ?? (1 == $number ? $singular : $plural);

        /**
         * Filters the singular or plural form of a string with gettext context.
         *
         * @param string $translation translated text
         * @param string $singular    the text to be used if the number is singular
         * @param string $plural      the text to be used if the number is plural
         * @param int    $number      the number to compare against to use either the singular or plural form
         * @param string $context     context information for the translators
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter('ngettext_with_context', $translation, $singular, $plural, $number, $context, $domain);

        /**
         * Filters the singular or plural form of a string with gettext context for a domain.
         *
         * The dynamic portion of the hook name, `$domain`, refers to the text domain.
         *
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         * @param string $translation translated text
         * @param string $singular    the text to be used if the number is singular
         * @param string $plural      the text to be used if the number is plural
         * @param int    $number      the number to compare against to use either the singular or plural form
         * @param string $context     context information for the translators
         *
         * @since 1.0.0
         */
        $translation = Hook::applyFilter("ngettext_with_context_{$domain}", $translation, $singular, $plural, $number, $context, $domain);

        return $translation;
    }

    /**
     * Registers plural strings in POT file, but does not translate them.
     *
     * Used when you want to keep structures with translatable plural
     * strings and use them later when the number is known.
     *
     * Example:
     *
     *     $message = _n_noop( '%s post', '%s posts', 'text-domain' );
     *     ...
     *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
     *
     * @param string      $singular singular form to be localized
     * @param string      $plural   plural form to be localized
     * @param string|null $domain   Optional. Text domain. Unique identifier for retrieving translated strings.
     *                              Default null.
     *
     * @return array{singular: string, plural: string, context: null, domain: string|null} Array of translation information for the strings
     *
     * @since 1.0.0
     */
    public function _n_noop(string $singular, string $plural, ?string $domain = null): array
    {
        return [
            'singular' => $singular,
            'plural' => $plural,
            'context' => null,
            'domain' => $domain,
        ];
    }

    /**
     * Registers plural strings with gettext context in POT file, but does not translate them.
     *
     * Used when you want to keep structures with translatable plural
     * strings and use them later when the number is known.
     *
     * Example of a generic phrase which is disambiguated via the context parameter:
     *
     *     $messages = array(
     *          'people'  => _nx_noop( '%s group', '%s groups', 'people', 'text-domain' ),
     *          'animals' => _nx_noop( '%s group', '%s groups', 'animals', 'text-domain' ),
     *     );
     *     ...
     *     $message = $messages[ $type ];
     *     printf( translate_nooped_plural( $message, $count, 'text-domain' ), number_format_i18n( $count ) );
     *
     * @param string      $singular singular form to be localized
     * @param string      $plural   plural form to be localized
     * @param string      $context  context information for the translators
     * @param string|null $domain   Optional. Text domain. Unique identifier for retrieving translated strings.
     *                              Default null.
     *
     * @return array{singular: string, plural: string, context: null, domain: string|null} Array of translation information for the strings
     *
     * @since 1.0.0
     */
    public function _nx_noop(string $singular, string $plural, string $context, ?string $domain = null): array
    {
        return [
            'singular' => $singular,
            'plural' => $plural,
            'context' => $context,
            'domain' => $domain,
        ];
    }
}
