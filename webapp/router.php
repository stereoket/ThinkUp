<?php
/**
 *
 * ThinkUp/webapp/router.php
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
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2011 Sam Rose
 */
require_once 'init.php';
$router = Router::getInstance();

$router->map('/post/:n/:t', array('controller' => 'PostController'), array('t' => '[0-9]+'));
$router->map('/user/:n/:u', array('controller' => 'UserController', 'i' => SessionCache::get('user')));
$router->map('/user/:u', array('controller' => 'UserController', 'i' => SessionCache::get('user'), 'n' => 'twitter'));
$router->map('/:n/:u:v');
$router->map('/');

$router->execute();

if (class_exists($router->controller)) {
    $controller = new $router->controller();
    if (is_a($controller, 'ThinkUpController')) {
        echo $controller->go();
    } else {
        // controller incorrect type
    }
} else {
    // controller class does not exist
}