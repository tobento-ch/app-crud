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
use Tobento\App\Crud\Field\Formatter\Str;
use Tobento\App\Crud\Field;
use Tobento\Service\Support\HtmlString;

class StrTest extends TestCase
{
    public function testStringStr()
    {
        $str = new Str();
        
        $this->assertSame(
            'foo',
            $str(value: 'foo', field: new Field\Text(name: 'name'))
        );
    }
    
    public function testArrayStr()
    {
        $str = new Str();
        
        $this->assertSame(
            'foo, bar',
            $str(value: ['foo', 'bar'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testIntegerStr()
    {
        $str = new Str();
        
        $this->assertSame(
            '555',
            $str(value: 555, field: new Field\Text(name: 'name'))
        );
    }
    
    public function testHtmlStringValue()
    {
        $str = new Str();
        
        $this->assertSame(
            '<p>lorem</p>',
            $str(value: new HtmlString('<p>lorem</p>'), field: new Field\Text(name: 'name'))
        );
    }
    
    public function testSkipsInvalidStr()
    {
        $str = new Str();
        
        $this->assertSame(
            '',
            $str(value: new Field\Text(name: 'name'), field: new Field\Text(name: 'name'))
        );
    }

    public function testEmptyValue()
    {
        $str = new Str();
        
        $this->assertSame(
            '',
            $str(value: '', field: new Field\Text(name: 'name'))
        );
        
        $this->assertSame(
            '',
            $str(value: [], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testFieldWithOptions()
    {
        $str = new Str();
        
        $this->assertSame(
            'Blue',
            $str(value: 'blue', field: new Field\Select(name: 'name')->options(['blue' => 'Blue', 'red' => 'Red']))
        );
        
        $this->assertSame(
            'Blue, Red',
            $str(value: ['blue', 'red'], field: new Field\Select(name: 'name')->options(['blue' => 'Blue', 'red' => 'Red']))
        );
    }
    
    public function testDelimiter()
    {
        $str = new Str(delimiter: ':');
        
        $this->assertSame(
            'foo:bar',
            $str(value: ['foo', 'bar'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testTrimWidth()
    {
        $str = new Str(trimWidth: 10);
        
        $this->assertSame(
            'Lorem i...',
            $str(value: 'Lorem ipsum dolor sit amet', field: new Field\Text(name: 'name'))
        );
        
        $this->assertSame(
            'foo, ba...',
            $str(value: ['foo', 'bar', 'baz'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testTrimMarker()
    {
        $str = new Str(trimWidth: 10, trimMarker: '..');
        
        $this->assertSame(
            'Lorem ip..',
            $str(value: 'Lorem ipsum dolor sit amet', field: new Field\Text(name: 'name'))
        );
        
        $this->assertSame(
            'foo, bar..',
            $str(value: ['foo', 'bar', 'baz'], field: new Field\Text(name: 'name'))
        );
    }

    public function testArrayToJson()
    {
        $str = new Str(arrayToJson: true);

        $this->assertSame(
            json_encode(['foo' => 'bar'], JSON_PRETTY_PRINT),
            $str(value: ['foo' => 'bar'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testPreWithArrayToJson()
    {
        $str = new Str(arrayToJson: true, pre: true);

        $expectedJson = json_encode(['foo' => 'bar'], JSON_PRETTY_PRINT);
        $expected = '<pre>'.\Tobento\Service\Support\Str::esc($expectedJson).'</pre>';

        $this->assertSame(
            $expected,
            (string)$str(value: ['foo' => 'bar'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testPreWithArray()
    {
        $str = new Str(pre: true);

        $expected = '<pre>'.\Tobento\Service\Support\Str::esc('foo, bar').'</pre>';

        $this->assertSame(
            $expected,
            (string)$str(value: ['foo', 'bar'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testPreScalar()
    {
        $str = new Str(pre: true);

        $expected = '<pre>'.\Tobento\Service\Support\Str::esc('Hello').'</pre>';

        $this->assertSame(
            $expected,
            (string)$str(value: 'Hello', field: new Field\Text(name: 'name'))
        );
    }
}