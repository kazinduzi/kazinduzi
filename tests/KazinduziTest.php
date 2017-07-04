<?php
namespace Kazinduzi\Tests;

use Kazinduzi\Core\Kazinduzi;

class KazinduziTest extends \PHPUnit_Framework_TestCase
{
    /**
     * 
     */
    public static function setupBeforeClass()
    {
        // ini_set('log_errors', 0);
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'kazinduzi'));
    }
    
    /**
     * 
     */
    public static function tearDownAfterClass()
    {
        // ini_set('log_errors', 1);
    }
}