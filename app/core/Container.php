<?php

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use Exception;

/**
 * Contenedor IoC ligero compatible con PHP 7.4.
 * Soporta bind, singleton y auto-wiring via reflection.
 */
class Container
{
    /** @var array<string, callable|string> */
    private $bindings = [];

    /** @var array<string, object> */
    private $instances = [];

    /** @var array<string, bool> */
    private $singletons = [];

    /**
     * Registra un binding (interface → implementación concreta o factory).
     *
     * @param string          $abstract  Nombre de la interface o clase abstracta
     * @param callable|string $concrete  Nombre de la clase concreta o factory callable
     */
    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Registra un singleton. La primera vez que se resuelva se crea la instancia;
     * las siguientes veces se devuelve la misma.
     *
     * @param string          $abstract
     * @param callable|string $concrete
     */
    public function singleton(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->singletons[$abstract] = true;
    }

    /**
     * Registra una instancia ya creada.
     *
     * @param string $abstract
     * @param object $instance
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resuelve una clase o interface, inyectando sus dependencias automáticamente.
     *
     * @param string $abstract
     * @return object
     * @throws Exception
     */
    public function make(string $abstract): object
    {
        // Si ya tenemos una instancia singleton, devolverla
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Determinar la clase concreta
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // Si es un callable (factory), ejecutarlo
        if (is_callable($concrete) && !is_string($concrete)) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        // Si está marcado como singleton, guardar la instancia
        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Construye una clase resolviendo sus dependencias del constructor via reflection.
     *
     * @param string $concrete Nombre completo de la clase
     * @return object
     * @throws Exception
     */
    private function build(string $concrete): object
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("La clase [{$concrete}] no es instanciable. ¿Olvidaste registrar un binding?");
        }

        $constructor = $reflector->getConstructor();

        // Si no tiene constructor, simplemente instanciar
        if ($constructor === null) {
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resuelve las dependencias de los parámetros del constructor.
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws Exception
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Si no tiene type hint, intentar usar el valor por defecto
            if ($type === null || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception(
                        "No se puede resolver el parámetro [{$parameter->getName()}] sin type hint ni valor por defecto."
                    );
                }
                continue;
            }

            $typeName = $type->getName();

            try {
                $dependencies[] = $this->make($typeName);
            } catch (Exception $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw $e;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Verifica si un binding existe.
     *
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
