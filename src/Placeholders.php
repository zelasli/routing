<?php

/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.3.5
 */

namespace Zelasli\Routing;


/**
 * Placeholder contains regular expressions.
 *
 * Provides constants Regular Expressions to parse parameters from
 * Request URLs.
 */
class Placeholders
{
    /**
     * Placeholder to match any character except new line
     *
     * This pattern extract any grouped character from request URL
     *
     * @var string
     */
    const ANY = '[^/]';

    /**
     * Placeholder to match alphanumeric string
     *
     * This pattern extract alphanumeric parameter from the request URL
     *
     * @var string
     */
    const ALNUM = '[a-zA-Z\d]';

    /**
     * Placeholder to match alphabetic string
     *
     * This pattern extract alphabetic parameter from the request URL
     *
     * @var string
     */
    const ALPHA = '[a-zA-Z]';

    /**
     * Placeholder to match binary number string
     *
     * This pattern extract binary number parameter from the request URL
     *
     * @var string
     */
    const BIT = '[01]';

    /**
     * Placeholder to match decimal number string
     *
     * This pattern extract decimal number parameter from the request URL
     *
     * @var string
     */
    const DIGIT = '[\d]';

    /**
     * Placeholder to match lowercase alphabetic string
     *
     * This pattern extract lowercase alphabetic parameter from the request URL
     *
     * @var string
     */
    const LOWER = '[a-z]';

    /**
     * Placeholder to match octal number string
     *
     * This pattern extract octal number parameter from the request URL
     *
     * @var string
     */
    const ODIGIT = '[0-7]';

    /**
     * Placeholder to match uppercase alphabetic string
     *
     * This pattern extract uppercase alphabetic parameter from the request URL
     *
     * @var string
     */
    const UPPER = '[A-Z]';

    /**
     * Placeholder to match hexadecimal number string
     *
     * This pattern extract hexadecimal number parameter from the request URL
     *
     * @var string
     */
    const XDIGIT = '[\dABCDEF]|[\dabcdef]';

    /**
     * Placeholder to match UUID string
     *
     * This pattern extract UUID parameter from the request URL
     *
     * @var string
     */
    const UUID = '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}';

    /**
     * Placeholder to match year string
     *
     * This pattern extract year parameter from the request URL
     *
     * @var string
     */
    const YEAR = '[12][0-9]{3}';

    /**
     * Placeholder to match month string
     *
     * This pattern extract month parameter from the request URL
     *
     * @var string
     */
    const MONTH = '0[1-9]|1[012]';

    /**
     * Placeholder to match day string
     *
     * This pattern extract day parameter from the request URL
     *
     * @var string
     */
    const DAY = '0[1-9]|[12][0-9]|3[01]';
}
