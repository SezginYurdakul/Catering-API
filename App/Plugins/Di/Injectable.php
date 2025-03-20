<?php


namespace App\Plugins\Di;


use App\Plugins\Di\Traits\InjectionAwareTrait;
use Exception;

abstract class Injectable {
    use InjectionAwareTrait;

    /**
     * Magic method __get
     *
     * @param string $propertyName
     * @return mixed|void
     * @throws Exception
     */
    public function __get(string $propertyName) {
        $bucket = $this->getDI();

        if ('di' === $propertyName) {
            $this->di = $bucket;
            return $bucket;
        }

        if (true === $bucket->hasShared($propertyName)) {
            $service = $bucket->getShared($propertyName);
            $this->$propertyName = $service;

            return $service;
        }

        /**
         * A notice is shown if the property is not defined and isn't a valid service
         */
        trigger_error('Access to undefined property ' . $propertyName);
    }

    /**
     * Returns the internal dependency injector
     * @throws Exception
     */
    public function getDI() {
        if (null === $this->container) {
            $this->container = Factory::getDi();
        }

        return $this->container;
    }
}
