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

namespace Tobento\App\Crud\Test\Field\Formatter;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Formatter\Badge;
use Tobento\App\Crud\Field;

class BadgeTest extends TestCase
{
    public function testStringValue()
    {
        $badge = new Badge(['foo' => 'text-green']);
        
        $this->assertSame(
            '<span class="crud-badge text-green">foo</span>',
            (string)$badge(value: 'foo', field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testArrayValue()
    {
        $badge = new Badge(['foo' => 'text-green']);
        
        $this->assertSame(
            '<span class="crud-badges"><span class="crud-badge text-green">foo</span><span class="crud-badge text-black">bar</span></span>',
            (string)$badge(value: ['foo', 'bar'], field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testIntegerValue()
    {
        $badge = new Badge([555 => 'text-green']);
        
        $this->assertSame(
            '<span class="crud-badge text-green">555</span>',
            (string)$badge(value: 555, field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testSkipsInvalidValue()
    {
        $badge = new Badge();
        
        $this->assertSame(
            '<span class="crud-badges"></span>',
            (string)$badge(value: [[]], field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testEmptyValue()
    {
        $badge = new Badge();
        
        $this->assertSame(
            '',
            (string)$badge(value: '', field: Field\Text::new(name: 'name'))
        );
        
        $this->assertSame(
            '',
            (string)$badge(value: [], field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testValueIsEscaped()
    {
        $badge = new Badge();
        
        $this->assertSame(
            '<span class="crud-badge text-black">&lt;p&gt;foo&lt;/p&gt;</span>',
            (string)$badge(value: '<p>foo</p>', field: Field\Text::new(name: 'name'))
        );
        
        $this->assertSame(
            '<span class="crud-badges"><span class="crud-badge text-black">&lt;p&gt;foo&lt;/p&gt;</span></span>',
            (string)$badge(value: ['<p>foo</p>'], field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testFieldWithOptions()
    {
        $badge = new Badge(['blue' => 'text-blue']);
        
        $this->assertSame(
            '<span class="crud-badge text-blue">Blue</span>',
            (string)$badge(value: 'blue', field: Field\Select::new(name: 'name')->options(['blue' => 'Blue', 'red' => 'Red']))
        );
        
        $this->assertSame(
            '<span class="crud-badges"><span class="crud-badge text-blue">Blue</span></span>',
            (string)$badge(value: ['blue'], field: Field\Select::new(name: 'name')->options(['blue' => 'Blue', 'red' => 'Red']))
        );
    }
    
    public function testAppliesDefaultClassIfNotExists()
    {
        $badge = new Badge();
        
        $this->assertSame(
            '<span class="crud-badge text-black">foo</span>',
            (string)$badge(value: 'foo', field: Field\Text::new(name: 'name'))
        );
        
        $this->assertSame(
            '<span class="crud-badges"><span class="crud-badge text-black">foo</span></span>',
            (string)$badge(value: ['foo'], field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testAppliesCustomClassIfNotExists()
    {
        $badge = new Badge(fallbackClass: 'text-foo');
        
        $this->assertSame(
            '<span class="crud-badge text-foo">foo</span>',
            (string)$badge(value: 'foo', field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testLimitValues()
    {
        $badge = new Badge(limit: 1);
        
        $this->assertSame(
            '<span class="crud-badges"><span class="crud-badge text-black">a</span><span>...</span></span>',
            (string)$badge(value: ['a', 'b', 'c'], field: Field\Text::new(name: 'name'))
        );
    }
}