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

use Pimple\Container;

abstract class Controller
{
    const DEFAULT_ACTION = 'index';
    const DEFAULT_CONTROLLER = 'index';

    public $Request;
    public $Response;
    public $defaultAction = self::DEFAULT_ACTION;

    private $_in_layout_display = true;
    private $action;
    private $controller;
    private $args;
    private $params;

    protected $registry = null;
    protected $methods = [];
    protected $models;
    protected $Template;

    private static $instance;
    private $container;

    /**
     * Method to get a singleton controller instance.
     *
     * @param	string	The name for the controller.
     *
     * @return mixed Controller derivative class.
     */
    public static function getInstance($Request, $Response)
    {
        $controllerClassName = get_called_class();
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        if ($Request instanceof Request && $Response instanceof Response) {
            return self::$instance = new $controllerClassName($Request, $Response);
        } else {
            return self::$instance = new $controllerClassName();
        }
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
     * Methot to construct the controller.
     *
     * @param Request  $Request
     * @param Response $Response
     */
    protected function __construct(Request $Request = null, Response $Response = null)
    {
        $this->Request = $Request instanceof Request ? $Request : Request::getInstance();
        $this->params = $this->Request->getParams();
        $this->Response = $Response instanceof Response ? $Response : Response::getInstance();
        $this->Template = new Template();
        // Get the public methods in this class.
        $reflector = new \ReflectionClass(get_class($this));
        $reflectorName = $reflector->getName();
        $reflectorMethods = $reflector->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($reflectorMethods as $reflectorMethod) {
            $methodName = $reflectorMethod->getName();
            if (!in_array($methodName, get_class_methods('Controller')) || $methodName == self::DEFAULT_ACTION) {
                $this->methods[] = strtolower($methodName);
            }
        }
        $this->init();
    }

    /**
     * All controllers must contain an index method.
     */
    abstract public function index();


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
        return $this->Request ? $this->Request : Request::getInstance();
    }

    /**
     * Get HTTP_Response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->Response ? $this->Response : Response::getInstance();
    }

    /**
     * @param type $name
     * @param type $config
     *
     * @return type
     */
    protected function createModel($name, $config = [])
    {
        $modelName = preg_replace('/[^A-Z0-9_]/i', '', $name);

        return $result = Model::getInstance($modelName, $config);
    }

    /**
     * @param string $name
     * @param array  $config
     *
     * @return \Model
     */
    public function getModel($name = '', $config = [])
    {
        if (empty($name)) {
            $name = $this->getName();
        }

        return $this->createModel($name, $config);
    }

    /**
     * @param type $url
     * @param type $status
     */
    public function redirect($url, $status = 302)
    {
        header('Status: '.$status);
        header('Location: '.str_replace('&amp;', '&', $url));
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
     *
     * @throws Exception
     *
     * @return void
     */
    public function executeAction($action)
    {
        try {
            $this->before();
            $this->{$action}($this->getArgs());
            $this->after();
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
            $this->executeAction($this->getAction());
            if ($this->isLayoutDisplayed()) {
                $this->Template->setLayout($this->getLayout());
                $this->Template->display();
            } else {
                $this->Template->render();
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
     * Prevent cloning the controller
     * Declaring this magic method __clone will prevent all attempt to clone this.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Magic set data of the controller.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->Template->__set($key, $value);
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
        return $this->Template->__get($key);
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
        return $this->Template->__isset($name);
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
        $this->Template->__unset($name);
    }

    /**
     * Forces the user's browser not to cache the results of the current Request.
     *
     * @return void
     */
    protected function disableCache()
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    /**
     * @param type $object
     */
    public function inspect($object)
    {
        $methods = get_class_methods($object);
        $data = get_class_vars(get_class($object));
        $odata = get_object_vars($object);
        $parent = get_parent_class($object);
        $output = 'Parent class: '.$parent."\n\n";
        $output .= "Methods:\n";
        $output .= "--------\n";
        foreach ($methods as $method) {
            $meth = new ReflectionMethod(get_class($object), $method);
            $output .= $method."\n";
            $output .= $meth->__toString();
        }
        $output .= "\nClass data:\n";
        $output .= "-----------\n";
        foreach ($data as $name => $value) {
            $output .= $name.' = '.print_r($value, 1)."\n";
        }
        $output .= "\nObject data:\n";
        $output .= "------------\n";
        foreach ($odata as $name => $value) {
            $output .= $name.' = '.print_r($value, 1)."\n";
        }
        echo '<pre>', $output, '</pre>';
    }
}
