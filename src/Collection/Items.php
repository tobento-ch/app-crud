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
use Generator;
use IteratorAggregate;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Support\Arrayable;

/**
 * @implements IteratorAggregate<array-key, Item>
 */
class Items implements IteratorAggregate, Arrayable, Countable
{
    /**
     * Create a new Items instance.
     *
     * @param array<array-key, array<array-key, mixed>> $items
     * @param string $locale
     * @param null|string $fallbackLocale
     */
    public function __construct(
        protected readonly array $items = [],
        protected readonly string $locale = 'en',
        protected readonly null|string $fallbackLocale = null,
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
        return new static($this->items, $locale, $this->fallbackLocale);
    }
    
    /**
     * Returns the fallback locale.
     *
     * @return null|string
     */
    public function fallbackLocale(): null|string
    {
        return $this->fallbackLocale;
    }
    
    /**
     * Returns a new instance with the given fallback locale.
     *
     * @param null|string $locale
     * @return static
     */
    public function withFallbackLocale(null|string $locale): static
    {
        return new static($this->items, $this->locale, $locale);
    }
    
    /**
     * Returns the number of items.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
    
    /**
     * Returns all items.
     *
     * @return array
     */
    public function all(): array
    {
        return Iter::toArray(iterable: $this->getIterator());
    }
    
    /**
     * Returns an iterator for the items.
     *
     * @return Generator<array-key, Item>
     */
    public function getIterator(): Generator
    {
        foreach($this->items as $key => $value) {
            if ($value instanceof Item) {
                yield $key => $value;
            } else {
                if (!is_array($value)) {
                    $value = [$value];
                }

                yield $key => new Item(attributes: $value, locale: $this->locale(), fallbackLocale: $this->fallbackLocale());                
            }
        }
    }
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }
}