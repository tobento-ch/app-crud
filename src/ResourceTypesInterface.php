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

use IteratorAggregate;
use Tobento\App\Crud\Exception\ResourceTypeNotFoundException;

interface ResourceTypesInterface extends IteratorAggregate
{
    /**
     * Adds a resource type.
     *
     * @param ResourceTypeInterface $type
     * @return static $this
     */
    public function add(ResourceTypeInterface $type): static;
    
    /**
     * Returns true if type exists, otherwise false.
     *
     * @param string $type
     * @return bool
     */
    public function has(string $type): bool;
    
    /**
     * Returns the resource type.
     *
     * @param string $type
     * @return ResourceTypeInterface
     * @throws ResourceTypeNotFoundException
     */
    public function get(string $type): ResourceTypeInterface;
    
    /**
     * Returns all types.
     *
     * @return array<string, ResourceTypeInterface>
     */
    public function all(): array;

    /**
     * Returns all names.
     *
     * @return array<int, string>
     */
    public function names(): array;
    
    /**
     * Returns all titles keyed by name.
     *
     * @return array<string, string>
     */
    public function titles(): array;
}