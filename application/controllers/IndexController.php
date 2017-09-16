<?php

use Kazinduzi\Core\Response;

/**
 * Description of IndexController
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */
class IndexController extends \Kazinduzi\Core\Controller
{
    /**
     * IndexAction
     * 
     * @return Response
     */
    public function index()
    { 
        $this->_in_layout_display = false;
        return new Response('Greetings from Kazinduzi! Hello World!');
    }
}
