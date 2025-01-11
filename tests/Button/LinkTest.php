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
use Tobento\App\Crud\Button\Link;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class LinkTest extends AbstractButton
{
    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(Link::new(label: 'label', group: 'group'));
    }
    
    public function testInterfaceGetterMethods()
    {
        $button = Link::new(label: 'label', group: 'group');
        
        $this->assertSame('label', $button->getName());
        $this->assertSame('label', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
        $this->assertSame(null, $button->getIcon());
    }
    
    public function testInterfaceSetterMethods()
    {
        $button = Link::new(label: 'label', group: 'group');
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');
        
        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());
    }
    
    public function testWithUrlMethod()
    {
        $button = Link::new(label: 'label', group: 'group');
        $newButton = $button->withUrl('url');
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testWithEntityMethod()
    {
        $button = Link::new(label: 'label', group: 'group');
        $newButton = $button->withEntity(new Entity());
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testRenderMethod()
    {
        $button = Link::new(label: 'label', group: 'group')
            ->withUrl('url');
        
        $this->assertSame(
            '<a href="url" class="button text-xs" data-button="label">label</a>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithPrimary()
    {
        $button = Link::new(label: 'label', group: 'group')
            ->primary()
            ->withUrl('url');
        
        $this->assertSame(
            '<a href="url" class="button text-xs primary" data-button="label">label</a>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithRaw()
    {
        $button = Link::new(label: 'label', group: 'group')
            ->raw()
            ->withUrl('url');
        
        $this->assertSame(
            '<a href="url" class="button text-xs raw" data-button="label">label</a>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithIcon()
    {
        $button = Link::new(label: 'label', group: 'group')
            ->icon('foo')
            ->withUrl('url');
        
        $this->assertSame(
            '<a href="url" class="button text-xs" data-button="label"><i>foo</i>label</a>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodWithAttr()
    {
        $button = Link::new(label: 'label', group: 'group')
            ->attr('data-foo', 'value')
            ->withUrl('url');
        
        $this->assertSame(
            '<a data-foo="value" href="url" class="button text-xs" data-button="label">label</a>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodLabelIsEscaped()
    {
        $button = Link::new(label: '<p>label</p>', group: 'group')
            ->name('name')
            ->withUrl('url');
        
        $this->assertSame(
            '<a href="url" class="button text-xs" data-button="name">&lt;p&gt;label&lt;/p&gt;</a>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderReturnsEmptyStringIfNoHref()
    {
        $button = Link::new(label: 'label', group: 'group')
            ->withUrl('');
        
        $this->assertSame('', $button->render(Factory::createView()));
    }
}