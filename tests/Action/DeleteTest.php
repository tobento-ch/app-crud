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
        $this->urlTests(new Action\Delete());
        $this->linkUrlTests(new Action\Delete());
        $this->linkToTests(new Action\Delete());
        $this->viewTests(new Action\Delete());
        $this->localeTests(new Action\Delete());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\Delete());
        $this->entitiesTests(new Action\Delete());
        $this->entityTests(new Action\Delete());
        $this->controllerTests(new Action\Delete());
        $this->controllerTests(new Action\Delete());$this->actionsTests(new Action\Delete());
        $this->inputTests(new Action\Delete());
        $this->valueTests(new Action\Delete());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Delete(title: 'title');
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
        $this->assertSame('Delete', new Action\Delete()->title());
        $this->assertSame('Foo', new Action\Delete(title: 'Foo')->title());        
        $this->assertSame('Foo', new Action\Delete(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}