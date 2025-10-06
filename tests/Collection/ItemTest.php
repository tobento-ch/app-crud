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

namespace Tobento\App\Crud\Test\Collection;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Collection\Item;

class ItemTest extends TestCase
{
    public function testLocaleMethods()
    {
        $item = new Item(attributes: ['key' => 'value']);
        $itemNew = $item->withLocale('de');
        
        $this->assertFalse($item === $itemNew);
        $this->assertSame('en', $item->locale());
        $this->assertSame('de', $itemNew->locale());
        
        $item = new Item(attributes: ['key' => 'value'], locale: 'fr');
        
        $this->assertSame('fr', $item->locale());
    }
    
    public function testLocaleFallbacksMethods()
    {
        $item = new Item(attributes: ['key' => 'value']);
        $itemNew = $item->withLocaleFallbacks(['en' => 'de']);
        
        $this->assertFalse($item === $itemNew);
        $this->assertSame([], $item->localeFallbacks());
        $this->assertSame(['en' => 'de'], $itemNew->localeFallbacks());
        
        $item = new Item(attributes: ['key' => 'value'], localeFallbacks: ['en' => 'de']);
        
        $this->assertSame(['en' => 'de'], $item->localeFallbacks());
        $this->assertSame([], $item->withLocaleFallbacks([])->localeFallbacks());
    }
    
    public function testGetMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame('value', $item->get('key'));
        $this->assertSame(null, $item->get('foo'));
        $this->assertSame('default', $item->get('foo', 'default'));
        $this->assertSame([], $item->get('foo', []));
    }
    
    public function testGetMethodWithTranslations()
    {
        $item = new Item(['name' => ['en' => 'Foo']]);
        
        $this->assertSame('Foo', $item->get('name'));
        $this->assertSame(['en' => 'Foo'], $item->withLocale('de')->get('name'));
        $this->assertSame('default', $item->withLocale('de')->get('name', 'default'));
        $this->assertSame('Foo', $item->withLocale('de')->withLocaleFallbacks(['de' => 'en'])->get('name'));
        $this->assertSame(null, $item->get('foo'));
    }
    
    public function testGetMethodIfEmptyTranslationFallbackShouldBeUsed()
    {
        $item = new Item(attributes: ['name' => ['en' => 'Foo', 'de' => '']], locale: 'de', localeFallbacks: ['de' => 'en']);
        
        $this->assertSame('Foo', $item->get('name'));
    }
    
    public function testHasMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertTrue($item->has('key'));
        $this->assertFalse($item->has('foo'));
    }
    
    public function testHasMethodWithTranslations()
    {
        $item = new Item(['name' => ['en' => 'Foo']]);
        
        $this->assertTrue($item->has('name'));
        $this->assertTrue($item->withLocale('de')->has('name'));
        $this->assertTrue($item->withLocale('de')->withLocaleFallbacks(['de' => 'en'])->has('name'));
        $this->assertFalse($item->has('foo'));
    }
    
    public function testRawMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame('value', $item->raw('key'));
        $this->assertSame(null, $item->raw('foo'));
        $this->assertSame(null, $item->raw(name: 'foo', locale: 'en'));
        $this->assertSame('default', $item->raw('foo', 'default'));
        $this->assertSame('default', $item->raw('foo', 'default', 'en'));
    }
    
    public function testRawMethodWithTranslations()
    {
        $item = new Item(['name' => ['en' => 'Foo']]);
        
        $this->assertSame(['en' => 'Foo'], $item->raw('name'));
        $this->assertSame('default', $item->raw('name', 'default'));
        $this->assertSame(['en' => 'Foo'], $item->raw('name', []));
        $this->assertSame('Foo', $item->raw(name: 'name', default: 'default', locale: 'en'));
        $this->assertSame('default', $item->raw(name: 'name', default: 'default', locale: 'fr'));
        $this->assertSame(null, $item->raw(name: 'foo'));
        $this->assertSame('default', $item->raw(name: 'name', default: 'default'));
        $this->assertSame(null, $item->raw(name: 'foo', locale: 'en'));
    }
    
    public function testHasRawMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertTrue($item->hasRaw('key'));
        $this->assertFalse($item->hasRaw('foo'));
    }
    
    public function testHasRawMethodWithTranslations()
    {
        $item = new Item(['name' => ['en' => 'Foo']]);
        
        $this->assertTrue($item->hasRaw('name'));
        $this->assertTrue($item->hasRaw('name', 'en'));
        $this->assertFalse($item->hasRaw('name', 'fr'));
        $this->assertFalse($item->hasRaw('foo'));
        $this->assertFalse($item->hasRaw('foo', 'fr'));
    }
    
    public function testCountMethod()
    {
        $this->assertSame(0, new Item([])->count());
        $this->assertSame(2, new Item(['key' => 'value', 'foo' => 'Foo'])->count());
    }
    
    public function testAllMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame(['key' => 'value'], $item->all());
    }
    
    public function testToArrayMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame(
            ['key' => 'value'],
            $item->toArray()
        );
    }
}