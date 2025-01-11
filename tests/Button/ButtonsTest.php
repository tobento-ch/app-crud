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

namespace Tobento\App\Crud\Test\Button;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\Button;
use Tobento\App\Crud\Button\Link;

class ButtonsTest extends TestCase
{
    public function testConstructorMethod()
    {
        $buttons = new Buttons();
        $this->assertInstanceof(ButtonsInterface::class, $buttons);
        
        $buttons = new Buttons(Button::new(label: 'label', group: 'group'));
        $this->assertTrue($buttons->has());
    }
    
    public function testGetMethod()
    {
        $buttons = new Buttons();
        $this->assertSame(null, $buttons->get(name: 'create'));
        
        $buttons = new Buttons(
            Button::new(label: 'create', group: 'group'),
        );
        $this->assertSame('create', $buttons->get(name: 'create')->getName());
    }
    
    public function testAddMethod()
    {
        $buttons = new Buttons();
        
        $buttons->add(
            Button::new(label: 'create', group: 'group'),
            Button::new(label: 'edit', group: 'group'),
        );
        
        $this->assertSame(2, $buttons->count());
        $this->assertSame('create', $buttons->get(name: 'create')->getName());
    }
    
    public function testRemoveMethod()
    {
        $buttons = new Buttons(
            Button::new(label: 'create', group: 'group'),
            Button::new(label: 'edit', group: 'group'),
            Button::new(label: 'new', group: 'group'),
        );
        
        $buttons->remove('create', 'new');
        
        $this->assertNull($buttons->get(name: 'create'));
        $this->assertNotNull($buttons->get(name: 'edit'));
        $this->assertNull($buttons->get(name: 'new'));
    }
    
    public function testReorderMethod()
    {
        $buttons = new Buttons(
            Button::new(label: 'create', group: 'group'),
            Button::new(label: 'edit', group: 'group'),
            Button::new(label: 'new', group: 'group'),
        );
        
        $newButtons = $buttons->reorder('new', 'edit');
        $this->assertFalse($buttons === $newButtons);
        $this->assertSame(['new', 'edit', 'create'], $newButtons->names());
        
        $buttons = $buttons->reorder('edit', 'unknown');
        $this->assertSame(['edit', 'create', 'new'], $buttons->names());
    }
    
    public function testFilterMethod()
    {
        $buttons = new Buttons(
            Button::new(label: 'create', group: 'group'),
            Button::new(label: 'edit', group: 'group'),
        );
        
        $filtered = $buttons->filter(fn(ButtonInterface $b): bool => $b->getName() === 'create');
        
        $this->assertFalse($buttons === $filtered);
        $this->assertSame(2, $buttons->count());
        $this->assertSame(1, $filtered->count());
    }
    
    public function testGroupMethod()
    {
        $buttons = new Buttons(
            Button::new(label: 'create', group: 'foo'),
            Button::new(label: 'edit', group: 'bar'),
            Button::new(label: 'new', group: 'foo'),
        );
        $buttonsNew = $buttons->group('foo');
        
        $this->assertFalse($buttons === $buttonsNew);
        $this->assertSame(3, $buttons->count());
        $this->assertSame(2, $buttonsNew->count());
    }

    public function testFirstMethod()
    {
        $buttons = new Buttons();
        $this->assertSame(null, $buttons->first());
        
        $create = Button::new(label: 'create', group: 'foo');
        $buttons = new Buttons($create);
        $this->assertSame($create, $buttons->first());
    }
    
    public function testAllMethod()
    {
        $buttons = new Buttons();
        $this->assertSame([], $buttons->all());
        
        $create = Button::new(label: 'create', group: 'foo');
        $buttons = new Buttons($create);
        $this->assertSame(['create' => $create], $buttons->all());
    }
    
    public function testNamesMethod()
    {
        $buttons = new Buttons(
            Button::new(label: 'create', group: 'group'),
            Button::new(label: 'edit', group: 'group'),
        );
        
        $this->assertSame(['create', 'edit'], $buttons->names());
    }
    
    public function testHasMethod()
    {
        $buttons = new Buttons();
        $this->assertFalse($buttons->has());
        
        $buttons = new Buttons(Button::new(label: 'create', group: 'group'));
        $this->assertTrue($buttons->has());
    }
    
    public function testCountMethod()
    {
        $buttons = new Buttons();
        $this->assertSame(0, $buttons->count());
        
        $buttons = new Buttons(Button::new(label: 'create', group: 'group'));
        $this->assertSame(1, $buttons->count());
    }
    
    public function testIteration()
    {
        $buttons = new Buttons(Button::new(label: 'create', group: 'group'));
        
        foreach($buttons as $button) {
            $this->assertInstanceof(ButtonInterface::class, $button);
        }
    }
}