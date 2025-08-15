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

class BulkTreeUpdateTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\BulkTreeUpdate::new());
        $this->linkUrlTests(Action\BulkTreeUpdate::new());
        $this->linkToTests(Action\BulkTreeUpdate::new());
        $this->viewTests(Action\BulkTreeUpdate::new());
        $this->localeTests(Action\BulkTreeUpdate::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\BulkTreeUpdate::new());
        $this->entitiesTests(Action\BulkTreeUpdate::new());
        $this->entityTests(Action\BulkTreeUpdate::new());
        $this->controllerTests(Action\BulkTreeUpdate::new());
        $this->actionsTests(Action\BulkTreeUpdate::new());
        $this->inputTests(Action\BulkTreeUpdate::new());
        $this->assertFalse(Action\BulkTreeUpdate::new()->displayButton());
    }
    
    public function testDefaultAction()
    {
        $action = Action\BulkTreeUpdate::new(title: 'title');
        $this->assertInstanceof(Action\BulkTreeUpdate::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        $this->assertInstanceof(BulkActionInterface::class, $action);
        
        $this->assertSame('bulk-tree-update', $action->name());
        $this->assertSame('{name}.bulk', $action->getRoute()[0] ?? null);
        $this->assertSame('crud/bulk/tree-update', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('index', $action->getLinkToAction());
        $this->assertSame([], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Tree Update', Action\BulkTreeUpdate::new()->title());
        $this->assertSame('Foo', Action\BulkTreeUpdate::new(title: 'Foo')->title());
    }
}