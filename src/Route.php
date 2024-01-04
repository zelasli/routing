<?php
/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.2.8
 */

namespace Zelasli\Routing;

class Route
{
    /**
     * Route request url
     * 
     * @var string
     */
    protected string $url;

    /**
     * Route controller namespace
     * 
     * @var string
     */
    protected string $controller;
    
    /**
     * Route controller method
     * 
     * @var string
     */
    protected string $action;

    /**
     * Additional routes attributes
     * 
     * @var array
     */
    protected array $attributes;

    /**
     * Route constructor
     * 
     * @param string $url
     * @param string $controller
     * @param string $action
     * @param array $attributes
     */
    public function __construct($url, $controller, $action, $attributes)
    {
        $this->url = $url;
        $this->controller = $controller;
        $this->action = $action;
        $this->attributes = $attributes;
    }

    /**
     * Get the name of the controller method for this route.
     * 
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the extracted parameters from URL.
     * 
     * @return array
     */
    public function getAttrParams(): array
    {
        return !empty($this->attributes['params']) ? 
        $this->attributes['params']
        : [];
    }

    /**
     * Get unformatted url.
     * 
     * @return string
     */
    public function getAttrUrl(): string
    {
        return $this->attributes['url'] ?? '';
    }

    /**
     * Get the controller class namespace.
     * 
     * The class name as it was declared including namespace.
     * 
     * @return string
     */
    public function getClass(): string
    {
        return (!empty($this->attributes['prefix'])) ?
            str_replace(
                '/', 
                '\\', 
                rtrim($this->attributes['prefix'], "/\\") .
                "\\" .
                $this->controller
            ) : 
            $this->controller;
    }

    /**
     * Get the controller class name.
     * 
     * The class name as it was declared without namespace.
     * 
     * @return string
     */
    public function getClassName(): string
    {
        return $this->controller;
    }

    /**
     * Get the name of route if specified empty otherwise.
     * 
     * @return string
     */
    public function getName(): string
    {
        return ( 
            !empty($this->attributes['name'])
        ) ? $this->attributes['name']: '';
    }

    /**
     * Get value set with the route
     * 
     * @param string $name
     * 
     * @return mixed|null
     */
    public function getOption($name): array|bool|float|int|string|null
    {
        return isset($this->attributes['options'][$name])
            ? $this->attributes['options'][$name]
            : null;
    }

    /**
     * Get multiple options value at a time
     * 
     * @param array $names
     */
    public function getOptions(array $names): array
    {
        $list = [];

        foreach ($names as $name) {
            $list[$name] = $this->getOption($name);
        }

        return $list;
    }

    /**
     * Get parsed parameters from request url that matched the route to be 
     * passed to the controller's method as it's parameters.
     * 
     * @return array
     */
    public function getParams(): string
    {
        return $this->attributes['paramsValue'] ?? [];
    }

    /**
     * Get the route controller namespace prefix
     * 
     * @return null|string
     */
    public function getPrefix(): string
    {
        return !empty($this->attributes['prefix']) ?
            $this->attributes['prefix'] : 
            '';
    }

    /**
     * Get route URL with placeholder
     * 
     * @param array|null $params
     * 
     * @return mixed
     */
    public function getUrl(): mixed
    {
        return $this->url;
    }

    /**
     * Check whether this route has non null value of an option.
     * 
     * @param string $name
     */
    public function has($name): bool
    {
        return $this->getOption($name) != null;
    }

    /**
     * Check if this route's controller action has parameters to pass
     * 
     * @return bool
     */
    public function hasParams(): bool
    {
        return isset($this->attributes['params']);
    }

    /**
     * Set action parameters
     * 
     * @param array $params
     * 
     * @return $this
     */
    public function setParamsValue(array $params): self
    {
        $attrsParams = $this->attributes['params'] ?? [];
        
        foreach ($attrsParams as $paramName) {
            foreach ($params as $paramArr) {
                if (((int) $paramName) === 0) {
                    $this->attributes['paramsValue'][$paramName] = $paramArr[$paramName];
                } else {
                    $this->attributes['paramsValue'][$paramName] = $paramArr[1];
                }
            }
        }
        
        return $this;
    }
}
