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

use Kazinduzi\IoC\Container;
use Inflector;

abstract class Controller
{

    const DEFAULT_ACTION = 'index';
    const DEFAULT_CONTROLLER = 'Index';

    public $Request;
    public $Response;
    public $defaultAction = self::DEFAULT_ACTION;
    protected $_in_layout_display = true;
    protected $action;
    protected $controller;
    protected $args;
    protected $params;
    protected $methods = [];    
    protected $Template;    
    protected $container;
        
    /**
     * Constructor the controller.
     *
     * @param Request  $Request
     * @param Response $Response
     */
    public function __construct(Request $Request = null, Response $Response = null, Container $container)
    {
        $this->setDIContainer($container);
        $this->Request = $Request instanceof Request ? $Request : Request::getInstance();
        $this->params = $this->Request->getParams();
        $this->Response = $Response instanceof Response ? $Response : Response::getInstance();
        $this->Template = $container->get('templating');
        $this->Template->setController($this);
        // Get the public methods in this class.
        $reflector = new \ReflectionClass($this);
        // $reflectorName = $reflector->getName();
        $reflectorMethods = $reflector->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($reflectorMethods as $reflectorMethod) {
            $methodName = $reflectorMethod->getName();
            if (!in_array($methodName, get_class_methods(self::class)) || $methodName === self::DEFAULT_ACTION) {
                $this->methods[] = $methodName;
            }
        }
        $this->init();
    }
    
    /**
     * Set DI Container.
     *
     * @param Container $container
     */
    public function setDIContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get DI Container.
     *
     * @return Container
     */
    public function getDIContainer()
    {
        return $this->container;
    }

    /**
     * All controllers must contain an index method.
     */
    abstract public function index();

    /**
     * Initialize
     */
    public function init()
    {
        
    }

    /**
     * Automatically executed before the controller action. Can be used to set
     * class properties, do authorization checks, and execute other custom code.
     *
     * @return void
     */
    public function before()
    {
        
    }

    /**
     * Automatically executed after the controller action. Can be used to apply
     * transformation to the Request Response, add extra output, and execute
     * other custom code.
     *
     * @return void
     */
    public function after()
    {
        
    }

    /**
     * @param type  $template
     * @param array $data
     *
     * @return \Controller
     */
    public function setTemplate($template, array $data = [])
    {
        $this->Template->setFilename($template, $data);

        return $this;
    }

    /**
     * @return type
     */
    public function getTemplate()
    {
        return $this->Template;
    }

    /**
     * @return type
     */
    public function getLayout()
    {
        return $this->Template->getLayout();
    }

    /**
     * @param type $name
     */
    public function setLayout($name)
    {
        $this->Template->setLayout($name);

        return $this;
    }

    /**
     * Set the controller.
     *
     * @param string $controller
     *
     * @return \Controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Get the controller.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param type $action
     *
     * @return type
     */
    public function defaultAction($action = '')
    {
        if (!empty($action)) {
            $this->defaultAction = $action;
        }

        return $this->defaultAction;
    }

    /**
     * @param type $action
     *
     * @return \Controller
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return type
     */
    public function getAction()
    {
        if ($this->action) {
            return $this->action;
        }
    }

    /**
     * @param type $args
     *
     * @return \Controller
     */
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * @return type
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param int $index
     *
     * @return mixed
     */
    public function getArg($index = 0)
    {
        if (!isset($this->args[$index])) {
            return;
        }
        return $this->args[$index];
    }

    /**
     * Get HTTP_Request object.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->Request ? $this->Request : $this->container->get('request');
    }

    /**
     * Get HTTP_Response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->Response;
    }    

    /**
     * @param type $url
     * @param type $status
     */
    public function redirect($url, $status = 302)
    {
        header('Status: ' . $status);
        header('Location: ' . str_replace('&amp;', '&', $url));
        exit();
    }

    /**
     * @return type
     */
    public function getName()
    {
        if (!$this->name) {
            $r = null;
            if (!preg_match('/(.*)Controller/i', get_class($this), $r)) {
                exit('The application fails to get controller name');
            }
            $this->name = strtolower($r[1]);
        }
        return $this->name;
    }
    
    /**
     * Execute the requested action
     * First check if the methods {before &| after} are present for this action.
     * the dispatching will be executed as follows:
     * - $this->before(),
     * - $this->executeAction(),
     * - $this->after().
     * 
     * @param string $action
     * @return Response
     * @throws \Kazinduzi\Core\Exception
     */
    public function executeAction($action)
    {
        try {
            $this->before();
            $this->Response = $this->{$action}($this->getArgs());
            $this->after();
            return $this->Response;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Method to run the requested action.
     * Execute first the requested action,
     * then wrap the execution of the action within the display method of the template engine.
     *
     * @return void
     */
    public function run()
    {
        try {
            $response = $this->executeAction($this->getAction());

            if ($this->isLayoutDisplayed()) {
                $response = $this->getTemplate()->display();
            }

            if ($response instanceof Response) {
                return $response;
            }

            if (is_string($response)) {
                return new Response($response);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @return type
     */
    protected function isLayoutDisplayed()
    {
        return $this->_in_layout_display === true;
    }

    /**
     * @param type $flag
     *
     * @return \Controller
     */
    protected function setLayoutDisplayed($flag = true)
    {
        $this->_in_layout_display = (bool) $flag;

        return $this;
    }
  

    /**
     * Magic set data of the controller.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->getTemplate()->__set($key, $value);
    }

    /**
     * Magically get data of the controller.
     *
     * @param string $key
     *
     * @return mixed | null
     */
    public function __get($key)
    {
        return $this->getTemplate()->__get($key);
    }

    /**
     * Magic method __isset, overloading the isset method.
     *
     * @param mixed $name
     *
     * @return mixed
     */
    public function __isset($name)
    {
        return $this->getTemplate()->__isset($name);
    }

    /**
     * Magic method to be triggered when unset function is called.
     *
     * @param string $name
     *
     * @return void
     */
    public function __unset($name)
    {
        $this->getTemplate()->__unset($name);
    }

    /**
     * Forces the user's browser not to cache the results of the current Request.
     *
     * @return void
     */
    protected function disableCache()
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }
    
    /**
     * Get controller path
     * 
     * @return string
     */
    public function getPath()
    {
        return Inflector::pathize($this->getName());
    }

    /**
     * Render a template
     * 
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function render($template = null, array $data = [])
    {
        return $this->Template->render($template, $data);
    }

}
