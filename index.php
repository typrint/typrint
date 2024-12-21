<?php
/**
 * Front to the TyPrint application.
 *
 * @package TyPrint
 */

declare(strict_types=1);

use TP\TP;

// Absolute path to the TyPrint directory.
const ABSPATH = __DIR__;

// Loads the TyPrint autoloader.
require_once __DIR__ . '/tp-vendor/autoload.php';

// Load the TyPrint configuration.
require_once __DIR__ . '/tp-config.php';

// Runs the TyPrint application.
(new TP())->run();
