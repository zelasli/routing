<?php

/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.3.5
 */

namespace Zelasli\Routing;

class Router
{
    /**
     * Routes Colledction
     *
     * @var RouteCollection<string|int, Route>
     */
    protected RouteCollection $collection;

    /**
     * Named placeholders used in route url as regex.
     *
     * @var <string, array<bool|string>>
     */
    protected static $placeholders = [
        'any'    => [
            Placeholders::ANY,
            true
        ],
        'alnum'  => [
            Placeholders::ALNUM,
            true
        ],
        'alpha'  => [
            Placeholders::ALPHA,
            true
        ],
        'bit'    => [
            Placeholders::BIT,
            true
        ],
        'day'    => [
            Placeholders::DAY,
            false
        ],
        'digit'  => [
            Placeholders::DIGIT,
            true
        ],
        'lower'  => [
            Placeholders::LOWER,
            true
        ],
        'month'  => [
            Placeholders::MONTH,
            false
        ],
        'odigit' => [
            Placeholders::ODIGIT,
            true
        ],
        'upper'  => [
            Placeholders::UPPER,
            true
        ],
        'uuid'   => [
            Placeholders::UUID,
            false
        ],
        'xdigit' => [
            Placeholders::XDIGIT,
            true
        ],
        'year'   => [
            Placeholders::YEAR,
            false
        ],
    ];

    /**
     * Router constructor
     *
     * @param RouteCollection $collection
     */
    public function __construct(RouteCollection $collection = null)
    {
        if ($collection != null) {
            $this->collection = $collection;
        }
    }

    /**
     * Check if value matches the given placeholder type
     *
     * @param string $value
     * @param string $type
     *
     * @return bool
     */
    protected function checkValueMatch($value, $type): bool
    {
        foreach (self::$placeholders as $name => $placeholder) {
            if ($name == $type) {
                $pattern = '#^(' . $placeholder[0];
                $pattern .= ($placeholder[1]) ?
                '*' : '';
                $pattern .= ')$#';

                return preg_match($pattern, $value, $m) !== 0 | false;
            }
        }

        return false;
    }

    /**
     * Check whether value matches quantifier pattern
     *
     * @param string $value
     * @param array $quantifier
     *
     * @return bool
     */
    protected function checkValueQuantifier($value, array $quantifier): bool
    {
        $valen = strlen((string) $value);

        if (
        (
            (count($quantifier) > 1) &&
            ($valen >= $quantifier[0] && (empty($quantifier[1]) || $valen <= $quantifier[1]))
        ) ||
        (count($quantifier) == 1 && $quantifier[0] == $valen)) {
            return true;
        }

        return false;
    }

    /**
     * Find the Route object in the collection that matches the given name.
     *
     * @param string $url
     *
     * @return null|Route
     */
    public function findRouteByName(string $name): null|Route
    {
        foreach ($this->collection as $RName => $RObject) {
            if ($RName === $name) {
                return clone $RObject;
            }
        }

        return null;
    }

    /**
     * Find the Route object in the collection that matches url string.
     *
     * @param string $url
     *
     * @return null|Route
     */
    public function findRouteByUrl(string $url): null|Route
    {
        foreach ($this->collection as $RObject) {
            $RObject = clone $RObject;

            if ($this->isUrlForRoute($url, $RObject)) {
                return $RObject;
            }
        }

        return null;
    }

    /**
     * Check to see if the given URL matches the URL for the given route.
     *
     * @param string $url
     * @param Route $route
     *
     * @return bool
     */
    protected function isUrlForRoute(string $url, Route $route): bool
    {
        $routeUrl = $route->getAttrUrl();

        // Simple match. Is url string same as route url?
        if ($routeUrl == $url) {
            return true;
        }

        // Are we Here? Make preg_match_all
        if (preg_match_all("#^" . $routeUrl . "$#", $url, $matches, PREG_SET_ORDER)) {
            if ($route->hasVargsParam()) {
                $route->setParamsValue($matches);
            }

            return true;
        }

        return false;
    }

    /**
     * Replace placeholders to actual regex patterns.
     *
     * @param string $url
     *
     * @return array
     */
    public static function processRouteParams(string $url): array
    {
        $placeholders = implode('|', array_keys(self::$placeholders));

        $pattern = '#
        (?<placeholder>[(]{1}
            (?:(?<name>[a-z0-9_]*))
            \:
            (?<type>'.$placeholders.')
            (?:(?<quantifier>[\:]*(\*|\+|\?|[,\d])*))
        [)]{1})#ix';

        preg_match_all($pattern, $url, $matches, PREG_SET_ORDER);
        $placeholders = [];

        $i = 1; // For positional placeholders (indexed params)
        foreach ($matches as $match) {
            $placeholders[] = [
                'pattern' => $match['placeholder'],
                'name' => !empty($match['name']) ? $match['name'] : $i++,
                'type' => $match['type'],
                'quantifier' => $match['quantifier'],
            ];

            $replace = '('; // start param subpattern
            if (!empty($match['name'])) {
                $replace .= "?'{$match['name']}'";
            }
            $replace .= self::$placeholders[$match['type']][0];

            $match['quantifier'] = (!empty($match['quantifier'])) ?
            substr($match['quantifier'], 1) : null;

            // Does type can have quantifier and no one specified? and on or more match!
            if (empty($match['quantifier']) &&
            self::$placeholders[$match['type']][1]) {
                $match['quantifier'] = '+';
            }
            // Does the quantifier match one of ?,*,+ quantifiers
            if (
                self::$placeholders[$match['type']][1] &&
                in_array($match['quantifier'], ['?', '*', '+'])) {
                $replace .= $match['quantifier'];
            // Does it uses min and/or max quantifier
            } elseif (!is_null($match['quantifier'])) {
                $quantifier = explode(',', $match['quantifier']);
                $replace .= '{'; // start quantifier

                if (count($quantifier) > 1 && empty($quantifier[0])) {
                    $replace .= 1;
                } else {
                    $replace .= $quantifier[0];
                }

                // Does it has max quantifier?
                if (count($quantifier) > 1) {
                    $replace .= ',' . $quantifier[1];
                }

                $replace .= '}'; // start quantifier
            }

            $replace .= ')'; // end param subpattern
            $url = str_ireplace($match['placeholder'], $replace, $url);
        }

        return [
            'url' => $url,
            'placeholders' => $placeholders
        ];
    }

    /**
     * Register new placeholder pattern
     *
     * @param string $name
     * @param string $pattern
     *
     * @return void
     */
    public static function registerPlaceholder(
        $name,
        $pattern,
        $hasQuantifier = true
    ): void {
        if (!in_array($name, array_keys(self::$placeholders))) {
            self::$placeholders[$name] = [$pattern, $hasQuantifier];
        }
    }

    /**
     * Reverse route url
     *
     * @param string $name
     * @param array|null $params
     *
     * @return null|string
     */
    public function reverseUrl($name, array|null $params = null): null|string
    {
        $route = $this->findRouteByName($name);
        $replaced = 0;
        $ret = null;

        if (!is_null($route)) {
            $routeUrlWithPlaceholder = $url = $route->getUrl();
            $urlParams = $this->processRouteParams($routeUrlWithPlaceholder);

            if ($route->getVargsCount() == 0) {
                $url = $route->getAttrUrl();
            } elseif (
            (is_array($params) && (count($params) != $route->getVargsCount())) ||
            !is_array($params)) {
                return null;
            }

            foreach ($urlParams['placeholders'] as $placeholder) {
                $name = $placeholder['name'];
                $type = $placeholder['type'];
                $quantifier = $placeholder['quantifier'];

                foreach ($params as $paramK => $paramV) {
                    if ($paramK == $name && $this->checkValueMatch($paramV, $type)) {
                        if (
                            !self::$placeholders[$type][1] &&
                            strpos($url, $placeholder['pattern']) !== false
                        ) {
                            $url = str_ireplace($placeholder['pattern'], $paramV, $url);
                            $replaced++;

                            continue;
                        } elseif (empty($quantifier) || substr($quantifier, 1) == '+') {
                            $quantifier = [1, ''];
                        } elseif (substr($quantifier, 1) == '?') {
                            $quantifier = [0, 1];
                        } elseif (substr($quantifier, 1) == '*') {
                            $quantifier = [0, ''];
                        } elseif (strpos($quantifier, ':') === 0) {
                            $quantifier = substr($quantifier, 1);

                            if (is_numeric($quantifier)) {
                                $quantifier = [$quantifier];
                            } else {
                                $quantifier = explode(',', $quantifier);
                                $quantifier[0] = empty($quantifier[0]) ?
                                1 : $quantifier[0];
                            }
                        }

                        if ($this->checkValueQuantifier($paramV, $quantifier) &&
                        strpos($url, $placeholder['pattern']) !== false) {
                            $url = str_ireplace($placeholder['pattern'], $paramV, $url);

                            $replaced++;
                        }
                    }
                }
            }

            if ($replaced == $route->getVargsCount()) {
                $ret = $url;
            }
        }

        return $ret;
    }
}
