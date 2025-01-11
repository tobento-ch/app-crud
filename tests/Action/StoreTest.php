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
        $this->urlTests(Action\Store::new());
        $this->linkUrlTests(Action\Store::new());
        $this->linkToTests(Action\Store::new());
        $this->viewTests(Action\Store::new());
        $this->localeTests(Action\Store::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\Store::new());
        $this->entitiesTests(Action\Store::new());
        $this->entityTests(Action\Store::new());
        $this->controllerTests(Action\Store::new());
        $this->actionsTests(Action\Store::new());
        $this->inputTests(Action\Store::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Store::new();
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
        $this->assertSame('Store', Action\Store::new()->title());
    }
}