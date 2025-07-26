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
use Tobento\App\Crud\Button\Html;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Tag\AttributesInterface;
use Tobento\Service\View\ViewInterface;

class HtmlTest extends AbstractButton
{
    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(Html::new(group: 'group'));
    }
    
    public function testInterfaceGetterMethods()
    {
        $button = Html::new(group: 'group');
        
        $this->assertSame('', $button->getName());
        $this->assertSame('', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
        $this->assertSame(null, $button->getIcon());
    }
    
    public function testInterfaceSetterMethods()
    {
        $button = Html::new(group: 'group');
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');
        
        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());
    }
    
    public function testWithUrlMethod()
    {
        $button = Html::new(group: 'group');
        $newButton = $button->withUrl('url');
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testWithEntityMethod()
    {
        $button = Html::new(group: 'group');
        $newButton = $button->withEntity(new Entity());
        
        $this->assertFalse($button === $newButton);
    }
    
    public function testRenderMethod()
    {
        $button = Html::new(group: 'group')->html('<a href="#">Label<a/>');
        
        $this->assertSame(
            '<a href="#">Label<a/>',
            $button->render(Factory::createView())
        );
    }
    
    public function testRenderMethodUsingClosure()
    {
        $button = Html::new(group: 'group')->html(function(Html $button, ViewInterface $view): string {
            $url = $button->getUrl();
            $entity = $button->getEntity();
            $attributes = $button->getAttributes();
            return '<a href="#">Label<a/>';
        });
        
        $this->assertSame(
            '<a href="#">Label<a/>',
            $button->render(Factory::createView())
        );
    }
}