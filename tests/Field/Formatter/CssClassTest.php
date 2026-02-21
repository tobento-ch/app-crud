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
use Tobento\App\Crud\Field\Formatter\CssClass;
use Tobento\App\Crud\Field;
use Tobento\Service\Support\HtmlString;

class CssClassTest extends TestCase
{
    public function testStringValue()
    {
        $class = new CssClass('text-700 float-right');
        
        $this->assertSame(
            '<span class="text-700 float-right">foo</span>',
            (string)$class(value: 'foo', field: new Field\Text(name: 'name'))
        );
    }
    
    public function testArrayValue()
    {
        $class = new CssClass('text-700');
        
        $this->assertSame(
            '<span class="crud-values"><span class="text-700">foo</span><span class="text-700">bar</span></span>',
            (string)$class(value: ['foo', 'bar'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testIntegerValue()
    {
        $class = new CssClass('text-700');
        
        $this->assertSame(
            '<span class="text-700">555</span>',
            (string)$class(value: 555, field: new Field\Text(name: 'name'))
        );
    }
    
    public function testHtmlStringValue()
    {
        $class = new CssClass('text-700');
        
        $this->assertSame(
            '<span class="text-700"><p>lorem</p></span>',
            (string)$class(value: new HtmlString('<p>lorem</p>'), field: new Field\Text(name: 'name'))
        );
    }
    
    public function testSkipsInvalidValue()
    {
        $class = new CssClass('text-700');
        
        $this->assertSame(
            '<span class="crud-values"></span>',
            (string)$class(value: [[]], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testEmptyClass()
    {
        $class = new CssClass('');
        
        $this->assertSame(
            '<span>foo</span>',
            (string)$class(value: 'foo', field: new Field\Text(name: 'name'))
        );
        
        $this->assertSame(
            '<span class="crud-values"><span>foo</span><span>bar</span></span>',
            (string)$class(value: ['foo', 'bar'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testEmptyValue()
    {
        $class = new CssClass('text-700');
        
        $this->assertSame(
            '',
            (string)$class(value: '', field: new Field\Text(name: 'name'))
        );
        
        $this->assertSame(
            '',
            (string)$class(value: [], field: new Field\Text(name: 'name'))
        );
    }    
    
    public function testValueIsEscaped()
    {
        $class = new CssClass('text-700');
        
        $this->assertSame(
            '<span class="text-700">&lt;p&gt;foo&lt;/p&gt;</span>',
            (string)$class(value: '<p>foo</p>', field: new Field\Text(name: 'name'))
        );
        
        $this->assertSame(
            '<span class="crud-values"><span class="text-700">&lt;p&gt;foo&lt;/p&gt;</span></span>',
            (string)$class(value: ['<p>foo</p>'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testFieldWithOptions()
    {
        $class = new CssClass('text-700');
        
        $this->assertSame(
            '<span class="text-700">Blue</span>',
            (string)$class(value: 'blue', field: new Field\Select(name: 'name')->options(['blue' => 'Blue', 'red' => 'Red']))
        );
        
        $this->assertSame(
            '<span class="crud-values"><span class="text-700">Blue</span></span>',
            (string)$class(value: ['blue'], field: new Field\Select(name: 'name')->options(['blue' => 'Blue', 'red' => 'Red']))
        );
    }
}