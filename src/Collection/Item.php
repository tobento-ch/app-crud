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
 
namespace Tobento\App\Crud\Collection;

use Countable;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Support\Arrayable;

class Item implements Arrayable, Countable
{
    /**
     * Create a new Item instance.
     *
     * @param array<array-key, mixed> $attributes
     * @param string $locale
     * @param array<string, string> $localeFallbacks
     */
    final public function __construct(
        protected readonly array $attributes,
        protected readonly string $locale = 'en',
        protected readonly array $localeFallbacks = [],
    ) {}

    /**
     * Returns the locale.
     *
     * @return string
     */
    public function locale(): string
    {
        return $this->locale;
    }
    
    /**
     * Returns a new instance with the given locale.
     *
     * @param string $locale
     * @return static
     */
    public function withLocale(string $locale): static
    {
        return new static($this->attributes, $locale, $this->localeFallbacks);
    }
    
    /**
     * Returns the locale fallbacks.
     *
     * @return array<string, string>
     */
    public function localeFallbacks(): array
    {
        return $this->localeFallbacks;
    }
    
    /**
     * Returns a new instance with the given locale fallbacks.
     *
     * @param array<string, string> $fallbacks
     * @return static
     */
    public function withLocaleFallbacks(array $fallbacks): static
    {
        return new static($this->attributes, $this->locale, $fallbacks);
    }
    
    /**
     * Returns an attribute value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if (Arr::has($this->attributes, $name.'.'.$this->locale)) {
            $value = Arr::get($this->attributes, $name.'.'.$this->locale);
            if (!empty($value)) {
                return $this->ensureType($value, $default);
            }
        }
        
        $locale = $this->localeFallbacks[$this->locale] ?? $this->locale;
        
        if ($this->locale !== $locale) {
            if (Arr::has($this->attributes, $name.'.'.$locale)) {
                return $this->ensureType(Arr::get($this->attributes, $name.'.'.$locale), $default);
            }
        }

        return $this->ensureType(Arr::get($this->attributes, $name, $default), $default);
    }
    
    /**
     * Returns true if has attribute, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string|int $name): bool
    {
        if (Arr::has($this->attributes, $name.'.'.$this->locale)) {
            return true;
        }
        
        return Arr::has($this->attributes, $name);
    }
    
    /**
     * Returns an attribute value by name and locale.
     *
     * @param string $name
     * @param mixed $default
     * @param null|string $locale
     * @return mixed
     */
    public function raw(string $name, mixed $default = null, null|string $locale = null): mixed
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
     * Returns true if has attribute, otherwise false.
     *
     * @param string $name
     * @param string $locale
     * @return bool
     */
    public function hasRaw(string|int $name, null|string $locale = null): bool
    {
        if (!is_null($locale)) {
            return Arr::has($this->attributes, $name.'.'.$locale);
        }
        
        return Arr::has($this->attributes, $name);
    }
    
    /**
     * Returns the number of items.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->attributes);
    }
    
    /**
     * Returns all files.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->attributes;
    }
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
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
}