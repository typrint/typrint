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

namespace TP;

class Constants
{
    public static function init(): void
    {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        /**
         * Constants for expressing human-readable data sizes in their respective number of bytes.
         *
         * @since 1.0.0
         */
        define('KB_IN_BYTES', 1024);
        define('MB_IN_BYTES', 1024 * KB_IN_BYTES);
        define('GB_IN_BYTES', 1024 * MB_IN_BYTES);
        define('TB_IN_BYTES', 1024 * GB_IN_BYTES);
        define('PB_IN_BYTES', 1024 * TB_IN_BYTES);
        define('EB_IN_BYTES', 1024 * PB_IN_BYTES);
        define('ZB_IN_BYTES', 1024 * EB_IN_BYTES);
        define('YB_IN_BYTES', 1024 * ZB_IN_BYTES);

        /**
         * Constants for expressing human-readable intervals
         * in their respective number of seconds.
         *
         * Please note that these values are approximate and are provided for convenience.
         * For example, MONTH_IN_SECONDS wrongly assumes every month has 30 days and
         * YEAR_IN_SECONDS does not take leap years into account.
         *
         * If you need more accuracy please consider using the DateTime class (https://www.php.net/manual/en/class.datetime.php).
         *
         * @since 1.0.0
         */
        define('MINUTE_IN_SECONDS', 60);
        define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
        define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
        define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
        define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
        define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);

        // Start of run timestamp.
        if (!defined('TP_START_TIMESTAMP')) {
            define('TP_START_TIMESTAMP', microtime(true));
        }

        // Content directory.
        if (!defined('TP_CONTENT_DIR')) {
            define('TP_CONTENT_DIR', ABSPATH.'/tp-content');
        }

        // Debug mode.
        if (!defined('TP_DEBUG')) {
            define('TP_DEBUG', false);
        }
    }
}
