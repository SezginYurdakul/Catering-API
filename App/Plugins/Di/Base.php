<?php

namespace App\Plugins\Di;

use Exception;

abstract class Base {
    /** @var array $services */
    private $services;

    /** @var array $sharedServices */
    private $sharedServices;

    /**
     * Base constructor.
     * @param array $services The services
     * @param array $sharedServices The shared services
     */
    public function __construct(array $services = [], array $sharedServices = []) {
        $this->services = $services;
        $this->sharedServices = $sharedServices;
    }

    /**
     * Function to set a service
     * @param $name
     * @param $service
     */
    public function set($name, $service) {
        $this->sharedServices[$name] = $service;
    }

    /**
     * Function to set a shared service
     * @param $name
     * @param $service
     */
    public function setShared($name, $service) {
        $service = $service();
        $this->sharedServices[$name] = $service;
    }

    /**
     * Function to get a service by type.
     * @param $type
     * @return mixed|null
     * @throws \ReflectionException
     */
    public function getTyped($type) {
        foreach ($this->sharedServices as $service) {
            if (ReflectionHelper::isOfType($type, $service)) {
                return $service;
            }
        }
        foreach ($this->services as $serviceFunc) {
            $service = $serviceFunc();
            if (ReflectionHelper::isOfType($type, $service)) {
                return $service;
            }
        }
        return null;
    }

    /**
     * Function to get a service
     * @param $name
     * @throws Exception
     */
    public function get($name) {
        if (!isset($this->services['$name'])) {
            throw new Exception('Service not registered in the DI', 1);
        }
        $serviceFunction = $this->services[$name];
        return $serviceFunction();
    }

    /**
     * Function to retrieve a shared service.
     * @param string $name The name of the shared service.
     * @return mixed Reference to the shared service.
     * @throws Exception
     */
    public function &getShared($name) {
        if (!isset($this->sharedServices[$name])) {
            throw new Exception("Tried to retrieve a shared service which is not registered in the DI", 1);
        }
        return $this->sharedServices[$name];
    }

    /**
     * Function to check if service exists
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool {
        return isset($this->services[$name]);
    }

    /**
     * Function to check if service exists
     * @param string $name
     * @return bool
     */
    public function hasShared(string $name): bool {
        return isset($this->sharedServices[$name]);
    }
}
