<?php
namespace Kazinduzi\IoC;

/**
 * Description of ContainerAwareTrait
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */
trait ContainerAwareTrait
{    
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Sets the container.
     *
     * @param Container|null $container A Container instance or null
     * @return $this
     */
    public function setContainer(Container $container = null)
    {
        $this->container = $container;
        return $this;
    }
    
    /**
     * Gets the container.
     * 
     * @return Container|null
     */
    public function getContainer()
    {
        return $this->container;
    }
}
