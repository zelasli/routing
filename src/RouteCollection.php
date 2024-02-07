<?php

/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.3.5
 */

namespace Zelasli\Routing;

use Countable;
use Iterator;

/**
 * RouteCollection contains a collection of Routes
 *
 * Used for adding, storing and removing routes.
 */
class RouteCollection implements Countable, Iterator
{
    /**
     * Container for the Routes container.
     *
     * @var < int|string,Route>
     */
    protected array $routes = [];

    /**
     * Add route to the collection
     *
     * @param Route $route
     *
     * @return void
     */
    public function add(Route $route): void
    {
        if ($route instanceof Route) {
            if (!empty($name = $route->getName())) {
                $this->routes[$name] = $route;
            } else {
                $this->routes[] = $route;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * @inheritDoc
     */
    public function current(): mixed
    {
        return current($this->routes);
    }

    /**
     * @inheritDoc
     */
    public function key(): mixed
    {
        return key($this->routes);
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        next($this->routes);
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        reset($this->routes);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return key($this->routes) !== null;
    }
}
