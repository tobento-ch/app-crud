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

class UpdateTest extends AbstractAction
{
    public function testDefaultInterfaceMethods()
    {
        $this->urlTests(Action\Update::new());
        $this->linkUrlTests(Action\Update::new());
        $this->linkToTests(Action\Update::new());
        $this->viewTests(Action\Update::new());
        $this->localeTests(Action\Update::new());
        $this->buttonTests(Action\Create::new());
        $this->fieldTests(Action\Update::new());
        $this->entitiesTests(Action\Update::new());
        $this->entityTests(Action\Update::new());
        $this->controllerTests(Action\Update::new());
        $this->actionsTests(Action\Update::new());
        $this->inputTests(Action\Update::new());
    }
    
    public function testDefaultAction()
    {
        $action = Action\Update::new();
        $this->assertInstanceof(Action\Update::class, $action);
        $this->assertInstanceof(ActionInterface::class, $action);
        
        $this->assertSame('update', $action->name());
        $this->assertSame('{name}.update', $action->getRoute()[0] ?? null);
        $this->assertSame('', $action->getView());
        $this->assertTrue(is_callable($action->getFieldsActions()['validation'] ?? null));
        $this->assertSame('index', $action->getLinkToAction());
        $this->assertSame([], $action->buttons()->names());
    }
    
    public function testTitleMethod()
    {
        $this->assertSame('Update', Action\Update::new()->title());
    }
}