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

class CopyTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\Copy::new());
        $this->linkUrlTests(Action\Copy::new());
        $this->linkToTests(Action\Copy::new());
        $this->viewTests(Action\Copy::new());
        $this->localeTests(Action\Copy::new());
        $this->buttonTests(Action\Copy::new());
        $this->fieldTests(Action\Copy::new());
        $this->entitiesTests(Action\Copy::new());
        $this->entityTests(Action\Copy::new());
        $this->controllerTests(Action\Copy::new());
        $this->actionsTests(Action\Copy::new());
        $this->inputTests(Action\Copy::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Copy::new(title: 'title');
        $this->assertInstanceof(Action\Copy::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('copy', $action->name());
        $this->assertSame('{name}.copy', $action->getRoute()[0] ?? null);
        $this->assertSame('crud/create', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('store', $action->getLinkToAction());
        $this->assertSame(['cancel', 'save', 'close', 'copy', 'new'], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Copy', Action\Copy::new()->title());
        $this->assertSame('Foo', Action\Copy::new(title: 'Foo')->title());        
        $this->assertSame('Foo', Action\Copy::new(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}