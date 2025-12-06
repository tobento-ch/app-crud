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
        $this->urlTests(new Action\Update());
        $this->linkUrlTests(new Action\Update());
        $this->linkToTests(new Action\Update());
        $this->viewTests(new Action\Update());
        $this->localeTests(new Action\Update());
        $this->buttonTests(new Action\Create());
        $this->fieldTests(new Action\Update());
        $this->entitiesTests(new Action\Update());
        $this->entityTests(new Action\Update());
        $this->controllerTests(new Action\Update());
        $this->actionsTests(new Action\Update());
        $this->inputTests(new Action\Update());
        $this->valueTests(new Action\Update());
    }
    
    public function testDefaultAction()
    {
        $action = new Action\Update();
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
        $this->assertSame('Update', new Action\Update()->title());
    }
}