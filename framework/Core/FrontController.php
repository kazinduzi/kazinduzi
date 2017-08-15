<?php

namespace Kazinduzi\Core;

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

/*
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

use Inflector;
use Kazinduzi\Core\Kazinduzi;
use Kazinduzi\IoC\Container;
use Kazinduzi\Core\Request;
use Kazinduzi\Core\Response;

class FrontController {

    private $container;
    private $controller;
    private $file;
    private $action;
    private $configs;
    private $CallableController;
    private $args = [];
    private $params = [];
    private $Response;
    private $Request;
    private static $instance;

    /**
     * Set DI Container.
     *
     * @param Container $container
     *
     * @return \FrontController
     */
    public function setDIContainer(Container $container) {
        $this->container = $container;

        return $this;
    }

    /**
     * Get DI Container.
     *
     * @return Container
     */
    public function getDIContainer() {
        return $this->container;
    }

    public static function getInstance(array $options = []) {
        if (empty(self::$instance)) {
            return self::$instance = new static(Request::getInstance(), Response::getInstance(), $options);
        }
        return self::$instance;
    }

    /**
     * @param Request  $Request
     * @param Response $Response
     */
    public function __construct(Request $Request, Response $Response, array $options = []) {
        $this->Request = $Request;
        $this->Response = $Response;
        $this->configs = Kazinduzi::getConfig()->toArray();
        $this->params = $this->Request->getParams();
        if (empty($options)) {
            $this->checkRequestRoute();
        } else {
            if (isset($options['controller'])) {
                $this->setController($options['controller']);
            }
            if (isset($options['action'])) {
                $this->setAction($options['action']);
            }
            if (isset($options['params'])) {
                $this->setArgs($options['params']);
            }
        }
    }

    /**
     * Parse the enveronment/server REQUEST_URI into routing.
     *
     * @return string
     */
    private function parseRequestUri() {
        $serverParams = $this->Request->serverParams();

        if (!isset($serverParams['REQUEST_URI'], $serverParams['SCRIPT_NAME'])) {
            return '';
        }
        $requestUriSegments = parse_url('http://dummy' . $serverParams['REQUEST_URI']);

        $query = isset($requestUriSegments['query']) ? $requestUriSegments['query'] : '';
        $uri = isset($requestUriSegments['path']) ? $requestUriSegments['path'] : '';

        if (isset($serverParams['SCRIPT_NAME'][0])) {
            if (strpos($uri, $serverParams['SCRIPT_NAME']) === 0) {
                $uri = (string) substr($uri, strlen($serverParams['SCRIPT_NAME']));
            } elseif (strpos($uri, dirname($serverParams['SCRIPT_NAME'])) === 0) {
                $uri = (string) substr($uri, strlen(dirname($serverParams['SCRIPT_NAME'])));
            }
        }
        // This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
        // URI is found, and also fixes the QUERY_STRING server var and $_GET array.
        if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0) {
            $query = explode('?', $query, 2);
            $uri = $query[0];
            $serverParams['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
        } else {
            $serverParams['QUERY_STRING'] = $query;
        }
        parse_str($serverParams['QUERY_STRING'], $_GET);
        if ($uri === '/' || $uri === '') {
            return '/';
        }

        // Do some final sanitizing of the URI and return it
        return $this->sanitizeRelativeDirectory($uri);
    }

    /**
     * @param type $uri
     *
     * @return type
     */
    private function sanitizeRelativeDirectory($uri) {
        $tok = strtok($uri, '/');
        $uri = [];
        while ($tok !== false) {
            if ((!empty($tok) || $tok === '0') && $tok !== '..') {
                $uri[] = $tok;
            }
            $tok = strtok('/');
        }

        return implode('/', $uri);
    }

    /**
     * @return null
     */
    private function checkRequestRoute() {
        if (Kazinduzi::$is_cli) {
            $protocol = 'cli';
            $options = Cli::options('route', 'method', 'get', 'post', 'referrer');
            if (isset($options['route'])) {
                $route = $options['route'];
            } elseif (true === $route) {
                $route = '';
            }
            $method = isset($options['method']) ? strtoupper($options['method']) : 'GET';
            if (isset($options['get'])) {
                parse_str($options['get'], $_GET);
            }
            if (isset($options['post'])) {
                parse_str($options['post'], $_POST);
            }
            if (isset($options['referrer'])) {
                $referrer = $options['referrer'];
            }
        }

        /*
         * Extract the uri_path in the $_SERVER['REQUEST_URI'] from the requested URL
         */
        $route = $this->parseRequestUri();

        if (isset($route)) {
            $route = str_replace(['//', '../'], '/', trim($route, '/'));
            $routeParts = array_filter(explode('/', rtrim($route, '/')));
        }
        if (empty($routeParts)) {
            $this->controller = $this->configs['default_controller'];
            $this->action = $this->configs['default_action'];

            return;
        }

        /*
         * From the requested route, we fetch the most outer controller which match the requested action.
         * This will prevent the earlier matched controller met when walking through the route.
         * Example: {/path/to/router}, with this requested route.
         *
         * If the controller PathToController does exists,
         * then the requested action outer will not be reached.
         * Thus, to surrender this, we MUST match the PathToOuterController most controller, if not we pop off the last part of the route
         * and we keep it for the action & arguments
         */
        $routeArgs = [];
        do {
            $controller_path = implode('/', $routeParts);
            if (is_file(APP_PATH . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . str_replace('../', '', $controller_path) . 'Controller.php')) {
                $this->file = APP_PATH . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . str_replace('../', '', $controller_path) . 'Controller.php';
                $this->controller = Inflector::camelize($controller_path);
                break;
            }
            array_push($routeArgs, array_pop($routeParts));
        } while (!empty($routeParts));

        $routeParts = array_reverse($routeArgs);
        if (isset($routeParts) && !empty($routeParts[0])) {
            $this->action = $routeParts[0];
            array_shift($routeParts);
            $this->args = $routeParts;
        }
        if (empty($this->action)) {
            $this->action = $this->configs['default_action'];
        }
        if ($this->configs['default_controller'] === strtolower($this->controller)) {
            if ($this->getAction() === $this->configs['default_action']) {
                $this->action = $this->configs['default_action'];
            }
        }
        unset($routeParts, $route);
    }

    /**
     * @return \class|null
     */
    public function loadController() {
        $controller = $this->getController();
        if (empty($controller)) {
            header('HTTP/1.1 404 Not Found', 404);
            $error_data = [
                'status' => 404,
                'title' => _('Unknown controller!'),
                'body' => _('No controller is available'),
            ];
            render('error404.phtml', $error_data);
            exit(1);
        }
        $class = ucfirst($controller . 'Controller');
        $this->CallableController = new $class($this->Request, $this->Response, $this->getDIContainer());
        $this->CallableController->setController($controller);
        if (!is_callable([$this->CallableController, $this->getAction()])) {
            $error_data = [
                'status' => 404,
                'title' => _('Unknown action called!'),
                'body' => _('Method <b>' . $this->getAction() . '</b> is not defined in the controller <b>' . $class . '</b>'),
            ];
            header('HTTP/1.1 404 Not Found');
            render('error404.phtml', $error_data);
            exit(1);
        } else {
            $this->CallableController->setAction($this->getAction());
            $this->CallableController->setArgs(array_values($this->getArgs()));
        }

        return $this;
    }

    /**
     * @return type
     */
    public function getCallableController() {
        return $this->CallableController;
    }

    /**
     * Set the controller.
     *
     * @param string $controller
     *
     * @return \FrontController
     */
    public function setController($controller) {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return type
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * @return type
     */
    public function getControllerToPath() {
        return Inflector::pathize($this->controller);
    }

    /**
     * Set the action.
     *
     * @param string $action
     *
     * @return \FrontController
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * Transform an "action" token into a method name.
     *
     * @param string $action
     *
     * @return string
     */
    public function getAction() {
        $action = str_replace(['.', '-', '_'], ' ', $this->action);
        $action = ucwords($action);
        $action = str_replace(' ', '', $action);
        $action = lcfirst($action);

        return $action;
    }

    /**
     * Set the arguments.
     *
     * @param array $args
     *
     * @return \FrontController
     */
    public function setArgs($args) {
        $this->args = $args;

        return $this;
    }

    /**
     * @return type
     */
    public function getArgs() {
        return $this->args;
    }

    /**
     * @return type
     */
    public function getRequest() {
        return $this->Request;
    }

    /**
     * @return type
     */
    public function getResponse() {
        return $this->Response;
    }

}

final class NotFoundException extends \Exception {
    
}
