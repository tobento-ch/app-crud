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

class CopyTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(new Action\Copy());
        $this->linkUrlTests(new Action\Copy());
        $this->linkToTests(new Action\Copy());
        $this->viewTests(new Action\Copy());
        $this->localeTests(new Action\Copy());
        $this->buttonTests(new Action\Copy());
        $this->fieldTests(new Action\Copy());
        $this->entitiesTests(new Action\Copy());
        $this->entityTests(new Action\Copy());
        $this->controllerTests(new Action\Copy());
        $this->actionsTests(new Action\Copy());
        $this->inputTests(new Action\Copy());
        $this->valueTests(new Action\Copy());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Copy(title: 'title');
        $this->assertInstanceof(Action\Copy::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('copy', $action->name());
        $this->assertSame('{name}.copy', $action->getRoute()[0] ?? null);
        $this->assertSame('crud/create', $action->getView());
        $this->assertSame(['afterLiveUpdate'], array_keys($action->getFieldsActions()));
        $this->assertSame('store', $action->getLinkToAction());
        $this->assertSame(['cancel', 'save', 'close', 'copy', 'new'], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Copy', new Action\Copy()->title());
        $this->assertSame('Foo', new Action\Copy(title: 'Foo')->title());        
        $this->assertSame('Foo', new Action\Copy(title: fn(EntityInterface $e) => 'Foo')->title());
    }
}