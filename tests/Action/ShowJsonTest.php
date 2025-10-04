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
        $this->urlTests(new Action\ShowJson());
        $this->linkUrlTests(new Action\ShowJson());
        $this->linkToTests(new Action\ShowJson());
        $this->viewTests(new Action\ShowJson());
        $this->localeTests(new Action\ShowJson());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\ShowJson());
        $this->entitiesTests(new Action\ShowJson());
        $this->entityTests(new Action\ShowJson());
        $this->controllerTests(new Action\ShowJson());
        $this->actionsTests(new Action\ShowJson());
        $this->inputTests(new Action\ShowJson());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\ShowJson();
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
        $this->assertSame('Show JSON', new Action\ShowJson()->title());
        $this->assertSame('Foo', new Action\ShowJson(title: 'Foo')->title());
        $this->assertSame('Foo', new Action\ShowJson(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}