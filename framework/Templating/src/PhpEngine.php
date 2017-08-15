<?php

namespace Kazinduzi\Templating;

/**
 * Description of PhpEngine
 *
 * @author Emmanuel
 */
class PhpEngine implements TemplatingEngine 
{
    private $templatesFolder;
    
    /**
     * Constructor
     * 
     * @param string $templatesFolder
     * @param string|null $cacheFolder
     */
    public function __construct($templatesFolder, $cacheFolder = null) 
    {
        $this->templatesFolder = $templatesFolder;        
    }

    /**
     * Get folder for templates
     * 
     * @return string
     */
    public function getTemplateFolder() 
    {
        return $this->templatesFolder;
    }    
    
    /**
     * Render template
     * 
     * @return string
     */
    public function render($template, array $data = [])
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }
        
        ob_start();
        ob_implicit_flush(false);
        try {
            include $this->templatesFolder . '/' . $template;
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            print_r($e);
        }

        return ob_get_clean();
        
    }
    
}
