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
        $this->urlTests(new Action\Edit());
        $this->linkUrlTests(new Action\Edit());
        $this->linkToTests(new Action\Edit());
        $this->viewTests(new Action\Edit());
        $this->localeTests(new Action\Edit());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\Edit());
        $this->entitiesTests(new Action\Edit());
        $this->entityTests(new Action\Edit());
        $this->controllerTests(new Action\Edit());
        $this->actionsTests(new Action\Edit());
        $this->inputTests(new Action\Edit());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Edit(title: 'title');
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
        $this->assertSame('Edit', new Action\Edit()->title());
        $this->assertSame('Foo', new Action\Edit(title: 'Foo')->title());        
        $this->assertSame('Foo', new Action\Edit(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}