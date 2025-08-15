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
        $this->urlTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->linkUrlTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->linkToTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->viewTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->localeTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->buttonTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->fieldTests(Action\BulkEdit::new(name: 'edit-status')->field('foo'));
        $this->entitiesTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->entityTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->controllerTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->actionsTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->inputTests(Action\BulkEdit::new(name: 'edit-status'));
        $this->assertTrue(Action\BulkDelete::new()->displayButton());
    }
    
    public function testDefaultAction()
    {
        $action = Action\BulkEdit::new(name: 'edit-status', title: 'title');
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
        $this->assertSame('edit-status', Action\BulkEdit::new(name: 'edit-status')->title());
        $this->assertSame('Foo', Action\BulkEdit::new(name: 'edit-status', title: 'Foo')->title());
    }
}