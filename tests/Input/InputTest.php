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

namespace Tobento\App\Crud\Test\Input;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Crud\Input\Input;

class InputTest extends TestCase
{
    public function testHasMethod()
    {
        $input = new Input(['foo' => 'Foo', 'options' => ['color' => 'blue'], 5 => '5']);
        
        $this->assertTrue($input->has('foo'));
        $this->assertTrue($input->has('options.color'));
        $this->assertTrue($input->has(5));
        $this->assertFalse($input->has('bar'));
        $this->assertFalse($input->has('options.meta'));
    }
    
    public function testGetMethod()
    {
        $input = new Input(['foo' => 'Foo', 'options' => ['color' => 'blue'], 5 => '5']);
        
        $this->assertSame('Foo', $input->get('foo'));
        $this->assertSame(null, $input->get('bar'));
        $this->assertSame('5', $input->get(5));
        $this->assertSame('blue', $input->get('options.color'));
        $this->assertSame(null, $input->get('options.meta'));
    }
    
    public function testGetMethodWithDefaultValue()
    {
        $input = new Input(['foo' => 'Foo', 'options' => ['color' => 'blue']]);
        
        $this->assertSame('Foo', $input->get('foo', 'default'));
        $this->assertSame('default', $input->get('bar', 'default'));
        $this->assertSame('blue', $input->get('options.color', 'default'));
        $this->assertSame('default', $input->get('options.meta', 'default'));
    }
    
    public function testGetMethodWithDefaultValueEnsuresType()
    {
        $input = new Input(['foo' => 'Foo', 'options' => ['color' => 'blue']]);
        
        $this->assertSame([], $input->get('foo', []));
        $this->assertSame(45, $input->get('foo', 45));
    }
    
    public function testSetMethod()
    {
        $input = new Input(['foo' => 'Foo', 'options' => ['color' => 'blue']]);
        $input->set('foo', 'FOO');
        $input->set('options.color', 'BLUE');
        $input->set('options.meta', 'BAZ');
        $input->set(5, '5');
        
        $this->assertSame('FOO', $input->get('foo'));
        $this->assertSame('BLUE', $input->get('options.color'));
        $this->assertSame('BAZ', $input->get('options.meta'));
        $this->assertSame('5', $input->get(5));
    }
    
    public function testDeleteMethod()
    {
        $input = new Input(['foo' => 'Foo', 'options' => ['color' => 'blue'], 5 => '5']);
        
        $this->assertTrue($input->has('foo'));
        $this->assertTrue($input->has('options.color'));
        $this->assertTrue($input->has(5));
        
        $input->delete('foo');
        $input->delete('options.color');
        $input->delete(5);
        $input->delete('options.meta');
        
        $this->assertFalse($input->has('foo'));
        $this->assertFalse($input->has('options.color'));
        $this->assertFalse($input->has(5));
    }
    
    public function testAllMethod()
    {
        $data = ['foo' => 'Foo', 'options' => ['color' => 'blue'], 5 => '5'];
        
        $input = new Input($data);
        
        $this->assertSame($data, $input->all());
    }
    
    public function testCollectionMethod()
    {
        $data = ['foo' => 'Foo', 'options' => ['color' => 'blue'], 5 => '5'];
        
        $input = new Input($data);
        
        $this->assertSame($data, $input->collection()->all());
    }
}