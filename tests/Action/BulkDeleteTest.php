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

class BulkDeleteTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(new Action\BulkDelete());
        $this->linkUrlTests(new Action\BulkDelete());
        $this->linkToTests(new Action\BulkDelete());
        $this->viewTests(new Action\BulkDelete());
        $this->localeTests(new Action\BulkDelete());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\BulkDelete());
        $this->entitiesTests(new Action\BulkDelete());
        $this->entityTests(new Action\BulkDelete());
        $this->controllerTests(new Action\BulkDelete());
        $this->containerTests(new Action\BulkDelete());
        $this->actionsTests(new Action\BulkDelete());
        $this->inputTests(new Action\BulkDelete());
        $this->valueTests(new Action\BulkDelete());
        $this->assertTrue(new Action\BulkDelete()->displayButton());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\BulkDelete(title: 'title');
        $this->assertInstanceof(Action\BulkDelete::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        $this->assertInstanceof(BulkActionInterface::class, $action);
        
        $this->assertSame('bulk-delete', $action->name());
        $this->assertSame('{name}.bulk', $action->getRoute()[0] ?? null);
        $this->assertSame('crud/bulk/delete', $action->getView());
        $this->assertSame([], $action->getFieldsActions());
        $this->assertSame('index', $action->getLinkToAction());
        $this->assertSame([], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Delete', new Action\BulkDelete()->title());
        $this->assertSame('Foo', new Action\BulkDelete(title: 'Foo')->title());
    }
}