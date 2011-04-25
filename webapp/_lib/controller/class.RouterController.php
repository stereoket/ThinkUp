<?php
/**
 *
 * ThinkUp/webapp/_lib/controller/class.RouterController.php
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
class RouterController {

    /**
     * The Router object for this class.
     * @var Router 
     */
    private $router;

    public function __construct($session_started = false) {
        if (!$session_started) {
            session_start();
        }

        $this->router = Router::getInstance();
    }

    public function go() {
        $this->mapDefaultRoutes();
        ThinkUpController::initializeApp(get_class($this));

        $controller_name = $this->router->getController();
        $controller = new $controller_name($session_started = true);
        
        return $controller->go();
    }

    /**
     * Sets the ThinkUp core routes.
     */
    private function mapDefaultRoutes() {
        // post mapping
        $this->router->map('/post/:n/:t', array('controller' => 'PostController'), array('t' => '[0-9]+'));
        $this->router->map('/post/:n/:t/:v', array('controller' => 'PostController'), array('t' => '[0-9]+'));

        // user mapping.
        $this->router->map('/user/:n/:u', array('controller' => 'UserController', 'i' => SessionCache::get('user')));
        $this->router->map('/user/:n/:u/:v', array('controller' => 'UserController', 'i' => SessionCache::get('user')));

        // account mapping
        $this->router->map('/account/:m', array('controller' => 'AccountConfigurationController'));
        $this->router->map('/account/:m/:p', array('controller' => 'AccountConfigurationController'));

        // routes the network, user and view to the dashboard controller.
        $this->router->map('/:n/:u/:v');
        $this->router->map('/:n/:u');

        // default dashboard
        $this->router->map('/');
    }
}
