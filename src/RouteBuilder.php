<?php

/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.3.5
 */

namespace Zelasli\Routing;

use Closure;
use InvalidArgumentException;

/**
 * RouteBuilder used to build routes
 *
 * Provides features to use for building and appending them into the
 * route collection.
 */
class RouteBuilder
{
    /**
     * Should route url append slash
     *
     * @var bool
     */
    protected bool $urlAppendSlash = false;

    /**
     * Collection that store routes.
     *
     * @var RouteCollection
     */
    protected RouteCollection $collection;

    /**
     * The prefix of the current group scope
     *
     * @var string
     */
    protected $scopePrefix = "";

    /**
     * RouteBuilder contructor
     *
     */
    public function __construct()
    {
        $this->collection = new RouteCollection();
    }

    /**
     * Create a new route object.
     *
     * Convert parameters<$url, $destination, $options> given to the actual
     * routing route.
     *
     * @param string $url - The request URL that match to this route.
     * @param string $destination - The destination to invoke for the url. This
     * is the  controller namespace, controller action to invoke as method,
     * and the parameters to pass when calling the method.
     * @param array $options - The optional values
     *
     * @return Route
     */
    protected function createRoute($url, $destination, $options): Route
    {
        // Replace backward slash to forward slash, since parser uses forward
        // slash
        $destination = str_replace('\\', '/', $destination);
        $closure = $this->parseClosureInfo($destination);
        $parsed = Router::processRouteParams($url);
        $closure['url'] = $url;
        $attributes = [
            'url' => $parsed['url']
        ];

        if (!empty($options['name']) && count($placeholders = $parsed['placeholders']) > 0) {
            foreach ($placeholders as $k => $placeholder) {
                $attributes['params'][] = $placeholder['pattern'];
            }
        }

        if (!empty($options['name'])) {
            $attributes['name'] = $options['name'];

            unset($options['name']);
        }

        // prefix isset? Add to $attributes[], remove it from $closure[]
        if (isset($closure['prefix'])) {
            $attributes['prefix'] = str_replace(
                '/',
                "\\",
                $closure['prefix']
            );
            unset($closure['prefix']);
        }
        // params isset? Add to $attributes[], remove it from $closure[]
        if (isset($closure['params'])) {
            $attributes['params'] = $closure['params'];
            unset($closure['params']);
        }

        // Add route $attributes[options] used by developer
        $attributes['options'] = array_filter(
            $options,
            fn ($v, $k) => str_starts_with($k, '_'),
            ARRAY_FILTER_USE_BOTH
        );

        // Strip (_) in each option key!
        foreach ($attributes['options'] as $optionK => $optionV) {
            unset($attributes['options'][$optionK]);

            $attributes['options'][substr($optionK, 1)] = $optionV;
        }

        // Does $attributes['options'] has no elements? retire it!
        if (empty($attributes['options'])) {
            unset($attributes['options']);
        }
        $closure['attributes'] = $attributes;

        return new Route(...$closure);
    }

    /**
     * Get Router instance ready to use with route builder by this route
     * builder
     *
     * @return Router
     */
    public function getRouterInstance(): Router
    {
        $router = null;

        if (!$router) {
            $router = new Router($this->collection);
        }

        return $router;
    }

    /**
     * Group a set of routes under single prefix
     *
     * Group related routes within the same scope of URL
     *
     * Example:
     *
     * ```
     * $builder->group('/admin', function ($builder) {
     *  $builder->link('/dashboard', "\Admin\Dashboard::index");
     *  $builder->link('/users', "\Admin\Users::index");
     *  $builder->link('/users/view/(id:digit)', "\Admin\Users::view/{id}");
     * });
     * ```
     *
     * @param string $url
     * @param Closure $anonymFunc
     *
     * @return $this
     */
    public function group(string $url, Closure $anonymFunc): self
    {
        $oldPrefix = $this->scopePrefix;
        $this->scopePrefix .= $url;

        // treat prefix to always starts with slash
        if (substr($this->scopePrefix, 0, 1) != '/') {
            $this->scopePrefix = '/' . $this->scopePrefix;
        }

        $anonymFunc($this);

        $this->scopePrefix = $oldPrefix;

        return $this;
    }

    /**
     * Link a new Route to destination.
     *
     * Links HTTP request URLs to the destination part of your project
     *
     * Example:
     *
     * ```
     * $builder->link('/blogs/publish', "App\\Controllers\\Blogs::create");
     * ```
     * This route will matches **App\Controllers\Blogs** as a controller
     * namespace and the **action** as the method name to invoke.
     * ```
     * $builder->link('/blogs/(:digit)', "App\Controllers\Blogs::view/{id}");
     * ```
     *
     * This route will matches request URL __/blogs/147000__, Create instance
     * object for contrlloer __App\Controllers\Blogs__ and call view method
     * with parameter **id** with value **147000**
     *
     *
     * ```
     * $builder->link('/blogs/publish', ["App\Controllers\Blogs", "create"]);
     * ```
     * This route linking is the same as the first link
     *
     * @param string $url
     * @param array|string $destination
     *
     * @return Route
     * @throws InvalidArgumentException
     */
    public function link(string $url, $destination, array $options = []): Route
    {
        $groupPrefix = $this->scopePrefix;
        $url = rtrim($groupPrefix, '/') . $url;

        if (is_array($destination)) {
            if (count($destination) > 1) {
                $old_destination = array_values($destination);

                $destination = $old_destination[0];
                $destination .= "::" . trim($old_destination[1], '/');

                array_shift($old_destination);
                array_shift($old_destination);

                if (!empty($old_destination)) {
                    $destination .= "/" .
                    ltrim(
                        implode(
                            '/',
                            array_map(
                                fn ($param) => trim($param, '/'),
                                $old_destination
                            )
                        ),
                        '/'
                    );
                }
            } else {
                throw new InvalidArgumentException(
                    "Route with array destination parameter must have controller and action passed."
                );
            }
        }

        if (!$this->urlAppendSlash) {
            $url = $url !== '/' ? rtrim($url, '/') : $url;
        } else {
            $url = rtrim($url, '/') . '/';
        }

        $route = $this->createRoute($url, $destination, $options);

        $this->collection->add($route);

        return $route;
    }

    /**
     * Parse the controller, action and parameters from route
     * destination string.
     *
     * @param string $destination
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function parseClosureInfo(string $destination): array
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

        if (!preg_match($pattern, rtrim($destination, '/'), $matches)) {
            throw new InvalidArgumentException(
                sprintf("Could not parse route destination: %s", $destination)
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
            $info['params'] = $this->stripParams(
               explode('/', $matches['params'])
            );
        }

        return $info;
    }

    /**
     * Switch whether the route url should append slash
     *
     * @param bool $test
     *
     * @return $this
     */
    public function shouldUrlAppendSlash(bool $test): self
    {
        $this->urlAppendSlash = $test;

        return $this;
    }

    /**
     * Remove parameters placeholder tag and empty values
     *
     * @param array $params
     *
     * @return array
     */
    public function stripParams($params): array
    {
        $new = [];
        $i = 1; // for positional param name
        foreach ($params as $param) {
            if (!empty($param)) {
                if ($param[0] == '{' && $param[-1] == '}') {
                    $part = explode('=', str_replace(['{', '}'], "", $param));

                    if (count($part) > 1) {
                        $param = [
                            'name' => $part[0],
                            'val' => $part[1],
                            'isVarg' => true
                        ];
                    } else {
                        $param = [
                            'name' => $part[0],
                            'isVarg' => true
                        ];
                    }
                } else {
                    $param = [
                        'name' => $i++,
                        'val' => $param,
                        'isVarg' => false
                    ];
                }
                $new[] = $param;
            }
        }

        return $new;
    }
}
