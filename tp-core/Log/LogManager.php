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

namespace TP\Log;

/**
 * Log manager for TyPrint.
 *
 * Simple PSR-3 inspired logger for TyPrint.
 */
class LogManager
{
    /**
     * The singleton instance of the log manager.
     */
    protected static ?self $instance = null;

    /**
     * Log levels.
     *
     * @var array<string, int>
     */
    protected const array LEVELS = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    /**
     * The minimum log level to record.
     */
    protected int $minimumLevel = 0;

    /**
     * The log file path.
     */
    protected string $logFile = ABSPATH.'/tp-content/debug.log';

    /**
     * Get the singleton instance of the log manager.
     */
    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Log message with emergency level.
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Log message with alert level.
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Log message with critical level.
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log message with error level.
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log message with warning level.
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log message with notice level.
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Log message with info level.
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log message with debug level.
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log with arbitrary level.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);

        if (!isset(self::LEVELS[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        if (self::LEVELS[$level] > $this->minimumLevel) {
            return;
        }

        $this->writeLog($level, $this->interpolate($message, $context));
    }

    /**
     * Interpolate context values into the message placeholders.
     */
    protected function interpolate(string $message, array $context = []): string
    {
        if (empty($context)) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $val) {
            if (null === $val || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{'.$key.'}'] = $val;
            } elseif (is_object($val)) {
                $replace['{'.$key.'}'] = '[object '.$val::class.']';
            } elseif (is_array($val)) {
                $replace['{'.$key.'}'] = json_encode($val);
            } else {
                $replace['{'.$key.'}'] = '['.gettype($val).']';
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Write a log entry to the log file.
     */
    protected function writeLog(string $level, string $message): void
    {
        $entry = sprintf(
            '[%s] %s: %s'.PHP_EOL,
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        file_put_contents($this->logFile, $entry, FILE_APPEND);
    }

    /**
     * Set the minimum log level.
     */
    public function setMinimumLevel(string|int $level): void
    {
        if (is_string($level)) {
            $level = strtolower($level);
            if (!isset(self::LEVELS[$level])) {
                throw new \InvalidArgumentException("Invalid log level: {$level}");
            }
            $this->minimumLevel = self::LEVELS[$level];
        } else {
            if ($level < 0 || $level > 7) {
                throw new \InvalidArgumentException("Invalid log level: {$level}");
            }
            $this->minimumLevel = $level;
        }
    }

    /**
     * Set the log file path.
     */
    public function setLogFile(string $path): void
    {
        $this->logFile = $path;
    }
}
