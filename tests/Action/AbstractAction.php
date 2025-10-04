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

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\Actions;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Entity\EntitiesInterface;
use Tobento\App\Crud\Entity\Entities;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\AbstractCrudController;
use Closure;

abstract class AbstractAction extends TestCase
{
    public function urlTests(ActionInterface $action)
    {
        // raw url:
        $this->assertSame(null, $action->getRawUrl());
        
        $action->url('url');
        $this->assertSame('url', $action->getRawUrl());
        
        $action->url(fn(EntityInterface $e) => 'url');
        $this->assertInstanceof(Closure::class, $action->getRawUrl());
        
        // resolved url:
        $this->assertSame('', $action->getUrl());
        
        $action->setUrl('url');
        $this->assertSame('url', $action->getUrl());
    }
    
    public function linkUrlTests(ActionInterface $action)
    {
        $this->assertSame('', $action->getLinkUrl());
        
        $action->setLinkUrl('url');
        $this->assertSame('url', $action->getLinkUrl());
    }
    
    public function linkToTests(ActionInterface $action)
    {
        $this->assertSame('link', $action->linkToUrl('link')->getLinkToUrl());
        $this->assertInstanceof(Closure::class, $action->linkToUrl(fn() => 'link')->getLinkToUrl());
        $this->assertSame('name', $action->linkToAction('name')->getLinkToAction());
        $this->assertSame(['name', ['id' => 5]], $action->linkToRoute('name', ['id' => 5])->getLinkToRoute());
    }
    
    public function viewTests(ActionInterface $action)
    {
        $action->view('custom');
        $this->assertSame('custom', $action->getView());
    }
    
    public function localeTests(ActionInterface $action)
    {
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN'], $action->getLocales());
        
        $action->locales(['de' => 'De', 'en' => 'En']);
        $this->assertSame('de', $action->getLocale());
        $this->assertSame(['de' => 'De', 'en' => 'En'], $action->getLocales());
    }
    
    public function buttonTests(ActionInterface $action)
    {
        $this->assertInstanceof(ButtonsInterface::class, $action->buttons());
        $this->assertSame(0, $action->setButtons(new Button\Buttons())->buttons()->count());
        $this->assertSame(1, $action->setButtons(new Button\Buttons(
            new Button\Button('label', 'group'),
        ))->buttons()->count());
        $this->assertSame(1, $action->setButtons(new Button\Button('label', 'group'))->buttons()->count());
    }
    
    public function fieldTests(ActionInterface $action)
    {
        $this->assertInstanceof(FieldsInterface::class, $action->fields());
        $this->assertSame(0, $action->setFields(new Fields())->fields()->count());
        $this->assertSame(1, $action->setFields(new Fields(
            new Field\Text('foo'),
        ))->fields()->count());
    }
    
    public function filterTests(ActionInterface $action)
    {
        $this->assertInstanceof(FiltersInterface::class, $action->filters());
        $this->assertSame(0, $action->setFilters(new Filters())->filters()->count());
        $this->assertSame(1, $action->setFilters(new Filters(
            new Filter\FieldsSortOrder(),
        ))->filters()->count());
    }
    
    public function entitiesTests(ActionInterface $action)
    {
        $this->assertInstanceof(EntitiesInterface::class, $action->entities());
        $this->assertSame(0, $action->setEntities(new Entities())->entities()->count());
        $this->assertSame(1, $action->setEntities(new Entities([
            new Entity(),
        ]))->entities()->count());
    }
    
    public function entityTests(ActionInterface $action)
    {
        $this->assertInstanceof(EntityInterface::class, $action->entity());
        
        $entity = new Entity();
        $this->assertSame($entity, $action->setEntity($entity)->entity());
    }
    
    public function controllerTests(ActionInterface $action)
    {
        $controller = new class() extends AbstractCrudController {
            protected function configureFields(ActionInterface $action): iterable|FieldsInterface
            {
                return [];
            }

            protected function configureActions(): iterable|ActionsInterface
            {
                return [];
            }

            protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
            {
                return [];
            }
        };
        
        $action->setController($controller);
        $this->assertSame($controller, $action->controller());
    }
    
    public function actionsTests(ActionInterface $action)
    {
        $this->assertInstanceof(ActionsInterface::class, $action->actions());
        
        $actions = new Actions();
        $this->assertSame($actions, $action->setActions($actions)->actions());
    }
    
    public function inputTests(ActionInterface $action)
    {
        $this->assertInstanceof(InputInterface::class, $action->getInput());
        
        $input = new Input();
        $this->assertSame($input, $action->setInput($input)->getInput());
    }
}