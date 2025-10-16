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
     * @var null|callable(EntityInterface):bool|array<array-key, int|string>
     */
    private $undeletable = null;
    
    /**
     * @var callable|string
     */
    private $undeletableReason = '';
    
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
     * @param callable(EntityInterface):bool|array<array-key, int|string> $ids
     * @param callable|string $reason
     * @return static $this
     */
    public function undeletable(callable|array $ids, callable|string $reason = ''): static
    {
        $this->undeletable = $ids;
        $this->undeletableReason = $reason;
        return $this;
    }
    
    /**
     * Returns the undeletable reason.
     *
     * @param EntityInterface $entity
     * @return string
     */
    public function undeletableReason(EntityInterface $entity): string
    {
        if (is_string($this->undeletableReason)) {
            return $this->undeletableReason;
        }
        
        return ($this->undeletableReason)($entity);
    }
}