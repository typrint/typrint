<?php
/**
 * TyPrint Version
 *
 * Contains version information for the current TyPrint release.
 *
 * @package TyPrint
 * @since 1.0.0
 */

declare(strict_types=1);

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
