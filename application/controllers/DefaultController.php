<?php
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

use Kazinduzi\Core\Controller;
use Kazinduzi\Core\Kazinduzi;
use Kazinduzi\Core\Request;
use Kazinduzi\Core\Response;

class DefaultController extends Controller
{
    
    /**
     * 
     * @return type
     */
    public function index()
    {       
        //$templating = $this->getDIContainer()->get('templating');
        $this->setLayout('default/default');        
        $this->title = __('messages.welcome');
        $this->content = 'Welcome to Kazinduzi framework v' . Kazinduzi::version();        
        $content = $this->render('default/index.phtml');
        return new Response($content);        
    }

    /**
     * 
     */
    public function test()
    {
        $data = ['token' => bin2hex(openssl_random_pseudo_bytes(16))];
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }
}
