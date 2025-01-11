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
use Tobento\App\Crud\Button\Delete;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class DeleteTest extends AbstractButton
{
    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(Delete::new(label: 'label', group: 'group'));
    }
    
    public function testInterfaceGetterMethods()
    {
        $button = Delete::new(label: 'label', group: 'group');
        
        $this->assertSame('label', $button->getName());
        $this->assertSame('label', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
        $this->assertSame(null, $button->getIcon());
    }
    
    public function testInterfaceSetterMethods()
    {
        $button = Delete::new(label: 'label', group: 'group');
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');
        
        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());
    }
    
    public function testWithUrlMethod()
    {
        $button = Delete::new(label: 'label', group: 'group');
        $newButton = $button->withUrl('url');
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testWithEntityMethod()
    {
        $button = Delete::new(label: 'label', group: 'group');
        $newButton = $button->withEntity(new Entity());
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testRenderMethod()
    {
        $button = Delete::new(label: 'label', group: 'group')
            ->name('delete')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="_method" type="hidden" value="DELETE"><input name="id" type="hidden" value="3"><button class="button text-xs" data-button="delete">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithPrimary()
    {
        $button = Delete::new(label: 'label', group: 'group')
            ->primary()
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="_method" type="hidden" value="DELETE"><input name="id" type="hidden" value="3"><button class="button text-xs primary" data-button="label">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithRaw()
    {
        $button = Delete::new(label: 'label', group: 'group')
            ->raw()
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="_method" type="hidden" value="DELETE"><input name="id" type="hidden" value="3"><button class="button text-xs raw" data-button="label">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithIcon()
    {
        $button = Delete::new(label: 'label', group: 'group')
            ->icon('foo')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="_method" type="hidden" value="DELETE"><input name="id" type="hidden" value="3"><button class="button text-xs" data-button="label"><i>foo</i>label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithAttr()
    {
        $button = Delete::new(label: 'label', group: 'group')
            ->attr('data-foo', 'value')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="_method" type="hidden" value="DELETE"><input name="id" type="hidden" value="3"><button data-foo="value" class="button text-xs" data-button="label">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodLabelIsEscaped()
    {
        $button = Delete::new(label: '<p>label</p>', group: 'group')
            ->name('delete')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="_method" type="hidden" value="DELETE"><input name="id" type="hidden" value="3"><button class="button text-xs" data-button="delete">&lt;p&gt;label&lt;/p&gt;</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithoutEntityReturnsEmptyString()
    {
        $button = Delete::new(label: 'label', group: 'group');
        
        $this->assertSame('', $button->render(Factory::createView()));
    }    
}