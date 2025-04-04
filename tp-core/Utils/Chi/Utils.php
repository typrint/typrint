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

namespace TP\Utils\Chi;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Utils
{
    /**
     * Helper function to create a PSR-7 response.
     *
     * @param int                         $status  HTTP status code
     * @param array                       $headers HTTP headers
     * @param string|StreamInterface|null $body    Response body
     * @param string                      $version HTTP version
     * @param string|null                 $reason  Reason phrase
     */
    public static function response(int $status = 200, array $headers = [], string|StreamInterface|null $body = null, string $version = '1.1', ?string $reason = null): ResponseInterface
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = self::determineContentType($body);
        }

        if (!isset($headers['Content-Length'])) {
            if ($body instanceof StreamInterface) {
                $headers['Content-Length'] = (string) (int) $body->getSize();
            } elseif (is_string($body)) {
                $headers['Content-Length'] = (string) strlen($body);
            } elseif (null === $body) {
                $headers['Content-Length'] = '0';
            }
        }

        if (!isset($headers['Date'])) {
            $headers['Date'] = gmdate('D, d M Y H:i:s \G\M\T');
        }

        if (!isset($headers['Cache-Control'])) {
            $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
        }

        return new Response($status, $headers, $body, $version, $reason);
    }

    /**
     * Determine the Content-Type based on the body content.
     *
     * @param mixed $body The response body
     *
     * @return string The determined Content-Type
     */
    private static function determineContentType(mixed $body): string
    {
        $contentType = 'application/octet-stream';

        if (null === $body) {
            return 'text/plain';
        }

        if ($body instanceof StreamInterface) {
            return $contentType;
        }

        if (is_string($body)) {
            if (self::isJson($body)) {
                return 'application/json';
            }
            if (self::isHtml($body)) {
                return 'text/html';
            }

            return 'text/plain';
        }

        // If it's an array or object, assume it's JSON
        if (is_array($body) || is_object($body)) {
            return 'application/json';
        }

        return $contentType;
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param string $string String to check
     *
     * @return bool Whether the string is JSON
     */
    private static function isJson(string $string): bool
    {
        return json_validate($string);
    }

    /**
     * Check if a string is HTML.
     *
     * @param string $string String to check
     *
     * @return bool Whether the string is HTML
     */
    private static function isHtml(string $string): bool
    {
        return $string !== strip_tags($string);
    }
}
