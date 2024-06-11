<?php

namespace DigitalPolygon\PolymerTest\PHPStan;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ObjectType;

class CollectionBuilderReflectionExtension implements MethodsClassReflectionExtension {

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->hasNativeMethod($methodName) || array_key_exists($methodName, $classReflection->getMethodTags())) {
            // Let other parts of PHPStan handle this.
            return false;
        }
        $interfaceObject = new ObjectType('Robo\Collection\CollectionBuilder');
        $objectType = new ObjectType($classReflection->getName());
        if (!$interfaceObject->isSuperTypeOf($objectType)->yes()) {
            return false;
        }

//        if ($methodName === 'exec') {
//            return true;
//        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): \PHPStan\Reflection\MethodReflection
    {
        $x = 5;
        // TODO: Implement getMethod() method.
    }
}
