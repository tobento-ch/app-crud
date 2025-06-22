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

namespace Tobento\App\Crud\Test\Button;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\Button;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button\ConfigurableButtons;
use Tobento\App\Crud\Button\Dropdown;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Test\Factory;

class ConfigurableButtonsTest extends TestCase
{
    public function testAddButtonMethod()
    {
        $button = new Button('foo', 'group');
        
        $buttons = (new ConfigurableButtonsAction())
            ->addButton($button)
            ->applyButtonsConfig(new Buttons(new Button('bar', 'group')));
        
        $this->assertNotNull($buttons->get('foo'));
    }
    
    public function testRemoveButtonMethod()
    {
        $buttons = (new ConfigurableButtonsAction())
            ->removeButton('foo', 'baz')
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('baz', 'group'),
            ));
        
        $this->assertSame(['bar'], $buttons->names());
    }
    
    public function testReoderButtonsMethod()
    {
        $buttons = (new ConfigurableButtonsAction())
            ->reorderButtons('bar', 'baz')
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('baz', 'group'),
            ));
        
        $this->assertSame(['bar', 'baz', 'foo'], $buttons->names());
    }
    
    public function testModifyButtonMethod()
    {
        $buttons = (new ConfigurableButtonsAction())
            ->modifyButton('foo', function(ButtonInterface $button, EntityInterface $entity): void {
                $button->group('Foo');
            })
            ->modifyButton('bar', function(ButtonInterface $button, EntityInterface $entity): void {
                $button->group('Bar');
            })
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
            ));
        
        $this->assertSame('Foo', $buttons->get('foo')->getGroup());
        $this->assertSame('Bar', $buttons->get('bar')->getGroup());
    }
    
    public function testGroupButtonsMethodWithExcept()
    {
        $buttons = (new ConfigurableButtonsAction())
            ->groupButtons(except: ['bar'], label: 'More', name: 'more')
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('zoo', 'group1'),
                new Button('baz', 'group'),
            ));
        
        $this->assertSame(['foo', 'baz'], $buttons->get('more')->getButtons()->names());
        $this->assertSame(['bar', 'zoo', 'more'], $buttons->names());
        $this->assertSame('More', $buttons->get('more')->getLabel());
    }
    
    public function testGroupButtonsMethodWithOnly()
    {
        $buttons = (new ConfigurableButtonsAction())
            ->groupButtons(only: ['bar', 'baz'], label: 'More', name: 'more')
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('zoo', 'group1'),
                new Button('baz', 'group'),
            ));
        
        $this->assertSame(['bar', 'baz'], $buttons->get('more')->getButtons()->names());
        $this->assertSame(['foo', 'zoo', 'more'], $buttons->names());
        $this->assertSame('More', $buttons->get('more')->getLabel());
    }
    
    public function testGroupButtonsMethodWithButton()
    {
        $btn = Dropdown::new(label: 'more', group: 'group');
        
        $buttons = (new ConfigurableButtonsAction())
            ->groupButtons(only: ['bar', 'baz'], button: $btn)
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('zoo', 'group1'),
                new Button('baz', 'group'),
            ));
        
        $this->assertSame(['bar', 'baz'], $buttons->get('more')->getButtons()->names());
        $this->assertSame(['foo', 'zoo', 'more'], $buttons->names());
        $this->assertSame($btn, $buttons->get('more'));
    }
    
    public function testGroupButtonsMethodMultiple()
    {
        $buttons = (new ConfigurableButtonsAction())
            ->groupButtons(only: ['bar'], name: 'more')
            ->groupButtons(except: ['foo', 'more'], name: 'another')
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('zoo', 'group1'),
                new Button('baz', 'group'),
            ));
        
        $this->assertSame(['bar'], $buttons->get('more')->getButtons()->names());
        $this->assertSame(['bar', 'baz'], $buttons->get('another')->getButtons()->names());
        $this->assertSame(['foo', 'zoo', 'more', 'another'], $buttons->names());
    }
    
    public function testDisplayButtonIfMethod()
    {
        $buttons = (new ConfigurableButtonsAction(entity: new Entity(['isPaid' => false])))
            ->displayButtonIf('foo', true)
            ->displayButtonIf('bar', false)
            ->displayButtonIf('baz', fn (EntityInterface $entity): bool => $entity->get('isPaid'))
            ->displayButtonIf('zoo', true)
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('baz', 'group'),
            ));
        
        $this->assertNotNull($buttons->get('foo'));
        $this->assertNull($buttons->get('bar'));
        $this->assertNull($buttons->get('baz'));
        $this->assertNull($buttons->get('zoo'));
    }
    
    public function testConfirmButtonActionMethod()
    {
        $buttons = (new ConfigurableButtonsAction(entity: new Entity(['title' => 'Title'])))
            ->confirmButtonAction('foo')
            ->confirmButtonAction('bar', 'Bar text')
            ->confirmButtonAction('baz', fn (EntityInterface $entity): string => $entity->get('title'))
            ->confirmButtonAction('zoo', false)
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('baz', 'group'),
                (new Button('zoo', 'group'))->attr('data-confirm', 'lorem'),
            ));
        
        $this->assertSame(
            '<button data-confirm="" class="button text-xs" data-button="foo">foo</button>',
            $buttons->get('foo')->render(Factory::createView())
        );
        
        $this->assertSame(
            '<button data-confirm="Bar text" class="button text-xs" data-button="bar">bar</button>',
            $buttons->get('bar')->render(Factory::createView())
        );
    
        $this->assertSame(
            '<button data-confirm="Title" class="button text-xs" data-button="baz">baz</button>',
            $buttons->get('baz')->render(Factory::createView())
        );
        
        $this->assertSame(
            '<button class="button text-xs" data-button="zoo">zoo</button>',
            $buttons->get('zoo')->render(Factory::createView())
        );
    }
    
    public function testAjaxButtonActionMethod()
    {
        $buttons = (new ConfigurableButtonsAction(entity: new Entity(['title' => 'Title'])))
            ->ajaxButtonAction('foo')
            ->ajaxButtonAction('bar', 'Bar text')
            ->ajaxButtonAction('baz', fn (EntityInterface $entity): string => $entity->get('title'))
            ->ajaxButtonAction('zoo', false)
            ->applyButtonsConfig(new Buttons(
                new Button('foo', 'group'),
                new Button('bar', 'group'),
                new Button('baz', 'group'),
                (new Button('zoo', 'group'))->attr('data-button-ajax', 'lorem'),
            ));
        
        $this->assertSame(
            '<button data-button-ajax="" class="button text-xs" data-button="foo">foo</button>',
            $buttons->get('foo')->render(Factory::createView())
        );
        
        $this->assertSame(
            '<button data-button-ajax="Bar text" class="button text-xs" data-button="bar">bar</button>',
            $buttons->get('bar')->render(Factory::createView())
        );
    
        $this->assertSame(
            '<button data-button-ajax="Title" class="button text-xs" data-button="baz">baz</button>',
            $buttons->get('baz')->render(Factory::createView())
        );
        
        $this->assertSame(
            '<button class="button text-xs" data-button="zoo">zoo</button>',
            $buttons->get('zoo')->render(Factory::createView())
        );
    }    
    
    public function testMultiple()
    {
        $action = new ConfigurableButtonsAction();
        
        $entities = [
            new Entity(['title' => 'Title1']),
            new Entity(['title' => 'Title2']),
        ];
        
        $buttons = new Buttons(
            (new Button('foo', 'group'))->name('foo'),
            (new Button('bar', 'group'))->name('bar'),
        );
        
        $labels = [];
        
        foreach($entities as $entity) {
            $btns = $action
                ->setEntity($entity)
                ->modifyButton('foo', function(ButtonInterface $button, EntityInterface $entity): void {
                    $button->label($entity->get('title'));
                })
                ->applyButtonsConfig($buttons);
            
            $labels[] = $btns->get('foo')->getLabel();
        }
        
        $this->assertSame(['Title1', 'Title2'], $labels);
    }
    
    public function testMultipleWithGroups()
    {
        $action = new ConfigurableButtonsAction();
        
        $entities = [
            new Entity(['title' => 'Title1']),
            new Entity(['title' => 'Title2']),
        ];
        
        $buttons = new Buttons(
            (new Button('foo', 'group'))->name('foo'),
            (new Button('bar', 'group'))->name('bar'),
        );
        
        $labels = [];
        
        foreach($entities as $entity) {
            $btns = $action
                ->setEntity($entity)
                ->modifyButton('foo', function(ButtonInterface $button, EntityInterface $entity): void {
                    $button->label($entity->get('title'));
                })
                ->groupButtons(only: ['foo'], name: 'more')
                ->applyButtonsConfig($buttons);
            
            $labels[] = $btns->get('more')->getButtons()->get('foo')->getLabel();
        }
        
        $this->assertSame(['Title1', 'Title2'], $labels);
    }
    
    public function testDisplayButtonIfMultipleWithGroups()
    {
        $action = new ConfigurableButtonsAction();
        
        $entities = [
            new Entity(['status' => 'paid']),
            new Entity(['status' => '']),
            new Entity(['status' => 'paid']),
        ];
        
        $buttons = new Buttons(
            (new Button('foo', 'entity'))->name('foo'),
            (new Button('bar', 'entity'))->name('bar'),
        );
        
        $exists = [];
        
        foreach($entities as $entity) {
            $btns = $action
                ->setEntity($entity)
                ->displayButtonIf('foo', fn (EntityInterface $entity): bool => $entity->get('status') === 'paid')
                ->groupButtons(only: ['bar', 'foo'], name: 'more')
                ->applyButtonsConfig($buttons->group('entity'));

            $exists[] = (bool)$btns->get('more')->getButtons()->get('foo');
        }

        $this->assertSame([true, false, true], $exists);
    }
    
    public function testMultipleWithGroupsWithoutOnlyNorExcept()
    {
        $action = new ConfigurableButtonsAction();

        $buttons = new Buttons(
            (new Button('list', 'group'))->name('list'),
            (new Button('foo', 'group'))->name('foo'),
            (new Button('bar', 'group'))->name('bar'),
        );
        
        $labels = [];
        
        $btns = $action
            ->groupButtons(
                name: 'list',
                label: 'List',
            )
            ->applyButtonsConfig($buttons);
        
        foreach($btns->get('list')->getButtons() as $button) {
            $labels[] = $button->getLabel();
        }
        
        $this->assertSame(['list', 'foo', 'bar'], $labels);
    }
    
    public function testMultipleWithGroupsWithoutOnlyNorExceptWithButton()
    {
        $action = new ConfigurableButtonsAction();

        $buttons = new Buttons(
            (new Button('foo', 'group'))->name('foo'),
            (new Button('bar', 'group'))->name('bar'),
        );
        
        $labels = [];
        
        $btns = $action
            ->groupButtons(
                button: (new Dropdown('list', 'group'))->name('list'),
            )
            ->applyButtonsConfig($buttons);
        
        foreach($btns->get('list')->getButtons() as $button) {
            $labels[] = $button->getLabel();
        }
        
        $this->assertSame(['foo', 'bar'], $labels);
    }
}

class ConfigurableButtonsAction
{
    use ConfigurableButtons;
    
    public function __construct(
        private null|EntityInterface $entity = null,
    ) {}
    
    public function setEntity(EntityInterface $entity): static
    {
        $this->entity = $entity;
        return $this;
    }
}