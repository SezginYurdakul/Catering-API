<?php


namespace App\Plugins\Di\Traits;

trait InjectionAwareTrait {
    protected $container = null;

    /**
     * Return the internal dependency injector
     * @return null
     */
    public function getDI() {
        return $this->container;
    }

    /**
     * Set the dependency injector
     * @param $container
     */
    public function setDI($container) {
        $this->container = $container;
    }
}
