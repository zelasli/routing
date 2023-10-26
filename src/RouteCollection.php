<?php
/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.1.0
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
    protected array $collection = [];

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
                $this->collection[$name] = $route;
            } else {
                $this->collection[] = $route;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * @inheritDoc
     */
    public function current(): mixed
    {
        return current($this->collection);
    }

    /**
     * @inheritDoc
     */
    public function key(): mixed
    {
        return key($this->collection);
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        next($this->collection);
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        reset($this->collection);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return key($this->collection) !== null;
    }
}
