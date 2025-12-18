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
        $this->urlTests(new Action\BulkTreeUpdate());
        $this->linkUrlTests(new Action\BulkTreeUpdate());
        $this->linkToTests(new Action\BulkTreeUpdate());
        $this->viewTests(new Action\BulkTreeUpdate());
        $this->localeTests(new Action\BulkTreeUpdate());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\BulkTreeUpdate());
        $this->entitiesTests(new Action\BulkTreeUpdate());
        $this->entityTests(new Action\BulkTreeUpdate());
        $this->controllerTests(new Action\BulkTreeUpdate());
        $this->containerTests(new Action\BulkTreeUpdate());
        $this->actionsTests(new Action\BulkTreeUpdate());
        $this->inputTests(new Action\BulkTreeUpdate());
        $this->valueTests(new Action\BulkTreeUpdate());
        $this->assertFalse(new Action\BulkTreeUpdate()->displayButton());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\BulkTreeUpdate(title: 'title');
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
        $this->assertSame('Tree Update', new Action\BulkTreeUpdate()->title());
        $this->assertSame('Foo', new Action\BulkTreeUpdate(title: 'Foo')->title());
    }
}