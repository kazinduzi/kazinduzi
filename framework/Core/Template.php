<?php

namespace Kazinduzi\Core;

use Kazinduzi\Templating\TemplatingEngine;

/*
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

class Template
{

    /**
     * @var type
     */
    private $data = [];

    /**
     * @var type
     */
    private $file = false;

    /**
     * @var \Theme
     */
    private $Theme;

    /**
     * @var type
     */
    private $layout = 'default';

    /**
     * @var string
     */
    private $layoutFile;

    /**
     * @var string
     */
    private $viewSuffix = 'phtml';

    /**
     * @var string
     */
    private $layoutSuffix = 'phtml';
    
    /**
     * @var Controller
     */
    protected $controller;

    /**
     * @var TemplingEngine
     */
    private $templatingEngine;

    /**
     * Constructor of the template.
     *
     * @param string $file
     * @param string $suffix
     * @param array $data
     */
    public function __construct($file = null, $suffix = null, array $data = null)
    {
        if ($suffix) {
            $this->setViewSuffix($suffix);
        }
        if (null !== $file) {
            $this->setFilename($file);
        }
        if (null !== $data) {
            $this->data = $data + $this->data;
        }
    }

    /**
     * Set calling controller
     * 
     * @param \Kazinduzi\Core\Controller $controller
     * @return $this
     */
    public function setController(Controller $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Get calling controller
     * 
     * @return Controller
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Set templatingeEngine
     * 
     * @param TemplatingEngine $templatingEngine
     * @return $this
     */
    public function setTemplatingEngine(TemplatingEngine $templatingEngine)
    {
        $this->templatingEngine = $templatingEngine;
        return $this;
    }

    /**
     * Get TemplatingEngine
     * 
     * @return TemplatingEngine
     */
    public function getTemplatingEngine()
    {
        return $this->templatingEngine;
    }

    /**
     * Sets the view filename.
     *
     * @param   string  The template filename
     *
     * @throws View_Exception
     *
     * @return View
     */
    public function setFilename($file)
    {
        $this->file = $file . '.' . $this->viewSuffix;
        return $this;
    }

    /**
     * Get template filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->file;
    }

    /**
     * @param string $suffix
     *
     * @return \Template
     */
    public function setViewSuffix($suffix)
    {
        $this->viewSuffix = $suffix;

        return $this;
    }

    /**
     * @return type
     */
    public function getViewSuffix()
    {
        return $this->viewSuffix;
    }

    /**
     * @param string $suffix
     *
     * @return \Template
     */
    public function setLayoutSuffix($suffix)
    {
        $this->layoutSuffix = $suffix;

        return $this;
    }

    /**
     * @return string
     */
    public function getLayoutSuffix()
    {
        return $this->layoutSuffix;
    }

    /**
     * @param type $key
     * @param type $value
     *
     * @return Template
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->data[$k] = $v;
            }
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * @param type $key
     *
     * @return null
     */
    public function get($key)
    {
        if (!isset($this->data[$key])) {
            return;
        }
        $value = $this->data[$key];
        if ($value instanceof Closure) {
            return $value($this);
        } else {
            return $value;
        }
    }

    /**
     * Assigns a value by reference. The benefit of binding is that values can
     * be altered without re-setting them. It is also possible to bind variables
     * before they have values. Assigned values will be available as a
     * variable within the view file:.
     *
     * @param   string   variable name
     * @param   mixed    referenced variable
     *
     * @return $this
     */
    public function bind($key, &$value)
    {
        $this->data[$key] = &$value;

        return $this;
    }

    /**
     * @set undefined data
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param type $name
     *
     * @return type
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * magic unset.
     *
     * @param mixed $name
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->data)) {
            unset($this->data[$this->name]);
        }
    }

    /**
     * magic __toString().
     *
     * @return type
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * sets the layout to be used for the current template view.
     *
     * @param string $layout
     *
     * @return Template
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * gets the layout for rendering the current view.
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Set the theme.
     *
     * @param \Theme $theme
     *
     * @return \Template
     */
    public function setTheme(\Theme $theme)
    {
        $this->Theme = $theme;

        return $this;
    }

    /**
     * @return type
     */
    public function getTheme()
    {
        return $this->Theme;
    }

    /**
     * @see render the template voor the specific
     * controller
     */
    public function render($filename = null, array $data = [])
    {
        $viewFile = $this->getController()->getAction();
        if (! $this->file) {
            $controller_path = $this->getController()->getPath();
            $this->file = $controller_path . DIRECTORY_SEPARATOR . $viewFile . '.' . $this->getViewSuffix();
        }

        if ($data) {
            $this->data = $data;
        }

        // Load Templating Engine
        if ($filename) {
            return $this->getTemplatingEngine()->render($filename, $this->data);
        }

        return $this->getTemplatingEngine()->render($this->file, $this->data);
    }

    /**
     * Display the content of the view rendered within the layout.
     *
     * @params $content = data from the loaded template ,
     *
     * @param $layout = layout template to be used for the MVC
     */
    public function display()
    {
        if ($this->getTheme()) {
            $theme_path = $this->getTheme()->getFileinfo()->getPathname();
            $this->layoutFile = $theme_path . DS . $this->getLayout() . '.' . $this->getLayoutSuffix();
        } elseif (!is_file($this->layoutFile)) {
            $this->layoutFile = LAYOUT_PATH . DS . $this->getLayout() . '.' . $this->getLayoutSuffix();
        }

        $this->content_for_layout = $this->render();
        extract($this->data, EXTR_SKIP | EXTR_REFS);

        ob_start();
        ob_implicit_flush(false);
        try {
            include $this->layoutFile;
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            print_r($e);
        }
    }

    /**
     * Method to append the CSS.
     *
     * @return string
     */
    public function appendStylesheets()
    {
        if (!$this->cssStyles || !is_array($this->cssStyles)) {
            return;
        }
        foreach ($this->cssStyles as $css) {
            if (is_array($css)) {
                echo '<link rel="stylesheet" href="' . $css[0] . '">' . "\n";
            } else {
                echo '<link rel="stylesheet" href="' . $css . '">' . "\n";
            }
        }
    }

    /**
     * Method to append.
     *
     * @return string
     */
    public function appendJavascripts()
    {
        if (!$this->javascriptFiles || !is_array($this->javascriptFiles)) {
            return;
        }
        foreach ($this->javascriptFiles as $js) {
            if (is_array($js)) {
                echo '<script type="text/javascript" src="' . $js[0] . '"></script>' . "\n";
            } else {
                echo '<script type="text/javascript" src="' . $js . '"></script>' . "\n";
            }
        }
    }

}
