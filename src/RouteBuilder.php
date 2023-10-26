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
        // TODO: process the given arguments and pass the parameters for route
        return new Route(...[]);
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
     * @param string $url
     * @param array|string $callback
     */
    public function link(string $url, $callback = [], array $options = []): Route
    {
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
