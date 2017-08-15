<?php
namespace Kazinduzi\Templating;

/**
 *
 * @author Emmanuel
 */
interface TemplatingEngine
{
    /**
     * 
     * @param type $template
     * @param array $data
     */
    public function render($template, array $data = []);
    
}
