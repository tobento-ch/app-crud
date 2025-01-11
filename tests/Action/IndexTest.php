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

class IndexTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\Index::new());
        $this->linkUrlTests(Action\Index::new());
        $this->linkToTests(Action\Index::new());
        $this->viewTests(Action\Index::new());
        $this->localeTests(Action\Index::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\Index::new());
        $this->entitiesTests(Action\Index::new());
        $this->entityTests(Action\Index::new());
        $this->controllerTests(Action\Index::new());
        $this->actionsTests(Action\Index::new());
        $this->inputTests(Action\Index::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Index::new(title: 'title');
        $this->assertInstanceof(Action\Index::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('index', $action->name());
        $this->assertSame(['{name}.index', []], $action->getRoute());
        $this->assertSame('crud/index', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('index', $action->getLinkToAction());
        $this->assertSame(['create', 'edit', 'copy', 'show', 'show.json', 'delete', 'update'], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Index', Action\Index::new()->title());
        $this->assertSame('Foo', Action\Index::new(title: 'Foo')->title());        
        $this->assertSame('Foo', Action\Index::new(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}