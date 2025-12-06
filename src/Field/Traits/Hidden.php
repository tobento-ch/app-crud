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

namespace Tobento\App\Crud\Field\Traits;

use Tobento\App\Crud\Field\FieldInterface;

trait Hidden
{
    /**
     * @var bool
     */
    protected bool $hidden = false;
    
    /**
     * Sets whether the field is hidden.
     *
     * @param bool|callable $hidden
     * @param null|string $action
     * @return static $this
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function hidden(bool|callable $hidden = true, null|string $action = null): static
    {
        if (is_null($action) && is_bool($hidden)) {
            $this->hidden = $hidden;
            return $this;
        }
        
        if (is_bool($hidden)) {
            $this->resolve(static function (FieldInterface $field) use ($hidden) {
                $field->hidden($hidden, null);
            }, action: $action);
            
            return $this;
        }
        
        $this->resolve(
            resolve: $hidden,
            resolved: static function(FieldInterface $field, mixed $resolved): void {
                if (is_bool($resolved)) {
                    $field->hidden($resolved);
                }
            },
        );
        
        return $this;
    }
    
    /**
     * Returns whether the field is hidden or not.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }
}