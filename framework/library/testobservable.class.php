<?php defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

class Testobserver extends Observer {

    public function update(&$sender, $arg) {
        switch ($arg) {
            case 'changed':
                echo 'Changed<br />';
                print_r($sender);
                break;
            case 'deleted':
                echo 'Deleted<br />';
                print_r($sender);
                break;
            default :
                print_r($sender);
                break;
        }
    }
}

class TestObservable extends Observable {

    public function changed() {
        echo 'Observable is changed<br/>';
        //Notify all attached observers to this
        $this->notifyAll('changed');
    }
    //
    public function deleted() {
        echo 'Observable is deleted<br/>';
        //Notify all attached observers to this
        $this->notifyAll('deleted');
    }
}

?>
