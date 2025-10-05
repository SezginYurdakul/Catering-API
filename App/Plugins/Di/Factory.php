<?php


namespace App\Plugins\Di;

use Exception;

class Factory extends Base {
    /** The DI instance */
    private static $instance = null;

    /**
     * Function to get the di
     * @return Factory instance of this (the DI)
     * @throws Exception
     */
    public static function getDi(): self {
        global $di;
        if ($di && !self::$instance) {
            throw new Exception('Global variable $di usage is restricted, it already exists with incorrect DI instance');
        }

        if ($di) {
            return $di;
        }

        self::$instance = new self();
        $di = self::$instance;
        return $di;
    }

    public static function create($class, ...$args) {
        $params = ReflectionHelper::getConstructorParams($class);
        if (!$params) {
            return new $class();
        }
        $useParams = [];
        foreach ($params as $param) {
            if (!$param->getClass()) {
                continue;
            }
            $paramType = $param->getClass()->name;
            $val = self::getDi()->getTyped($paramType);
            $useParams[] = $val;
        }
        $useParams = array_merge($useParams, $args);
        return new $class(...$useParams);
    }
}
