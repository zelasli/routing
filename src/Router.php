<?php
/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.1.0
 */

namespace Zelasli\Routing;

use InvalidArgumentException;

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
     * @var <string, array>
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
     * Find the Route object in the collection that matches the given name.
     * 
     * @param string $url
     * 
     * @return null|Route
     */
    public function findRouteByName(string $name): null|Route
    {
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
        return false;
    }
    
    /**
     * Parse the controller, action and parameters from route 
     * destination string.
     * 
     * @param string $callback
     * 
     * @return array
     * @throws InvalidArgumentException
     */
    public static function parseClosureInfo(string $callback): array
    {
        $pattern = '#^
        (?:(?<prefix>[a-z0-9]+(?:/[a-z0-9]+)*)/)?
        (?<controller>[a-z0-9]+)
        ::
        (?<action>[a-z0-9_]+)
        (?<params>(?:/(?:[a-z][a-z0-9-_]*=)?
            (?:
                ({{1}[a-z0-9-_=]+}{1})|
                ([a-z0-9-_=]+)|
                (["\'][^\'"]+[\'"]))
        )+/?)?
        $#ix';
        
        if (!preg_match($pattern, rtrim($callback, '/'), $matches)) {
            throw new InvalidArgumentException(
                sprintf("Could not parse route destination: %s", $callback)
            );
        }
        
        $info = [
            'controller' => $matches['controller'],
            'action' => $matches['action']
        ];

        if (!empty($matches['prefix'])) {
            $info['prefix'] = $matches['prefix'];
        }
        if (!empty($matches['params'])) {
            $info['params'] = self::stripParams(
               explode('/', $matches['params'])
            );
        }

        return $info;
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
        
        foreach ($matches as $match) {
            $placeholders[] = [
                'placeholder' => $match['placeholder'],
                'name' => $match['name'],
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
            
            if (is_null($match['quantifier']) && 
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
    public static function registerPlaceholder($name, $pattern, $hasQuantifier = true)
    {
        if (!in_array($name, array_keys(self::$placeholders))) {
            self::$placeholders[$name] = [$pattern, $hasQuantifier];
        }
    }
    
    /**
     * Set routes collection
     * 
     * @param RouteCollection $collection
     * 
     * @return void
     */
    public function setCollection(RouteCollection $collection): void
    {
        $this->collection = $collection;
    }

    /**
     * Remove parameters placeholder tag and empty values
     * 
     * @param array $params
     * 
     * @return array
     */
    public static function stripParams($params): array
    {
        $new = [];
        foreach ($params as $param) {
            if (!empty($param)) {
                $new[] = str_replace(["{", "}"], "", $param);
            }
        }

        return $new;
    }
}
