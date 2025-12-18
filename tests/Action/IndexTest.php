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
        $this->urlTests(new Action\Index());
        $this->linkUrlTests(new Action\Index());
        $this->linkToTests(new Action\Index());
        $this->viewTests(new Action\Index());
        $this->localeTests(new Action\Index());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\Index());
        $this->filterTests(new Action\Index());
        $this->entitiesTests(new Action\Index());
        $this->entityTests(new Action\Index());
        $this->controllerTests(new Action\Index());
        $this->containerTests(new Action\Index());
        $this->actionsTests(new Action\Index());
        $this->inputTests(new Action\Index());
        $this->valueTests(new Action\Index());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Index(title: 'title');
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
        $this->assertSame('Index', new Action\Index()->title());
        $this->assertSame('Foo', new Action\Index(title: 'Foo')->title());        
        $this->assertSame('Foo', new Action\Index(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}