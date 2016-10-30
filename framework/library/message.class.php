<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/).
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 *
 * @link      http://kazinduzi.com
 *
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 */
class Message
{
    /**
     * @var type
     */
    private $msgId;

    /**
     * @var type
     */
    private $msgTypes = ['help', 'info', 'warning', 'success', 'error'];

    /*
     *
     */
    private $msgClass = 'messages';

    /**
     * @var type
     */
    private $msgWrapper = "<div class='%s %s'><a href='#' class='closeMessage'></a>\n%s</div>\n";

    /**
     * @var type
     */
    private $msgBefore = '<p>';

    /**
     * @var type
     */
    private $msgAfter = "</p>\n";



    public function __construct()
    {
        // Generate a unique ID for this user and session
        $this->msgId = md5(uniqid());
        // Create the session array if it doesnt already exist
        if (!array_key_exists('flash_messages', $_SESSION)) {
            $_SESSION['flash_messages'] = [];
        }
    }

    /**
     * @param type $type
     * @param type $message
     *
     * @throws Exception
     *
     * @return bool
     */
    public function add($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            return false;
        }
        if (!isset($type) || !isset($message[0])) {
            return false;
        }
        // Replace any shorthand codes with their full version
        if (strlen(trim($type)) == 1) {
            $type = str_replace(['h', 'i', 'w', 'e', 's'], ['help', 'info', 'warning', 'error', 'success'], $type);
        }
        // Backwards compatibility...
        elseif ($type == 'information') {
            $type = 'info';
        }
        // Make sure it's a valid message type
        if (!in_array($type, $this->msgTypes)) {
            throw new Exception('"'.strip_tags($type).'" is not a valid message type!');
        }
        // If the session array doesn't exist, create it
        if (!array_key_exists($type, $_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'][$type] = [];
        }

        $_SESSION['flash_messages'][$type][] = $message;

        return true;
    }

    /**
     * @param type $type
     * @param type $print
     *
     * @return bool
     */
    public function display($type = 'all', $print = true)
    {
        $messages = '';
        $data = '';
        if (!isset($_SESSION['flash_messages'])) {
            return false;
        }
        if ($type == 'g' || $type == 'growl') {
            $this->displayGrowlMessages();

            return true;
        }
        // Print a certain type of message?
        if (in_array($type, $this->msgTypes)) {
            foreach ($_SESSION['flash_messages'][$type] as $msg) {
                $messages .= $this->msgBefore.$msg.$this->msgAfter;
            }
            $data .= sprintf($this->msgWrapper, $this->msgClass, $type, $messages);
            // Clear the viewed messages
            $this->clear($type);
        }
        // Print ALL queued messages
        elseif ('all' == $type) {
            foreach ($_SESSION['flash_messages'] as $type => $msgArray) {
                $messages = '';
                foreach ($msgArray as $msg) {
                    $messages .= $this->msgBefore.$msg.$this->msgAfter;
                }
                $data .= sprintf($this->msgWrapper, $this->msgClass, $type, $messages);
            }
            // Clear ALL of the messages
            $this->clear();

        // Invalid Message Type?
        } else {
            return false;
        }
        // Print everything to the screen or return the data
        if ($print) {
            echo $data;
        } else {
            return $data;
        }
    }

    public function hasErrors()
    {
        return empty($_SESSION['flash_messages']['error']) ? false : true;
    }

    /**
     * @param type $type
     *
     * @return bool
     */
    public function hasMessages($type = null)
    {
        if (!is_null($type)) {
            if (!empty($_SESSION['flash_messages'][$type])) {
                return $_SESSION['flash_messages'][$type];
            }
        } else {
            foreach ($this->msgTypes as $type) {
                if (!empty($_SESSION['flash_messages'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return type
     */
    public function __toString()
    {
        return $this->hasMessages();
    }

    /**
     * @param type $type
     *
     * @return bool
     */
    public function clear($type = 'all')
    {
        if ('all' == $type) {
            unset($_SESSION['flash_messages']);
        } else {
            unset($_SESSION['flash_messages'][$type]);
        }

        return true;
    }


    public function __destruct()
    {
        //$this->clear();
    }
}
