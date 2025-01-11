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

namespace Tobento\App\Crud\Test\Action;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Action\Actions;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;

class ActionsTest extends TestCase
{
    public function testConstructorMethod()
    {
        $actions = new Actions();
        $this->assertInstanceof(ActionsInterface::class, $actions);
        
        $actions = new Actions(Action\Create::new());
        $this->assertFalse($actions->empty());
    }
    
    public function testFilterMethod()
    {
        $actions = new Actions(Action\Create::new(), Action\Edit::new());
        $filtered = $actions->filter(fn(ActionInterface $a): bool => $a->name() === 'create');
        
        $this->assertFalse($actions === $filtered);
        $this->assertSame(2, $actions->count());
        $this->assertSame(1, $filtered->count());
    }
    
    public function testBulksMethod()
    {
        $actions = new Actions(Action\Create::new(), Action\BulkEdit::new(name: 'foo'));
        $actionsNew = $actions->bulks();
        
        $this->assertFalse($actions === $actionsNew);
        $this->assertSame(2, $actions->count());
        $this->assertSame(1, $actionsNew->count());
    }
    
    public function testFirstMethod()
    {
        $actions = new Actions();
        $this->assertSame(null, $actions->first());
        
        $actions = new Actions(Action\Create::new(), Action\Edit::new());
        $this->assertSame('create', $actions->first()->name());
    }
    
    public function testGetMethod()
    {
        $actions = new Actions();
        $this->assertSame(null, $actions->get(name: 'create'));
        
        $actions = new Actions(Action\Create::new(), Action\Edit::new());
        $this->assertSame('create', $actions->get(name: 'create')->name());
    }
    
    public function testAllMethod()
    {
        $actions = new Actions();
        $this->assertSame([], $actions->all());
        
        $create = Action\Create::new();
        $actions = new Actions($create);
        $this->assertSame([$create], $actions->all());
    }
    
    public function testEmptyMethod()
    {
        $actions = new Actions();
        $this->assertTrue($actions->empty());
        
        $actions = new Actions(Action\Create::new());
        $this->assertFalse($actions->empty());
    }
    
    public function testCountMethod()
    {
        $actions = new Actions();
        $this->assertSame(0, $actions->count());
        
        $actions = new Actions(Action\Create::new(), Action\Edit::new());
        $this->assertSame(2, $actions->count());
    }
    
    public function testIteration()
    {
        $actions = new Actions(Action\Create::new(), Action\Edit::new());
        
        foreach($actions as $action) {
            $this->assertInstanceof(ActionInterface::class, $action);
        }
    }
}