<?php

/**
 *
 * ThinkUp/webapp/_lib/model/class.Router.php
 *
 * Copyright (c) 2009-2011 Sam Rose
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkupapp.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 *
 * @author Sam Rose <samwho@lbak.co.uk>
 * @author http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2011 Sam Rose
 */
class Router {
    /**
     * The default controller to use for page requests.
     * @var string
     */
    private $default_controller = 'DashboardController';

    private $_404_controller = '404Controller';

    /**
     * The singletone instance of this class.
     * @var Router
     */
    private static $instance;

    /**
     * Gets the singleton instance of the Router object for ThinkUp. This object handles
     * the routing of URLs to their appropriate pages. If you want to specify a new URL
     * page rule, you will do it through this object.
     *
     * @return Router The Router object for ThinkUp.
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Router();
        }

        return self::$instance;
    }

    /**
     * The request URI of the page request as found in $_SERVER['REQUEST_URI']
     * @var string
     */
    private $request_uri;

    /**
     * The array of Route objects that will be used to match a URL to a controller.
     * @var array
     */
    private $routes = array();

    /**
     * A string containing the class name of the controller to use for this page load.
     * This variable will be null if none of the Routes matches the request URL.
     * @var string
     */
    private $controller;

    /**
     * An array of parameters for the matched route. This array will be null if a Route
     * does not match the current URL.
     * @var array
     */
    private $params;

    /**
     * Whether or not a valid route has been found for the current path.
     * @var bool
     */
    private $route_found = false;

    /**
     * Whether or not the execute function has been run.
     * @var bool
     */
    private $executed = false;

    private function __construct() {
        $request = $_SERVER['REQUEST_URI'];
        $pos = strpos($request, '?');
        if ($pos)
            $request = substr($request, 0, $pos);

        // the request URI includes that path from the web root up to the ThinkUp root. Need to fix that by removing
        // the ThinkUp root from the path.
        $this->request_uri = str_replace(Config::getInstance()->getValue('site_root_path'), '', $request);

        // ensure that the request uri has a leading forward slash
        if (strpos($this->request_uri, '/') !== 0) {
            $this->request_uri = '/' . $this->request_uri;
        }
    }

    /**
     * This function lets you set a new rule for mapping URLs on ThinkUp. Note that URL mapping
     * happens very early on in the life cycle of a ThinkUp page request. After a URL has been
     * mapped to a controller, this function does nothing.
     *
     * Some example uses:
     *
     * $router = Router::getInstance();
     * $router->map('/:user');
     *
     * Any section of a mapping rule that starts with a colon is a variabe. The default matching pattern
     * for variables is "([a-zA-Z0-9_\+\-%]+)". So if a URL matches that regex and nothing else, e.g.
     *
     * http://example.com/thinkup/samwhoo
     *
     * That URL will map to the default controller for page requests, DashboardController, with $_GET['user']
     * set to the string "samwhoo".
     *
     * If you want to specify your own mapping rules or controller you can do so by specifying extra parameters of the
     * function:
     *
     * $router->map('/post/:id', array('controller' => 'PostController'), array('id' => '[0-9]+'));
     *
     * This will match the /post part literally and the /:id part with the regex "[0-9]+". The following URL
     * would match:
     *
     * http://example.com/thinkup/post/123456789
     *
     * That will send a request to the PostController class with $_GET['id'] set to the string "123456789".
     *
     * If the controller you specify does not exist or does not inherit from the ThinkUpController class,
     * the request will fail.
     *
     * If you try and overwrite an already mapped rule, an exception will be thrown. Rules are mapped with their
     * rule strings (first argument of the map function) as the key. As such, it is possible to map the following
     * two rules:
     *
     * $router->map('/:firstrule');
     * $router->map('/:secondrule');
     *
     * In this situation, both rules will go through but the first one would take precedence.
     *
     *
     * @param string $rule The rule for this new URL mapping.
     * @param array $params The parameters to send this mapping. Optional.
     * @param array $conditions The regex conditions for sections of the URL. Optional.
     */
    public function map($rule, $params = array(), $conditions = array()) {
        if (!isset($this->routes[$rule])) {
            $this->routes[$rule] = new Route($rule, $this->request_uri, $params, $conditions);
        } else {
            throw new Exception("Tried to overwrite an existing URL mapping rule.");
        }
    }

    private function setRoute($route) {
        $this->route_found = true;
        $this->controller = isset($route->params['controller']) ?
                $route->params['controller'] : $this->default_controller;
        unset($route->params['controller']);
        $_GET = array_merge($route->params, $_GET);
    }

    /**
     * Search through all the currently mapped rules until a match on the current
     * request URI is found. When a match is found, this object's controller and params
     * member variables will be set to whatever the Route has defined them to be.
     */
    private function execute() {
        foreach ($this->routes as $route) {
            if ($route->is_matched) {
                $this->setRoute($route);
                $this->executed = true;
                break;
            }
        }
    }

    /**
     * Runs the router matching and returns the appropriate controller for the URI. If no match is found or the
     * controller class specified does not exist, the default 404 controller is returned.
     *
     * This is only intended to be run in the router.php file. Running it anywhere else is very likely to break things.
     * 
     * @return str A controller class string.
     */
    public function getController() {
        if (!$this->executed) {
            $this->execute();
        }

        if ($this->route_found) {
            if (class_exists($this->controller)) {
                return $this->controller;
            } else {
                return $this->_404_controller;
            }
        } else {
            return $this->_404_controller;
        }
    }

}
