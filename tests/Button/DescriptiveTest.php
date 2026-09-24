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
use Tobento\App\Crud\Button\Descriptive;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Support\HtmlString;

class DescriptiveTest extends AbstractButton
{
    public function testImplementsButtonInterface()
    {
        $this->assertInstanceof(
            ButtonInterface::class,
            new Descriptive(button: new Button(label: 'foo', group: 'group'))
        );
    }

    public function testConstructorCopiesLabelGroupIconNameFromInnerButton()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $inner->icon('inner-icon');

        $button = new Descriptive(button: $inner);

        $this->assertSame('foo', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('inner-icon', $button->getIcon());
        $this->assertSame($inner->getName(), $button->getName());
    }

    public function testGetButtonMethod()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $button = new Descriptive(button: $inner);

        $this->assertSame($inner, $button->getButton());
    }

    public function testGetDescMethodDefault()
    {
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));

        $this->assertSame('', $button->getDesc());
    }

    public function testGetDescMethodWithValue()
    {
        $button = new Descriptive(
            button: new Button(label: 'foo', group: 'group'),
            description: 'Some description',
        );

        $this->assertSame('Some description', $button->getDesc());
    }

    public function testGetVisualGroupMethodDefault()
    {
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));

        $this->assertSame(null, $button->getVisualGroup());
    }

    public function testGetVisualGroupMethodWithValue()
    {
        $button = new Descriptive(
            button: new Button(label: 'foo', group: 'group'),
            visualGroup: 'General',
        );

        $this->assertSame('General', $button->getVisualGroup());
    }

    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(new Descriptive(button: new Button(label: 'foo', group: 'group')));
    }

    public function testInterfaceGetterMethods()
    {
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));

        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
    }

    public function testInterfaceSetterMethodsDoNotAffectRenderedOutput()
    {
        // AbstractButton setters still work as plain state mutators...
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');

        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());

        // ...but render() only ever delegates to the inner button, ignoring all of the above.
        $this->assertSame(
            '<button class="button text-xs" data-button="foo">foo</button>',
            $button->render(Factory::createView())
        );
    }

    public function testWithUrlMethod()
    {
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));
        $newButton = $button->withUrl('url');

        $this->assertFalse($button === $newButton);
    }

    public function testWithEntityMethod()
    {
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));
        $newButton = $button->withEntity(new Entity());

        $this->assertFalse($button === $newButton);
    }

    public function testRenderWithoutDescriptionPassesThroughInnerButton()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $button = new Descriptive(button: $inner);

        $this->assertSame(
            $inner->render(Factory::createView()),
            $button->render(Factory::createView())
        );
    }

    public function testRenderWithoutDescriptionIgnoresOwnAttrPrimaryRaw()
    {
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'))
            ->primary()
            ->raw()
            ->attr('data-foo', 'value');

        $this->assertSame(
            '<button class="button text-xs" data-button="foo">foo</button>',
            $button->render(Factory::createView())
        );
    }

    public function testRenderWithDescription()
    {
        $button = new Descriptive(
            button: new Button(label: 'foo', group: 'group'),
            description: 'Some description',
        );

        $this->assertSame(
            '<div class="crud-btn-descriptive" data-button="">'
                .'<div class="descriptive-btn"><button class="button text-xs" data-button="foo">foo</button></div>'
                .'<div class="descriptive-desc">Some description</div>'
                .'</div>',
            $button->render(Factory::createView())
        );
    }

    public function testRenderWithDescriptionEscapesDescription()
    {
        $button = new Descriptive(
            button: new Button(label: 'foo', group: 'group'),
            description: '<b>desc</b> & "quoted"',
        );

        $html = $button->render(Factory::createView());

        $this->assertStringContainsString(
            '<div class="descriptive-desc">&lt;b&gt;desc&lt;/b&gt; &amp; &quot;quoted&quot;</div>',
            $html
        );
    }
    
    public function testRenderWithDescriptionUsingStringable()
    {
        $button = new Descriptive(
            button: new Button(label: 'foo', group: 'group'),
            description: new HtmlString('<p>Some description</p>'),
        );

        $this->assertSame(
            '<div class="crud-btn-descriptive" data-button="">'
                .'<div class="descriptive-btn"><button class="button text-xs" data-button="foo">foo</button></div>'
                .'<div class="descriptive-desc"><p>Some description</p></div>'
                .'</div>',
            $button->render(Factory::createView())
        );
    }

    public function testRenderWithDescriptionAndPrimaryRawAttrOnInnerButtonStillApply()
    {
        // Since Descriptive delegates rendering to the inner button, changes made
        // directly on the inner button (not on the Descriptive wrapper) DO show up.
        $inner = (new Button(label: 'foo', group: 'group'))->primary();

        $button = new Descriptive(button: $inner, description: 'desc');

        $html = $button->render(Factory::createView());

        $this->assertStringContainsString(
            '<button class="button text-xs primary" data-button="foo">foo</button>',
            $html
        );
    }
}