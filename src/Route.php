<?php

/**
 * Zelasli Routing
 *
 * @package Zelasli\Routing
 * @author Rufai Limantawa <rufailimantawa@gmail.com>
 * @version 0.3.5
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
        $this->attributes['vargsCount'] = 0;

        // count total number of parameter that has value from url string
        if (isset($this->attributes['params'])) {
            foreach ($this->attributes['params'] as $param) {
                if ($param['isVarg']) {
                    $this->attributes['vargsCount'] += 1;
                }
            }
        }
    }

    /**
     * Get controller's method for this route.
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
     * Get regexpr url.
     *
     * @return string
     */
    public function getAttrUrl(): string
    {
        return $this->attributes['url'] ?? '';
    }

    /**
     * Get controller class name.
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
     * Get the controller name.
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
     * Get value option set with the route
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function getOption($name): mixed
    {
        return isset($this->attributes['options'][$name])
            ? $this->attributes['options'][$name]
            : null;
    }

    /**
     * Get multiple options value at a time
     *
     * @param array $names
     *
     * @return array
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
    public function getParams(): array
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
     * Get patterned URL
     *
     * @param array|null $params
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the number of variable arguments
     *
     * @return int
     */
    public function getVargsCount(): int
    {
        return $this->attributes['vargsCount'];
    }

    /**
     * Check whether this route has non null value of an option.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name): bool
    {
        return isset($this->attributes['options'][$name]);
    }

    /**
     * Check if this route's controller action has parameters to pass
     *
     * @return bool
     */
    public function hasVargsParam(): bool
    {
        return $this->attributes['vargsCount'] > 0;
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

        foreach ($attrsParams as $param) {
            foreach ($params as $paramArr) {
                if (((int) $param['name']) === 0) {
                    $this->attributes['paramsValue'][$param['name']] = $paramArr[$param['name']];
                } else {
                    $this->attributes['paramsValue'][$param['name']] = $paramArr[1];
                }
            }
        }

        return $this;
    }
}
