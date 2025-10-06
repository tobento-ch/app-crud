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
use Tobento\App\Crud\Collection\Items;

class ItemsTest extends TestCase
{
    public function testLocaleMethods()
    {
        $items = new Items(items: []);
        $itemsNew = $items->withLocale('de');
        
        $this->assertFalse($items === $itemsNew);
        $this->assertSame('en', $items->locale());
        $this->assertSame('de', $itemsNew->locale());
        
        $items = new Items(items: ['key' => 'value'], locale: 'fr');
        
        $this->assertSame('fr', $items->locale());
    }
    
    public function testLocaleFallbacksMethods()
    {
        $items = new Items(items: []);
        $itemsNew = $items->withLocaleFallbacks(['en' => 'de']);
        
        $this->assertFalse($items === $itemsNew);
        $this->assertSame([], $items->localeFallbacks());
        $this->assertSame(['en' => 'de'], $itemsNew->localeFallbacks());
        
        $items = new Items(items: [], localeFallbacks: ['en' => 'de']);
        
        $this->assertSame(['en' => 'de'], $items->localeFallbacks());
        $this->assertSame([], $items->withLocaleFallbacks([])->localeFallbacks());
    }
    
    public function testCountMethod()
    {
        $this->assertSame(0, new Items([])->count());
        $this->assertSame(2, new Items([['foo' => 'Foo'], ['bar' => 'Bar']])->count());
    }
    
    public function testAllMethod()
    {
        $items = new Items([['foo' => 'Foo'], ['bar' => 'Bar']]);
        
        $this->assertInstanceof(Item::class, $items->all()[0]);
    }
    
    public function testLocalesArePassedToItem()
    {
        $items = new Items(items: [['foo' => 'Foo']], locale: 'fr', localeFallbacks: ['es' => 'en']);
        
        $this->assertSame('fr', $items->all()[0]->locale());
        $this->assertSame(['es' => 'en'], $items->all()[0]->localeFallbacks());
    }

    public function testConstructMethodWithPassingItem()
    {
        $item = new Item(['foo' => 'Foo']);
        $items = new Items(items: [$item]);
        
        $this->assertTrue($item === $items->all()[0]);
    }
    
    public function testToArrayMethod()
    {
        $items = new Items([['foo' => 'Foo'], ['bar' => 'Bar']]);
        
        $this->assertSame(
            [['foo' => 'Foo'], ['bar' => 'Bar']],
            $items->toArray()
        );
    }
}