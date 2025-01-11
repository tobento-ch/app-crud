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

class ShowTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\Show::new());
        $this->linkUrlTests(Action\Show::new());
        $this->linkToTests(Action\Show::new());
        $this->viewTests(Action\Show::new());
        $this->localeTests(Action\Show::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\Show::new());
        $this->entitiesTests(Action\Show::new());
        $this->entityTests(Action\Show::new());
        $this->controllerTests(Action\Show::new());
        $this->actionsTests(Action\Show::new());
        $this->inputTests(Action\Show::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Show::new(title: 'title');
        $this->assertInstanceof(Action\Show::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('show', $action->name());
        $this->assertSame('{name}.show', $action->getRoute()[0] ?? null);
        $this->assertSame('crud/show', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame(null, $action->getLinkToAction());
        $this->assertSame(['back'], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Show', Action\Show::new()->title());
        $this->assertSame('Foo', Action\Show::new(title: 'Foo')->title());        
        $this->assertSame('Foo', Action\Show::new(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}