<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Lightweight dependency-injection container.
 *
 * Supports binding interfaces/abstracts to concrete factories, singleton
 * resolution, and constructor autowiring for classes without an explicit
 * binding. This is the single place object graphs are assembled — services
 * must depend on interfaces registered here, never instantiate their own
 * collaborators with `new`.
 */
final class Container
{
    private static ?Container $instance = null;

    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $singletons = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = function (self $c) use ($abstract, $factory) {
            return $this->singletons[$abstract] ??= $factory($c);
        };
    }

    public function make(string $abstract): object
    {
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        return $this->autowire($abstract);
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Cannot resolve unknown class [{$class}].");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "Cannot resolve primitive parameter [\${$parameter->getName()}] for [{$class}]."
            );
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
