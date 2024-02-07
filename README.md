# Zelasli Routing

A routing module that link single HTTP request to a destination (controller's action)

## **Installation**

To install using composer in your project (**`recommended`**):

```bash
composer require zelasli/routing
```

You can also clone the repository and include all the files resides inside `src/` to the entry point of your project.

```bash
git clone https://github.com/zelasli/routing.git
```

and includes the modules files

```php
require_once 'path/to/src/Placeholders.php';
require_once 'path/to/src/Route.php';
require_once 'path/to/src/RouteBuilder.php';
require_once 'path/to/src/RouteCollection.php';
require_once 'path/to/src/Router.php';
```

## Getting started

Create the `RouteBuilder` which will build the linking of the `URL` to the given destination.

```php
$routeBuilder = new RouteBuilder(new RouteCollection);
```

### **Defining the linking**

Define all your request linking and grouped linking to a prefix initial. The link is a method in the `RouteBuilder` that create `Route` for the request url and link that request URL to destination of an action. This action comprises of class namespace and method to be invoked, with it's parameter(s) to pass.

The first parameter define the URL that will match the linking (`Route`), and the second parameter is the class namespace with action, and parameter to be extracted from the very first parameter.

```php
$builder->link('/home', 'HomeController::index')
```

The above linking can also be defined like this which the second parameter to be an array of the controller class and action (the class method to invoke).

```php
$builder->link('/home', ["HomeController", "index"]);
```

### **Linking with parameters**

The linking can also collect parameters to pass when invoking a controller's action.

```php
$builder->link('/blogs/(:digit)', "App\\Controllers\\BlogController::view/{1}");
```

This means that the matching of `/blogs/123456789` will extract the value `123456789` as a number and it means that the method `view` in class `App\Controllers\BlogController` has a parameter that collect integer value.

You can also use name of parameter like this

```php
$builder->link('/blogs/(blogId:digit)', "App\\Controllers\\BlogController::view/{blogId}");
```

### **Linking with name**

A linking can also have a name used to reverse the route

```php
$builder->link('/blogs/(blogId:digit)', "App\\Controllers\\BlogController::view/{blogId}", [
    'name' => 'blogViewPage'
]);
```

### **Grouping related linking**

You can also group related linking with the same initial.

```php
$builder->group('/blogs', function (RouteBuilder $builder) {
    $builder->link('/', "App\\Controllers\\BlogController::view_all", [
        'name' => 'blogsViewAll'
    ]);
    $builder->link('/create', "App\\Controllers\\BlogController::create", [
        'name' => 'blogsCreate'
    ]);
    $builder->link('/view/(blogId:digit)', "App\\Controllers\\BlogController::view/{blogId}", [
        'name' => 'blogsView'
    ]);
    $builder->link('/delete/(blogId:digit)', "App\\Controllers\\BlogController::delete/{blogId}", [
        'name' => 'blogsDelete'
    ]);
    $builder->link('/update/(blogId:digit)', "App\\Controllers\\BlogController::update/{blogId}", [
        'name' => 'blogsUpdate'
    ]);
});
```

## **Working with Router**

The main use of `Router` is to find the `Route` that matches the current HTTP request url. Now, get the instance of `Router` object from the builder.

```php
$router = $routeBuilder->getRouterInstance();
```

### **Find the matched Route**

Here you will ask `Router` to give you a `Route` that match the specifier you give.

```php
$url = parse_url($_SERVER['REQUEST_URI']); // use parse_url() because query string may have been exists
$route = $router->findRouteByUrl($url['path']);
```

If a client request goes like this: `http:://example.com/blogs/view/123456789` then the `Router` will search in the collection and find the matched `Route` instance. Now get the the class and it's method

```php
// http:://example.com/blog/123456789

$class = $route->getClass(); // App\Controllers\BlogController
$action = $route->getAction(); // view
$params = $route->getParams(); // [blogId => 1282]
```

Get them ready to invoke the action (method)

```php
$obj = new $class(); // $obj = new App\Controllers\BlogController();
$obj->{$action}(...$params); // $obj->view(...[blogId => 1282])
```

### **Reverse URL**

The `Router` can also reverse `URL` string of a named linking (`Route`) using `Router::reverseUrl`

```php
$router->reverseUrl('blogsView', [
    'blogId' => 1234
]);
// return: /blogs/view/1234
```

You can also define you own placeholder types using the `Router::registerPlaceholder` function.

```php
Router::registerPlaceholder('date', '\d{4}\/\d{1,2}\/\d{1,2}', false);
```

The fisrt parameter passed is the name of type, the second is pattern, and the third is a boolean whether your defined type can have quantifier or not, the default is true. Then use it the way you are using [types](#route-types)

```php
$builder->link('/blog/(d:date)/(id:digit)', 'App\\Blog\\Post::view/{date}/{id}');
```

The above type will match the request: `http://example.com/blog/2006/3/21/1234`. This will extract `2006/3/21` as `$d` and `1234` as `$id`.

## **Route**

As we already see we use `builder->link($url, $destination, $options)` to create new Route that link the `$url` to the `$destination` with optional parameter `$options`. Here we go deep into it.

### **Route URL**

The first parameter `$url` in `RouteBuilder::link` can contains placeholders to be extracted called variable arguments which can be passed to class method as it's parameters. This placeholder's rule syntax look like:

> ([name]\[:type]\[:quantifier])

**[name]** - _Optional_. The name of parameter for this placeholder

**[:type]** - The type of value this placeholder can hold.

**[:quantifier]** - _Optional_. The length of the value.

The second parameter is the destination of the request URL that `Route` map to. Which contains a class and it's method with parameters given according to the order they are written in that method.

### **Route Types**

These are the list of valid placeholder types defined.

|   Type  | Has quantifier | Description |
|:-------:|:--------------:|:-----------:|
any       |     true       | This placeholder matches any type of character except new line
alnum     |     true       | This placeholder matches alphanumeric character character
alpha     |     true       | This placeholder will matches alphabet only
bit       |     true       | This placeholder will matches binary numbers only.
day       |     false      | This placeholder matches day format (01-31)
digit     |     true       | This placeholder matches decimal numbers (0-9)
lower     |     true       | This placeholder matches lower case alphabet
month     |     false      | This placeholder matches month format (01-12)
odigit    |     true       | This placeholder matches octal numbers (0-7)
upper     |     true       | This placeholder matches upper case alphabet
uuid      |     false      | This placeholder matches UUID
xdigit    |     true       | This placeholder matches hexadecimal numbers (0-9, a-z)
year      |     false      | This placeholder matches year format (1000-2999 only)

For the placeholder that can have quantifier, this is the format they can be written.

| Example | Description |
|:-------:|:-----------:|
`(:digit)`      | This matches will be passed as the value of first parameter of method. The length is 1 to more decimal number.
`(id:digit)`    | This matches will be passed to parameter named `$id`. The length is the same as the above.
`(:alnum:3)`    | This matches exactly 3 string of characters.
`(:alnum:3,)`   | This matches 3 or more string of characters.
`(:alpha:3,50)` | This matches from 3 to 50 sitring of characters.
`(:alnum:,50)`  | This matches from 1 to 50 string of character(s).
`(:alnum:?)`    | This matches 0 or 1 string of character.
`(:alnum:*)`    | This matches from 0 or more string of character(s).
`(:alnum:+)`    | This matches from 1 or more string of character(s).
