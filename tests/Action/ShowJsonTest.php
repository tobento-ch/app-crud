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

class ShowJsonTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\ShowJson::new());
        $this->linkUrlTests(Action\ShowJson::new());
        $this->linkToTests(Action\ShowJson::new());
        $this->viewTests(Action\ShowJson::new());
        $this->localeTests(Action\ShowJson::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\ShowJson::new());
        $this->entitiesTests(Action\ShowJson::new());
        $this->entityTests(Action\ShowJson::new());
        $this->controllerTests(Action\ShowJson::new());
        $this->actionsTests(Action\ShowJson::new());
        $this->inputTests(Action\ShowJson::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\ShowJson::new();
        $this->assertInstanceof(Action\ShowJson::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('show.json', $action->name());
        $this->assertSame('{name}.show', $action->getRoute()[0] ?? null);
        $this->assertSame('', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame(null, $action->getLinkToAction());
        $this->assertSame([], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Show JSON', Action\ShowJson::new()->title());
        $this->assertSame('Foo', Action\ShowJson::new(title: 'Foo')->title());
        $this->assertSame('Foo', Action\ShowJson::new(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}