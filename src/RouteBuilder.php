<?php
/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.2.8
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
     * Convert parameters<$url, $destination, $options> given to the actual 
     * routing route.
     * 
     * @param string $url - The request URL that match to this route.
     * @param string $destination - The destination to invoke for the url. This 
     * is the  controller namespace, controller action to invoke as method, 
     * and the parameters to pass when calling the method. 
     * @param array $options - The options on how to handle the route when the
     * request URL matches the route URL.
     * 
     * @return Route
     */
    protected function createRoute($url, $destination, $options): Route
    {
        // Replace backward slash to forward slash, since parser uses forward 
        // slash
        $destination = str_replace('\\', '/', $destination);
        $closure = Router::parseClosureInfo($destination);
        $parsed = Router::processRouteParams($url);
        $closure['url'] = $parsed['url'];
        $attributes = [
            'url' => $url
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
        
        // Add $attributes[] to $closure[]
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

        // Does $attributes['options'] has no elements? remove it!
        if (empty($attributes['options'])) {
            unset($attributes['options']);
        }
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
     * Get Router instance ready to use with route builder by this route 
     * builder
     * 
     * @return Router
     */
    public function getRouterInstance(): Router
    {
        return new Router($this->collection);
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
     * @param array|string $destination
     * 
     * @return Route
     * @throws InvalidArgumentException
     */
    public function link(string $url, $destination, array $options = []): Route
    {
        $groupPrefix = $this->scopePrefix;
        $url = 
            rtrim($groupPrefix, '/') . 
            '/' . 
            ltrim($url, '/');

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

        $route = $this->createRoute($url, $destination, $options);

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
     *  $builder->link('/dashboard', "\Admin\Dashboard::index");
     *  $builder->link('/users', "\Admin\Users::index");
     *  $builder->link('/users/view/(id:digit)', "\Admin\Users::view/{id}");
     * });
     * ```
     * 
     * @param string $url
     * @param Closure $anonymFunc
     * 
     * @return void
     */
    public function group(string $url, Closure $anonymFunc): void
    {
        $oldPrefix = $this->scopePrefix;
        $prefix = ($oldPrefix == '/')
            ? ""
            : rtrim($this->scopePrefix, '/');

        $this->scopePrefix = $prefix . '/' . ltrim($url, '/');

        $anonymFunc($this);

        $this->scopePrefix = $oldPrefix;
    }
}
