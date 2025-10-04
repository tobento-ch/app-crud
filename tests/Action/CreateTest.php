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
        $this->urlTests(new Action\Create());
        $this->linkUrlTests(new Action\Create());
        $this->linkToTests(new Action\Create());
        $this->viewTests(new Action\Create());
        $this->localeTests(new Action\Create());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\Create());
        $this->entitiesTests(new Action\Create());
        $this->entityTests(new Action\Create());
        $this->controllerTests(new Action\Create());
        $this->actionsTests(new Action\Create());
        $this->inputTests(new Action\Create());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Create(title: 'title');
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
        $this->assertSame('Create', new Action\Create()->title());
        $this->assertSame('Foo', new Action\Create(title: 'Foo')->title());        
        $this->assertSame('Foo', new Action\Create(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}