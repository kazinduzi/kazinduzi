<?php
namespace Kazinduzi\Core;

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */
use Pimple\Container;
use Kazinduzi\Core\FrontController;
use Kazinduzi\Core\Controller;

class Dispatcher
{
    /**
     * @var Container Dependency Injection Container
     */
    private $container;
    
    /**
     * Constructor
     * 
     * @param Container $dic
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Dispatch the application
     */
    public function dispatch()
    {
	$frontCortroller = FrontController::getInstance();
        $frontCortroller->setDIContainer($this->container);
	$frontCortroller->loadController();
	if ($frontCortroller->getCallableController() instanceof Controller) {
	    $frontCortroller->getCallableController()->run();	    
	}
    }

}
