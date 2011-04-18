<?php
/**
 *
 * ThinkUp/webapp/_lib/model/class.Route.php
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
class Route {

    public $is_matched = false;
    public $params;
    public $url;
    private $conditions;

    public function __construct($url, $request_uri, $params, $conditions) {
        $this->url = $url;
        $this->params = array();
        $this->conditions = $conditions;
        $p_names = array();
        $p_values = array();

        // match all of the variables (e.g. :id) in the URL.
        preg_match_all('@:([\w]+)@', $url, $p_names, PREG_PATTERN_ORDER);
        $p_names = $p_names[0];

        // create one regex for this URL rule
        $url_regex = preg_replace_callback('@:[\w]+@', array($this, 'regex_url'), $url);
        $url_regex .= '/?';
        
        if (preg_match('@^' . $url_regex . '$@', $request_uri, $p_values)) {
            array_shift($p_values);

            // add the matched :variable in the URL to the params array of this object
            foreach ($p_names as $index => $value)
                $this->params[substr($value, 1)] = urldecode($p_values[$index]);

            // add the additionally specified params to the params array
            foreach ($params as $key => $value)
                $this->params[$key] = $value;

            // set the object to matched
            $this->is_matched = true;
        }

        unset($p_names);
        unset($p_values);
    }

    /**
     * Takes matches from a preg_replace_callback function call and decides what regex to use for that
     * section of the Route URL.
     *
     * @param array $matches Passed to the function from preg_replace_callback
     * @return string Regex to use in matching a route pattern.
     */
    private function regex_url($matches) {
        // trim the colon from the start of the variable
        $key = str_replace(':', '', $matches[0]);

        // if the variable has its own regex condition specified, use that
        if (array_key_exists($key, $this->conditions)) {
            return '(' . $this->conditions[$key] . ')';
        } else {
            // else default to this regex for matching variables
            return '([a-zA-Z0-9_\+\-%]+)';
        }
    }

}

