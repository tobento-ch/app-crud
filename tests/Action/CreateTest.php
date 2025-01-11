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

class CreateTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\Create::new());
        $this->linkUrlTests(Action\Create::new());
        $this->linkToTests(Action\Create::new());
        $this->viewTests(Action\Create::new());
        $this->localeTests(Action\Create::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\Create::new());
        $this->entitiesTests(Action\Create::new());
        $this->entityTests(Action\Create::new());
        $this->controllerTests(Action\Create::new());
        $this->actionsTests(Action\Create::new());
        $this->inputTests(Action\Create::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Create::new(title: 'title');
        $this->assertInstanceof(Action\Create::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('create', $action->name());
        $this->assertSame(['{name}.create', []], $action->getRoute());
        $this->assertSame('crud/create', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('store', $action->getLinkToAction());
        $this->assertSame(['cancel', 'save', 'close', 'copy', 'new'], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Create', Action\Create::new()->title());
        $this->assertSame('Foo', Action\Create::new(title: 'Foo')->title());        
        $this->assertSame('Foo', Action\Create::new(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}