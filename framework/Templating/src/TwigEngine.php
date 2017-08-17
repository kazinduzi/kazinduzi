<?php
namespace Kazinduzi\Templating;

use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Description of TwigEngine
 *
 * @author Emmanuel
 */
class TwigEngine implements TemplatingEngine
{
    private $engine;
    private $templatesFolder;
    private $templateFolderCache;

    /**
     * Constructor
     * 
     * @param string $templatesFolder
     * @param string|null $cacheFolder
     */
    public function __construct($templatesFolder, $cacheFolder = null) 
    {
        $this->templatesFolder = $templatesFolder;
        $this->templateFolderCache = $cacheFolder;
        
        if (null === $this->templateFolderCache) {
            $this->templateFolderCache = $this->templatesFolder . '/cache';
        }
        $loader = new Twig_Loader_Filesystem($this->templatesFolder);
        $this->engine = new Twig_Environment($loader, [
            'cache' => false,            
        ]);
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
     * Get folder for template cache
     * 
     * @return string
     */
    public function getTemplateFolderCache() 
    {
        return $this->templateFolderCache;
    }

    /**
     * Render template
     * 
     * @param type $template
     * @param array $data
     * @return string
     */
    public function render($template, array $data = []) 
    {
        return $this->engine->render($template, $data);        
    }

}
