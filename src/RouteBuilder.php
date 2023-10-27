<?php
/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.1.0
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
    protected $scopePrefix = "/";

    /**
     * RouteBuilder contructor()
     * 
     * @param RouteCollection $routeCollection - The collection to store the 
     * routes created by the route builder.
     * 
     * @return $this
     */
    public function __construct(RouteCollection $routeCollection)
    {
        $this->collection = $routeCollection;
    }

    /**
     * Create a new route object.
     * 
     * Convert parameters<$url, $callback, $options> given to the actual 
     * routing route.
     * 
     * @param string $url - The request URL that match to this route.
     * @param string $callback - The destination to invoke for the url. This 
     * is the  controller namespace, controller action to invoke as method, 
     * and the parameters to pass when calling the method. 
     * @param array $options - The options on how to handle the route when the
     * request URL matches the route URL.
     * 
     * @return Route
     */
    protected function createRoute($url, $callback, $options): Route
    {
        // Replace backward slash to forward slash, since parser uses forward 
        // slash
        $callback = str_replace('\\', '/', $callback);
        $closure = Router::parseClosureInfo($callback);
        $parsed = Router::processRouteParams($url);
        $closure['url'] = $parsed['url'];
        $attributes = [
            'url' => $url
        ];
        
        if (!empty($options['name']) && count($placeholders = $parsed['placeholders']) > 0) {
            $i = 1;
            $newPlaceholders = [];
            
            foreach ($placeholders as $k => $placeholder) {
                if (empty($placeholder)) {
                    $placeholders[$k]['name'] = $i++;
                }
                
                $newPlaceholders[] = $placeholder;
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
        
        // Add $attributes[] to $closure[]
        $attributes['options'] = array_filter(
            $options, 
            fn ($k, $v) => !in_array($k, ['name']), 
            ARRAY_FILTER_USE_BOTH
        );
        $closure['attributes'] = $attributes;
        
        return new Route(...$closure);
    }

    /**
     * Get the routes collection
     * 
     * @return RouteCollection
     */
    public function getCollection(): RouteCollection
    {
        return $this->collection;
    }

    /**
     * Link a new Route to destination.
     * 
     * Links HTTP request URLs to the destination the part of 
     * your project
     * 
     * Example:
     * 
     * ```
     * $builder->link('post/new', "Blogs\Controller\Post::create");
     * ```
     * This route will matches **Blogs\Controller\Post** as a controller 
     * namespace and the **action** as the method name to invoke.
     * 
     * 
     * ```
     * $builder->link('post/(:digit)', "Blogs\Controller\Post::view/{id}");
     * ```
     * 
     * This route will matches request URL **\/post/147000**, Create instance 
     * object for contrlloer **\Blogs\Controller\Post** and call view method 
     * with parameter **id** with value **147000**
     * 
     * 
     * ```
     * $builder->link('home', ["Controller\\Namespace", "action"]);
     * ```
     * 
     * @param string $url
     * @param array|string $callback
     */
    public function link(string $url, $callback = [], array $options = []): Route
    {
        $groupPrefix = $this->scopePrefix;
        $url = 
            rtrim($groupPrefix, '/') . 
            '/' . 
            ltrim($url, '/');

        if (is_array($callback)) {
            if (count($callback) > 1) {
                $old_callback = array_values($callback);

                $callback = $old_callback[0];
                $callback .= "::" . trim($old_callback[1], '/');

                array_shift($old_callback);
                array_shift($old_callback);
                
                if (!empty($old_callback)) {
                    $callback .= "/" .
                    ltrim(
                        implode(
                            '/', 
                            array_map(
                                fn ($param) => trim($param, '/'), 
                                $old_callback
                            )
                        ), 
                        '/'
                    );
                }
            } else {
                throw new InvalidArgumentException(
                    "Route with array callback parameter must have controller and action passed."
                );
            }
        }

        $route = $this->createRoute($url, $callback, $options);

        $this->collection->add($route);
        
        return $route;
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
     *  $builder->link('dashboard', "\Admin\Dashboard::index");
     *  $builder->link('users', "\Admin\Users::index");
     *  $builder->link('users/view/(id:digit)', "\Admin\Users::view/{id}");
     * });
     * ```
     * 
     * @param string $url
     * @param Closure $callback
     * 
     * @return void
     */
    public function group(string $url, Closure $callback): void
    {
        $oldPrefix = $this->scopePrefix;
        $this->scopePrefix .= ltrim($url, '/');

        $callback($this);

        $this->scopePrefix = $oldPrefix;
    }
}
