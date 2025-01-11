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

namespace Tobento\App\Crud\Action;

use Tobento\App\Crud\Entity\EntityInterface;
use Closure;

/**
 * Delete
 */
final class Delete extends AbstractAction
{
    /**
     * @var null|callable|array<array-key, int|string>
     */
    private $undeletable = null;
    
    /**
     * Create a new Delete.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        null|string|Closure $title = null,
    ) {
        $this->title = $title;
        $this->route('{name}.delete', function(EntityInterface $entity): array {
            return ['id' => $entity->id()];
        });
        
        $this->linkToAction('index');
    }
    
    /**
     * Create a new instance.
     *
     * @param null|string|Closure $title
     * @return static
     */
    public static function new(null|string|Closure $title = null): static
    {
        return new static($title);
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'delete';
    }

    /**
     * Returns whether the entity is deletable.
     *
     * @param EntityInterface $entity
     * @return bool
     */
    public function isDeletable(EntityInterface $entity): bool
    {
        if (is_null($this->undeletable)) {
            return true;
        }
        
        if (is_array($this->undeletable)) {
            return in_array($entity->id(), $this->undeletable) ? false : true;
        }
        
        return call_user_func($this->undeletable, $entity) === true ? false : true;
    }
    
    /**
     * Sets the undeletable ids or using a callback returning whether the entity is undeletable.
     *
     * @param callable|array<array-key, int|string> $ids
     * @return static $this
     */
    public function undeletable(callable|array $ids): static
    {
        $this->undeletable = $ids;
        return $this;
    }
}