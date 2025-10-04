<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Crud;

use ArrayIterator;
use Tobento\App\Crud\Exception\ResourceTypeNotFoundException;
use Traversable;

class ResourceTypes implements ResourceTypesInterface
{
    /**
     * @var array<string, ResourceTypeInterface>
     */
    protected array $types = [];
    
    /**
     * Create a new ResourceTypes instance.
     *
     * @param ResourceTypeInterface ...$types
     */
    public function __construct(
        ResourceTypeInterface ...$types,
    ) {
        foreach($types as $type) {
            $this->add($type);
        }
    }
    
    /**
     * Adds a resource type.
     *
     * @param ResourceTypeInterface $type
     * @return static $this
     */
    public function add(ResourceTypeInterface $type): static
    {
        $this->types[$type->name()] = $type;
        return $this;
    }
    
    /**
     * Returns true if type exists, otherwise false.
     *
     * @param string $type
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }
    
    /**
     * Returns the resource type.
     *
     * @param string $type
     * @return ResourceTypeInterface
     * @throws ResourceTypeNotFoundException
     */
    public function get(string $type): ResourceTypeInterface
    {
        if (! $this->has($type)) {
            throw new ResourceTypeNotFoundException(type: $type);
        }
        
        return $this->types[$type];
    }
    
    /**
     * Returns all types.
     *
     * @return array<string, ResourceTypeInterface>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Returns all names.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->all());
    }
    
    /**
     * Returns all titles keyed by name.
     *
     * @return array<string, string>
     */
    public function titles(): array
    {
        $titles = [];
        
        foreach($this->all() as $type) {
            $titles[$type->name()] = $type->title();
        }
        
        return $titles;
    }
    
    /**
     * Get iterator.
     *
     * @return Traversable<string, ResourceTypeInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
}