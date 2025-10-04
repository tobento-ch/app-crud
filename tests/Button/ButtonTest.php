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
use Tobento\App\Crud\Button\Button;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class ButtonTest extends AbstractButton
{
    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(new Button(label: 'label', group: 'group'));
    }
    
    public function testInterfaceGetterMethods()
    {
        $button = new Button(label: 'label', group: 'group');
        
        $this->assertSame('label', $button->getName());
        $this->assertSame('label', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
        $this->assertSame(null, $button->getIcon());
    }
    
    public function testInterfaceSetterMethods()
    {
        $button = new Button(label: 'label', group: 'group');
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');
        
        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());
    }
    
    public function testWithUrlMethod()
    {
        $button = new Button(label: 'label', group: 'group');
        $newButton = $button->withUrl('url');
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testWithEntityMethod()
    {
        $button = new Button(label: 'label', group: 'group');
        $newButton = $button->withEntity(new Entity());
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testRenderMethod()
    {
        $button = new Button(label: 'label', group: 'group');
        
        $this->assertSame(
            '<button class="button text-xs" data-button="label">label</button>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithPrimary()
    {
        $button = new Button(label: 'label', group: 'group');
        $button->primary();
        
        $this->assertSame(
            '<button class="button text-xs primary" data-button="label">label</button>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithRaw()
    {
        $button = new Button(label: 'label', group: 'group');
        $button->raw();
        
        $this->assertSame(
            '<button class="button text-xs raw" data-button="label">label</button>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithIcon()
    {
        $button = new Button(label: 'label', group: 'group');
        $button->icon('foo');
        
        $this->assertSame(
            '<button class="button text-xs" data-button="label"><i>foo</i>label</button>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithAttr()
    {
        $button = new Button(label: 'label', group: 'group');
        $button->attr('data-foo', 'value');
        
        $this->assertSame(
            '<button data-foo="value" class="button text-xs" data-button="label">label</button>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodLabelIsEscaped()
    {
        $button = new Button(label: '<p>label</p>', group: 'group');
        $button->name('name');
        
        $this->assertSame(
            '<button class="button text-xs" data-button="name">&lt;p&gt;label&lt;/p&gt;</button>',
            $button->render(Factory::createView())
        );
    }
}