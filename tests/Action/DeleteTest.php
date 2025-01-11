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

class DeleteTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\Delete::new());
        $this->linkUrlTests(Action\Delete::new());
        $this->linkToTests(Action\Delete::new());
        $this->viewTests(Action\Delete::new());
        $this->localeTests(Action\Delete::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\Delete::new());
        $this->entitiesTests(Action\Delete::new());
        $this->entityTests(Action\Delete::new());
        $this->controllerTests(Action\Delete::new());
        $this->actionsTests(Action\Delete::new());
        $this->inputTests(Action\Delete::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Delete::new(title: 'title');
        $this->assertInstanceof(Action\Delete::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('delete', $action->name());
        $this->assertSame('{name}.delete', $action->getRoute()[0] ?? null);
        $this->assertSame('', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('index', $action->getLinkToAction());
        $this->assertSame([], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Delete', Action\Delete::new()->title());
        $this->assertSame('Foo', Action\Delete::new(title: 'Foo')->title());        
        $this->assertSame('Foo', Action\Delete::new(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}