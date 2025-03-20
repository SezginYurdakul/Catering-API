<?php

namespace App\Plugins\Di;

use ReflectionException;

class ReflectionHelper {
    /**
     * Function to get a classname
     * @param mixed $mixed String or object to retrieve classname of
     * @return String Classname if found. Crash otherwise.
     * @throws ReflectionException
     */
    public static function getClassName($mixed) {
        $class = new \ReflectionClass($mixed);
        if ($class) {
            return $class->name;
        }
        throw new \Exception('Class name could not be found for ' . print_r($mixed, true));
    }

    /**
     * Function to get the constructor params.
     * @param mixed $mixed String or object to retrieve constructor params of.
     * @return array|\ReflectionParameter[] ,        List of ReflectionParameter instances.
     * @throws ReflectionException
     */
    public static function getConstructorParams($mixed) {
        $class = new \ReflectionClass($mixed);
        $constructor = $class->getConstructor();
        return $constructor ? $constructor->getParameters() : [];
    }

    /**
     * Function to check of a variable is of given type.
     * @param string $type String type definition of class/interface.
     * @param mixed $mixed String or object
     * @return bool True if type matches, false otherwise.
     * @throws ReflectionException
     */
    public static function isOfType($type, $mixed) {
        $classInterfaces = class_implements($mixed);
        $implementsInterface = in_array($type, $classInterfaces);
        if ($implementsInterface) {
            return true;
        }
        return self::getClassName($mixed) == $type;
    }
}
