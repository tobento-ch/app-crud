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
        $this->urlTests(Action\BulkDelete::new());
        $this->linkUrlTests(Action\BulkDelete::new());
        $this->linkToTests(Action\BulkDelete::new());
        $this->viewTests(Action\BulkDelete::new());
        $this->localeTests(Action\BulkDelete::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\BulkDelete::new());
        $this->entitiesTests(Action\BulkDelete::new());
        $this->entityTests(Action\BulkDelete::new());
        $this->controllerTests(Action\BulkDelete::new());
        $this->actionsTests(Action\BulkDelete::new());
        $this->inputTests(Action\BulkDelete::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\BulkDelete::new(title: 'title');
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
        $this->assertSame('Delete', Action\BulkDelete::new()->title());
        $this->assertSame('Foo', Action\BulkDelete::new(title: 'Foo')->title());
    }
}