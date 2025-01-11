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

namespace Tobento\App\Crud\Entity;

use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Collection\Collection;

/**
 * Entity
 */
class Entity implements EntityInterface
{
    /**
     * @var null|FieldsInterface
     */
    protected null|FieldsInterface $fields = null;
    
    /**
     * @var null|ButtonsInterface
     */
    protected null|ButtonsInterface $buttons = null;
    
    /**
     * Create a new Entity.
     *
     * @param array $attributes
     * @param string $idAttributeName
     */
    public function __construct(
        protected array $attributes = [],
        protected string $idAttributeName = 'id',
    ) {}

    /**
     * Returns the id.
     *
     * @return int|string
     */
    public function id(): int|string
    {
        $id = $this->get($this->idAttributeName);
        return $id ?: 0;
    }
    
    /**
     * Returns an attribute value by name and locale.
     *
     * @param string $name
     * @param mixed $default
     * @param null|string $locale
     * @return mixed
     */
    public function get(string $name, mixed $default = null, null|string $locale = null): mixed
    {
        if ($locale) {
            if (Arr::has($this->attributes, $name.'.'.$locale)) {
                return $this->ensureType(Arr::get($this->attributes, $name.'.'.$locale), $default);
            }

            return $this->ensureType(Arr::get($this->attributes, $name, $default), $default);
        }
        
        return $this->ensureType(Arr::get($this->attributes, $name, $default), $default);
    }

    /**
     * Returns true if entity has the given attribute, otherwise false.
     *
     * @param string|int $name
     * @return bool
     */
    public function has(string|int $name): bool
    {
        return Arr::has($this->attributes, $name);
    }
    
    /**
     * Delete an attribute.
     *
     * @param string|int $name
     * @return static $this
     */
    public function delete(string|int $name): static
    {
        $this->attributes = Arr::delete($this->attributes, $name);
        return $this;
    }
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return (new Collection($this->attributes))->toArray();
    }
    
    /**
     * Sets the fields.
     *
     * @param FieldsInterface $fields
     * @return static $this
     */
    public function setFields(FieldsInterface $fields): static
    {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * Returns the fields.
     *
     * @return FieldsInterface
     */
    public function fields(): FieldsInterface
    {
        if (is_null($this->fields)) {
            return new Fields();
        }
        
        return $this->fields;
    }
    
    /**
     * Sets the buttons.
     *
     * @param ButtonsInterface $buttons
     * @return static $this
     */
    public function setButtons(ButtonsInterface $buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function buttons(): ButtonsInterface
    {
        if (is_null($this->buttons)) {
            return new Buttons();
        }
        
        return $this->buttons;
    }
    
    /**
     * If default value is set. It ensure the type of it.
     *
     * @param mixed The data
     * @param mixed The default value
     * @return mixed The ensured value
     */
    protected function ensureType(mixed $data, mixed $default): mixed
    {
        // do not ensure the type if default is null.
        if ($default === null) {
            return $data;
        }
        
        // verify type based on the default. If data is not the same just return $default.
        $defaultType = gettype($default);
        $dataType = gettype($data);
        
        if ($defaultType !== $dataType) {
            // int, float are valid types.
            $types = ['double' => ['integer'], 'integer' => ['double']];
            
            if (
                isset($types[$defaultType])
                && in_array($dataType, $types[$defaultType]))
            {
                return $data;
            }
            
            if ($defaultType === 'string') {
                $types = ['int', 'double', 'integer'];

                if (in_array($dataType, $types)) {
                    return (string) $data;
                }
            }
            
            return $default;
        }
        
        return $data;
    }
    
    /**
     * __get For array_column object support
     */
    public function __get(string $prop)
    {
        return Arr::get($this->attributes, $prop);
    }

    /**
     * __isset For array_column object support
     */
    public function __isset(string $prop): bool
    {
        return Arr::has($this->attributes, $prop);
    }
}