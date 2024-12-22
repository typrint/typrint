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

use TP\TP;

// Absolute path to the TyPrint directory.
const ABSPATH = __DIR__;

// Loads the TyPrint autoloader.
require_once __DIR__.'/tp-vendor/autoload.php';

// Load the TyPrint configuration.
require_once __DIR__.'/tp-config.php';

// Runs the TyPrint application.
(new TP())->run();
