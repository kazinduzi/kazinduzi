<?php

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
class Dispatcher
{

    /**
     * Dispatch the application
     */
    public function dispatch()
    {
	$frontCortroller = FrontController::getInstance();
	$frontCortroller->loadController();
	if ($frontCortroller->getCallableController() instanceof Controller) {
	    $frontCortroller->getCallableController()->run();	    
	}
    }

}
