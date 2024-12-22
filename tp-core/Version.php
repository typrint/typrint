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

class Version
{
    /**
     * The TyPrint core version string.
     *
     * Holds the current version number for TyPrint core.
     */
    public const string CORE = '1.0.0';

    /**
     * The TyPrint database version string.
     *
     * Holds the TyPrint database revision, increments when changes are made to the TyPrint database schema.
     */
    public const string DB = '20241222';

    /**
     * The TyPrint PHP version string.
     *
     * Holds the required PHP version for TyPrint.
     */
    public const string PHP = '8.3.0';
}
