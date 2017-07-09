<?php
namespace Kazinduzi\Tests;

use Kazinduzi\Core\Kazinduzi;
use PHPUnit\Framework\TestCase;

class KazinduziTest extends TestCase
{
    /**
     * 
     */
    public function testAppName()
    {
        Kazinduzi::setAppName('KazinduziTest');
        $this->assertEquals('KazinduziTest', Kazinduzi::getAppName());
    }

    /**
     * 
     */
    public function testPushAndPop()
    {     
        $stack = [];
        $this->assertEquals(0, count($stack));

        array_push($stack, 'foo');
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));

        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));
    }
}