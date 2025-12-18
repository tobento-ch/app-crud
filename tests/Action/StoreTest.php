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

class StoreTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(new Action\Store());
        $this->linkUrlTests(new Action\Store());
        $this->linkToTests(new Action\Store());
        $this->viewTests(new Action\Store());
        $this->localeTests(new Action\Store());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\Store());
        $this->entitiesTests(new Action\Store());
        $this->entityTests(new Action\Store());
        $this->controllerTests(new Action\Store());
        $this->containerTests(new Action\Store());
        $this->actionsTests(new Action\Store());
        $this->inputTests(new Action\Store());
        $this->valueTests(new Action\Store());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Store();
        $this->assertInstanceof(Action\Store::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('store', $action->name());
        $this->assertSame(['{name}.store', []], $action->getRoute());
        $this->assertSame('', $action->getView());
        $this->assertTrue(is_callable($action->getFieldsActions()['validation'] ?? null));
        $this->assertSame('index', $action->getLinkToAction());
        $this->assertSame([], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Store', new Action\Store()->title());
    }
}