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

use Tobento\App\Crud\Action\BulkActionInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

class BulkEditTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(new Action\BulkEdit(name: 'edit-status'));
        $this->linkUrlTests(new Action\BulkEdit(name: 'edit-status'));
        $this->linkToTests(new Action\BulkEdit(name: 'edit-status'));
        $this->viewTests(new Action\BulkEdit(name: 'edit-status'));
        $this->localeTests(new Action\BulkEdit(name: 'edit-status'));
        $this->buttonTests(new Action\BulkEdit(name: 'edit-status'));
        $this->fieldTests(new Action\BulkEdit(name: 'edit-status')->field('foo'));
        $this->entitiesTests(new Action\BulkEdit(name: 'edit-status'));
        $this->entityTests(new Action\BulkEdit(name: 'edit-status'));
        $this->controllerTests(new Action\BulkEdit(name: 'edit-status'));
        $this->actionsTests(new Action\BulkEdit(name: 'edit-status'));
        $this->inputTests(new Action\BulkEdit(name: 'edit-status'));
        $this->assertTrue(new Action\BulkDelete()->displayButton());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\BulkEdit(name: 'edit-status', title: 'title');
        $this->assertInstanceof(Action\BulkEdit::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        $this->assertInstanceof(BulkActionInterface::class, $action);
        
        $this->assertSame('edit-status', $action->name());
        $this->assertSame('{name}.bulk', $action->getRoute()[0] ?? null);
        $this->assertSame('crud/bulk/edit', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('index', $action->getLinkToAction());
        $this->assertSame([], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('edit-status', new Action\BulkEdit(name: 'edit-status')->title());
        $this->assertSame('Foo', new Action\BulkEdit(name: 'edit-status', title: 'Foo')->title());
    }
}