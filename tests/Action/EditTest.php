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

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

class EditTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\Edit::new());
        $this->linkUrlTests(Action\Edit::new());
        $this->linkToTests(Action\Edit::new());
        $this->viewTests(Action\Edit::new());
        $this->localeTests(Action\Edit::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\Edit::new());
        $this->entitiesTests(Action\Edit::new());
        $this->entityTests(Action\Edit::new());
        $this->controllerTests(Action\Edit::new());
        $this->actionsTests(Action\Edit::new());
        $this->inputTests(Action\Edit::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Edit::new(title: 'title');
        $this->assertInstanceof(Action\Edit::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('edit', $action->name());
        $this->assertSame('{name}.edit', $action->getRoute()[0] ?? null);
        $this->assertSame('crud/edit', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('update', $action->getLinkToAction());
        $this->assertSame(['cancel', 'save', 'close', 'copy', 'new'], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Edit', Action\Edit::new()->title());
        $this->assertSame('Foo', Action\Edit::new(title: 'Foo')->title());        
        $this->assertSame('Foo', Action\Edit::new(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}