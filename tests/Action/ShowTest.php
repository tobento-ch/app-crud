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
        $this->urlTests(new Action\Show());
        $this->linkUrlTests(new Action\Show());
        $this->linkToTests(new Action\Show());
        $this->viewTests(new Action\Show());
        $this->localeTests(new Action\Show());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\Show());
        $this->entitiesTests(new Action\Show());
        $this->entityTests(new Action\Show());
        $this->controllerTests(new Action\Show());
        $this->actionsTests(new Action\Show());
        $this->inputTests(new Action\Show());
        $this->valueTests(new Action\Show());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Show(title: 'title');
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
        $this->assertSame('Show', new Action\Show()->title());
        $this->assertSame('Foo', new Action\Show(title: 'Foo')->title());        
        $this->assertSame('Foo', new Action\Show(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}