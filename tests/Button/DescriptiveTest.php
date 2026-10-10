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

use Tobento\App\Crud\Button\Button;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button\ButtonsAwareInterface;
use Tobento\App\Crud\Button\ButtonsInterface;
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

    public function testImplementsButtonsAwareInterface()
    {
        $this->assertInstanceof(
            ButtonsAwareInterface::class,
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

    public function testGetButtonsMethodContainsInnerButton()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $button = new Descriptive(button: $inner);

        $this->assertInstanceof(ButtonsInterface::class, $button->getButtons());
        $this->assertSame($inner, $button->getButtons()->first());
    }

    public function testButtonsMethodReplacesButtons()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $other = new Button(label: 'bar', group: 'group');
        $button = new Descriptive(button: $inner);

        $result = $button->buttons($other);

        $this->assertSame($button, $result);
        $this->assertSame($other, $button->getButtons()->first());
        $this->assertSame($other, $button->getButton());
    }

    public function testButtonsMethodWithoutButtonsKeepsInnerButton()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $button = new Descriptive(button: $inner);

        $button->buttons();

        $this->assertNull($button->getButtons()->first());
        $this->assertSame($inner, $button->getButton());
    }

    public function testWithButtonsMethodReturnsNewInstance()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $other = new Button(label: 'bar', group: 'group');
        $button = new Descriptive(button: $inner);

        $new = $button->withButtons(new Buttons($other));

        $this->assertFalse($button === $new);
        $this->assertSame($other, $new->getButton());
        $this->assertSame($other, $new->getButtons()->first());
    }

    public function testWithButtonsMethodKeepsOriginalUnchanged()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $button = new Descriptive(button: $inner);

        $button->withButtons(new Buttons(new Button(label: 'bar', group: 'group')));

        $this->assertSame($inner, $button->getButton());
        $this->assertSame($inner, $button->getButtons()->first());
    }

    public function testWithButtonsMethodUsesFirstButtonAsInnerButton()
    {
        $first = new Button(label: 'first', group: 'group');
        $second = new Button(label: 'second', group: 'group');
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));

        $new = $button->withButtons(new Buttons($first, $second));

        $this->assertSame($first, $new->getButton());
    }

    public function testWithButtonsMethodWithEmptyButtonsKeepsPreviousInnerButton()
    {
        $inner = new Button(label: 'foo', group: 'group');
        $button = new Descriptive(button: $inner);

        $new = $button->withButtons(new Buttons());

        $this->assertSame($inner, $new->getButton());
        $this->assertNull($new->getButtons()->first());
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
        $inner = new Button(label: 'foo', group: 'group')->primary();

        $button = new Descriptive(button: $inner, description: 'desc');

        $html = $button->render(Factory::createView());

        $this->assertStringContainsString(
            '<button class="button text-xs primary" data-button="foo">foo</button>',
            $html
        );
    }

    public function testRenderUsesButtonsSetByWithButtons()
    {
        $other = new Button(label: 'bar', group: 'group');
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'))
            ->withButtons(new Buttons($other));

        $this->assertSame(
            $other->render(Factory::createView()),
            $button->render(Factory::createView())
        );
    }

    public function testRenderWithDescriptionUsesButtonsSetByWithButtons()
    {
        $other = new Button(label: 'bar', group: 'group');
        $button = new Descriptive(
            button: new Button(label: 'foo', group: 'group'),
            description: 'Some description',
        )->withButtons(new Buttons($other));

        $html = $button->render(Factory::createView());

        $this->assertStringContainsString(
            '<div class="descriptive-btn">'.$other->render(Factory::createView()).'</div>',
            $html
        );
        $this->assertStringContainsString('<div class="descriptive-desc">Some description</div>', $html);
        $this->assertStringNotContainsString('data-button="foo"', $html);
    }

    public function testRenderUsesButtonSetByButtonsMethod()
    {
        $other = new Button(label: 'bar', group: 'group');
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'));
        $button->buttons($other);

        $this->assertSame(
            $other->render(Factory::createView()),
            $button->render(Factory::createView())
        );
    }

    public function testRenderReturnsEmptyStringIfNoButtons()
    {
        $button = new Descriptive(button: new Button(label: 'foo', group: 'group'))
            ->withButtons(new Buttons());

        $this->assertSame('', $button->render(Factory::createView()));
    }

    public function testRenderReturnsEmptyStringIfNoButtonsEvenWithDescription()
    {
        $button = new Descriptive(
            button: new Button(label: 'foo', group: 'group'),
            description: 'Some description',
        )->withButtons(new Buttons());

        $this->assertSame('', $button->render(Factory::createView()));
    }
}