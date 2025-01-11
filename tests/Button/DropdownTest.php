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

use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\ButtonsAwareInterface;
use Tobento\App\Crud\Button\Button;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button\Dropdown;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class DropdownTest extends AbstractButton
{
    public function testImplementsButtonsAwareInterface()
    {
        $this->assertInstanceof(
            ButtonsAwareInterface::class,
            Dropdown::new(label: 'label', group: 'group')
        );
    }
    
    public function testWithButtonsMethod()
    {
        $button = Dropdown::new(label: 'label', group: 'group');
        $newButton = $button->withButtons(new Buttons());
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testGetButtonsMethod()
    {
        $button = Dropdown::new(label: 'label', group: 'group');
        $this->assertSame(0, $button->getButtons()->count());
        
        $button = $button->withButtons(new Buttons(Button::new(label: 'bar', group: 'group')));
        $this->assertSame(1, $button->getButtons()->count());
    }
    
    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(Dropdown::new(label: 'label', group: 'group'));
    }
    
    public function testInterfaceGetterMethods()
    {
        $button = Dropdown::new(label: 'label', group: 'group');
        
        $this->assertSame('label', $button->getName());
        $this->assertSame('label', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
        $this->assertSame(null, $button->getIcon());
    }
    
    public function testInterfaceSetterMethods()
    {
        $button = Dropdown::new(label: 'label', group: 'group');
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');
        
        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());
    }
    
    public function testWithUrlMethod()
    {
        $button = Dropdown::new(label: 'label', group: 'group');
        $newButton = $button->withUrl('url');
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testWithEntityMethod()
    {
        $button = Dropdown::new(label: 'label', group: 'group');
        $newButton = $button->withEntity(new Entity());
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testRenderMethod()
    {
        $button = Dropdown::new(label: 'label', group: 'group');
        
        $this->assertSame(
            '<div class="crud-dropdown" data-dropdown="dropdown-menu-label"><span class="button text-xs" aria-haspopup="true" aria-controls="dropdown-menu-label" data-button="label">label</span><div class="crud-dropdown-menu" id="dropdown-menu-label" role="menu"><div class="crud-dropdown-body"></div></div></div>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderWithButtons()
    {
        $button = Dropdown::new(label: 'label', group: 'group')->buttons(
            Button::new(label: 'foo', group: 'group'),
            Button::new(label: 'bar', group: 'group'),
        );
        
        $this->assertSame('<div class="crud-dropdown" data-dropdown="dropdown-menu-label"><span class="button text-xs" aria-haspopup="true" aria-controls="dropdown-menu-label" data-button="label">label</span><div class="crud-dropdown-menu" id="dropdown-menu-label" role="menu"><div class="crud-dropdown-body"><div class="crud-dropdown-item"><button class="button text-xs" data-button="foo">foo</button></div><div class="crud-dropdown-item"><button class="button text-xs" data-button="bar">bar</button></div></div></div></div>', $button->render(Factory::createView()));
    }
    
    public function testRenderMethodWithPrimary()
    {
        $button = Dropdown::new(label: 'label', group: 'group')
            ->primary();
        
        $this->assertSame(
            '<div class="crud-dropdown" data-dropdown="dropdown-menu-label"><span class="button text-xs primary" aria-haspopup="true" aria-controls="dropdown-menu-label" data-button="label">label</span><div class="crud-dropdown-menu" id="dropdown-menu-label" role="menu"><div class="crud-dropdown-body"></div></div></div>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithRaw()
    {
        $button = Dropdown::new(label: 'label', group: 'group')
            ->raw();
        
        $this->assertSame(
            '<div class="crud-dropdown" data-dropdown="dropdown-menu-label"><span class="button text-xs raw" aria-haspopup="true" aria-controls="dropdown-menu-label" data-button="label">label</span><div class="crud-dropdown-menu" id="dropdown-menu-label" role="menu"><div class="crud-dropdown-body"></div></div></div>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithIcon()
    {
        $button = Dropdown::new(label: 'label', group: 'group')
            ->icon('foo');
        
        $this->assertSame(
            '<div class="crud-dropdown" data-dropdown="dropdown-menu-label"><span class="button text-xs" aria-haspopup="true" aria-controls="dropdown-menu-label" data-button="label"><i>foo</i>label</span><div class="crud-dropdown-menu" id="dropdown-menu-label" role="menu"><div class="crud-dropdown-body"></div></div></div>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithAttr()
    {
        $button = Dropdown::new(label: 'label', group: 'group')
            ->attr('data-foo', 'value');
        
        $this->assertSame(
            '<div class="crud-dropdown" data-dropdown="dropdown-menu-label"><span data-foo="value" class="button text-xs" aria-haspopup="true" aria-controls="dropdown-menu-label" data-button="label">label</span><div class="crud-dropdown-menu" id="dropdown-menu-label" role="menu"><div class="crud-dropdown-body"></div></div></div>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodLabelIsEscaped()
    {
        $button = Dropdown::new(label: '<p>label</p>', group: 'group')
            ->name('name');
        
        $this->assertSame(
            '<div class="crud-dropdown" data-dropdown="dropdown-menu-name"><span class="button text-xs" aria-haspopup="true" aria-controls="dropdown-menu-name" data-button="name">&lt;p&gt;label&lt;/p&gt;</span><div class="crud-dropdown-menu" id="dropdown-menu-name" role="menu"><div class="crud-dropdown-body"></div></div></div>',
            $button->render(Factory::createView())
        );
    }
}