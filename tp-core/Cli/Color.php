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

namespace TP\Cli;

class Color
{
    public const string RESET = "\033[0m";
    public const string BOLD = "\033[1m";
    public const string FAINT = "\033[2m";
    public const string ITALIC = "\033[3m";
    public const string UNDERLINE = "\033[4m";
    public const string BLINK_SLOW = "\033[5m";
    public const string BLINK_RAPID = "\033[6m";
    public const string REVERSE_VIDEO = "\033[7m";
    public const string CONCEALED = "\033[8m";
    public const string CROSSED_OUT = "\033[9m";
    // Front
    public const string BLACK = "\033[30m";
    public const string RED = "\033[31m";
    public const string GREEN = "\033[32m";
    public const string YELLOW = "\033[33m";
    public const string BLUE = "\033[34m";
    public const string MAGENTA = "\033[35m";
    public const string CYAN = "\033[36m";
    public const string WHITE = "\033[37m";
    // Back
    public const string BG_BLACK = "\033[40m";
    public const string BG_RED = "\033[41m";
    public const string BG_GREEN = "\033[42m";
    public const string BG_YELLOW = "\033[43m";
    public const string BG_BLUE = "\033[44m";
    public const string BG_MAGENTA = "\033[45m";
    public const string BG_CYAN = "\033[46m";
    public const string BG_WHITE = "\033[47m";
    // Front Highlight
    public const string HI_BLACK = "\033[90m";
    public const string HI_RED = "\033[91m";
    public const string HI_GREEN = "\033[92m";
    public const string HI_YELLOW = "\033[93m";
    public const string HI_BLUE = "\033[94m";
    public const string HI_MAGENTA = "\033[95m";
    public const string HI_CYAN = "\033[96m";
    public const string HI_WHITE = "\033[97m";
    // Back Highlight
    public const string BG_HI_BLACK = "\033[100m";
    public const string BG_HI_RED = "\033[101m";
    public const string BG_HI_GREEN = "\033[102m";
    public const string BG_HI_YELLOW = "\033[103m";
    public const string BG_HI_BLUE = "\033[104m";
    public const string BG_HI_MAGENTA = "\033[105m";
    public const string BG_HI_CYAN = "\033[106m";
    public const string BG_HI_WHITE = "\033[107m";

    protected array $attributes = [];

    /**
     * Static method to print colored text to stdout.
     *
     * @param string|null $color     The color code to use
     * @param string      $format    The format string
     * @param mixed       ...$values The values to format
     */
    public static function printf(?string $color, string $format, ...$values): void
    {
        file_put_contents('php://stdout', self::sprintf($color, $format, ...$values));
    }

    /**
     * Static method to format text with color.
     *
     * @param string|null $color     The color code to use
     * @param string      $format    The format string
     * @param mixed       ...$values The values to format
     *
     * @return string The formatted string with color
     */
    public static function sprintf(?string $color, string $format, ...$values): string
    {
        $string = sprintf($format, ...$values);
        if (empty($color)) {
            return $string;
        }

        return $color.$string.self::RESET;
    }

    /**
     * Static method to print colored text with multiple attributes.
     *
     * @param array  $colors    Array of color codes
     * @param string $format    The format string
     * @param mixed  ...$values The values to format
     */
    public static function printfMulti(array $colors, string $format, ...$values): void
    {
        file_put_contents('php://stdout', self::sprintfMulti($colors, $format, ...$values));
    }

    /**
     * Static method to format text with multiple color attributes.
     *
     * @param array  $colors    Array of color codes
     * @param string $format    The format string
     * @param mixed  ...$values The values to format
     *
     * @return string The formatted string with colors
     */
    public static function sprintfMulti(array $colors, string $format, ...$values): string
    {
        $string = sprintf($format, ...$values);

        return implode('', $colors).$string.self::RESET;
    }

    /**
     * Constructor for instance-based usage.
     *
     * @param string ...$attributes Color attributes
     */
    public function __construct(string ...$attributes)
    {
        $this->add(...$attributes);
    }

    /**
     * Add color attributes to the instance.
     *
     * @param string ...$attributes Color attributes
     */
    public function add(string ...$attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Instance method to print colored text.
     *
     * @param string $format    The format string
     * @param mixed  ...$values The values to format
     */
    public function doPrintf(string $format, ...$values): void
    {
        file_put_contents('php://stdout', $this->sprintf($format, ...$values));
    }

    /**
     * Instance method to format text with color.
     *
     * @param string $format    The format string
     * @param mixed  ...$values The values to format
     *
     * @return string The formatted string with color
     */
    public function doSprintf(string $format, ...$values): string
    {
        $string = sprintf($format, ...$values);

        return $this->render($string);
    }

    /**
     * Render the string with color attributes.
     *
     * @param string $string The string to render
     *
     * @return string The rendered string
     */
    protected function render(string $string): string
    {
        if (empty($this->attributes)) {
            return $string;
        }

        return implode('', $this->attributes).$string.self::RESET;
    }
}
