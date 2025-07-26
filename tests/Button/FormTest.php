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
use Tobento\App\Crud\Button\Form;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class FormTest extends AbstractButton
{
    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(Form::new(label: 'label', group: 'group'));
    }
    
    public function testInterfaceGetterMethods()
    {
        $button = Form::new(label: 'label', group: 'group');
        
        $this->assertSame('label', $button->getName());
        $this->assertSame('label', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
        $this->assertSame(null, $button->getIcon());
    }
    
    public function testInterfaceSetterMethods()
    {
        $button = Form::new(label: 'label', group: 'group');
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');
        
        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());
    }
    
    public function testWithUrlMethod()
    {
        $button = Form::new(label: 'label', group: 'group');
        $newButton = $button->withUrl('url');
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testWithEntityMethod()
    {
        $button = Form::new(label: 'label', group: 'group');
        $newButton = $button->withEntity(new Entity());
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testRenderMethodReturnsEmptyStringIfUrlIsEmpty()
    {
        $button = Form::new(label: 'label', group: 'group')
            ->name('delete')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethod()
    {
        $button = Form::new(label: 'label', group: 'group')
            ->renderEmptyUrl(true)
            ->name('delete')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="id" type="hidden" value="3"><button class="button text-xs" data-button="delete">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithPrimary()
    {
        $button = Form::new(label: 'label', group: 'group')
            ->renderEmptyUrl(true)
            ->primary()
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="id" type="hidden" value="3"><button class="button text-xs primary" data-button="label">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithRaw()
    {
        $button = Form::new(label: 'label', group: 'group')
            ->renderEmptyUrl(true)
            ->raw()
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="id" type="hidden" value="3"><button class="button text-xs raw" data-button="label">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithIcon()
    {
        $button = Form::new(label: 'label', group: 'group')
            ->renderEmptyUrl(true)
            ->icon('foo')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="id" type="hidden" value="3"><button class="button text-xs" data-button="label"><i>foo</i>label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithAttr()
    {
        $button = Form::new(label: 'label', group: 'group')
            ->renderEmptyUrl(true)
            ->attr('data-foo', 'value')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="id" type="hidden" value="3"><button data-foo="value" class="button text-xs" data-button="label">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithCustomMethod()
    {
        $button = Form::new(label: 'label', group: 'group')
            ->renderEmptyUrl(true)
            ->name('delete')
            ->method('PATCH')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="_method" type="hidden" value="PATCH"><input name="id" type="hidden" value="3"><button class="button text-xs" data-button="delete">label</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodLabelIsEscaped()
    {
        $button = Form::new(label: '<p>label</p>', group: 'group')
            ->renderEmptyUrl(true)
            ->name('delete')
            ->withEntity(new Entity(['id' => 3]));
        
        $this->assertSame(
            '<form action method="POST"><input name="id" type="hidden" value="3"><button class="button text-xs" data-button="delete">&lt;p&gt;label&lt;/p&gt;</button></form>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithoutEntityReturnsEmptyString()
    {
        $button = Form::new(label: 'label', group: 'group');
        
        $this->assertSame('', $button->render(Factory::createView()));
    }    
}