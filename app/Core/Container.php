<?php
/**
 * XooPress Dependency Injection Container
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Container
{
    /**
     * The container's shared instances
     * 
     * @var array
     */
    protected array $instances = [];
    
    /**
     * The container's bindings
     * 
     * @var array
     */
    protected array $bindings = [];
    
    /**
     * The container's resolved instances
     * 
     * @var array
     */
    protected array $resolved = [];
    
    /**
     * Register a shared instance in the container
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }
    
    /**
     * Register a binding in the container
     * 
     * @param string $abstract
     * @param callable|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, callable|string|null $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }
    
    /**
     * Register a shared binding in the container
     * 
     * @param string $abstract
     * @param callable|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Resolve a service from the container
     * 
     * @param string $abstract
     * @return mixed
     * @throws \Exception
     */
    public function get(string $abstract): mixed
    {
        // Check if we have an instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Check if we have a binding
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            
            // If shared and already resolved, return the resolved instance
            if ($binding['shared'] && isset($this->resolved[$abstract])) {
                return $this->resolved[$abstract];
            }
            
            // Resolve the concrete
            $concrete = $binding['concrete'];
            
            if (is_callable($concrete)) {
                $instance = $concrete($this);
            } elseif (is_string($concrete) && class_exists($concrete)) {
                $instance = $this->build($concrete);
            } else {
                $instance = $concrete;
            }
            
            // Store if shared
            if ($binding['shared']) {
                $this->resolved[$abstract] = $instance;
            }
            
            return $instance;
        }
        
        // Try to auto-resolve class
        if (class_exists($abstract)) {
            return $this->build($abstract);
        }
        
        throw new \Exception("No binding found for [$abstract]");
    }
    
    /**
     * Check if a service exists in the container
     * 
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || 
               isset($this->bindings[$abstract]) || 
               class_exists($abstract);
    }
    
    /**
     * Build a class instance
     * 
     * @param string $class
     * @return object
     * @throws \Exception
     */
    protected function build(string $class): object
    {
        try {
            $reflector = new \ReflectionClass($class);
            
            // Check if the class is instantiable
            if (!$reflector->isInstantiable()) {
                throw new \Exception("Class [$class] is not instantiable");
            }
            
            // Get the constructor
            $constructor = $reflector->getConstructor();
            
            // If no constructor, instantiate directly
            if (is_null($constructor)) {
                return $reflector->newInstance();
            }
            
            // Get constructor parameters
            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);
            
            // Instantiate with dependencies
            return $reflector->newInstanceArgs($dependencies);
        } catch (\ReflectionException $e) {
            throw new \Exception("Unable to build [$class]: " . $e->getMessage());
        }
    }
    
    /**
     * Resolve method dependencies
     * 
     * @param array $parameters
     * @return array
     * @throws \Exception
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            // Get the type of the parameter
            $type = $parameter->getType();
            
            if (!$type) {
                // No type hint, check for default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Unable to resolve dependency [{$parameter->getName()}]");
                }
            } elseif ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                // Class type hint
                $className = $type->getName();
                $dependencies[] = $this->get($className);
            } elseif ($parameter->isDefaultValueAvailable()) {
                // Built-in type with default value
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \Exception("Unable to resolve dependency [{$parameter->getName()}]");
            }
        }
        
        return $dependencies;
    }
}