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

use Kazinduzi\Core\Controller;
use Kazinduzi;

class DefaultController extends Controller 
{     
    /**
     * Constructor for loginController
     */
    public function __construct() 
    {
        parent::__construct();	        
        $this->setLayout('default/default');
        $this->Template->setViewSuffix('phtml');
    }

    // We check if they are logged in, generally this would be done in the constructor, but we want to allow customers to log out still
    // or still be able to either retrieve their password or anything else this controller may be extended to do
    // if they are logged in, we send them back to the dashboard by default, if they are not logging in
    public function index() 
    {        
        $this->title = __('messages.welcome');        
        $this->content = 'Welcome to Kazinduzi framework v' . Kazinduzi::version();        
    }
    
    public function test()
    {
        $data = ['token' => bin2hex(openssl_random_pseudo_bytes(16))];
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }
    
}