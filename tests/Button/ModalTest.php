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
use Tobento\App\Crud\Button\Modal;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class ModalTest extends AbstractButton
{
    public function testImplementsButtonsAwareInterface()
    {
        $this->assertInstanceof(
            ButtonsAwareInterface::class,
            new Modal(label: 'label', group: 'group')
        );
    }

    public function testWithButtonsMethod()
    {
        $button = new Modal(label: 'label', group: 'group');
        $newButton = $button->withButtons(new Buttons());

        $this->assertFalse($button === $newButton);
    }

    public function testGetButtonsMethod()
    {
        $button = new Modal(label: 'label', group: 'group');
        $this->assertSame(0, $button->getButtons()->count());

        $button = $button->withButtons(new Buttons(new Button(label: 'bar', group: 'group')));
        $this->assertSame(1, $button->getButtons()->count());
    }

    public function testButtonsMethodIsFluent()
    {
        $button = new Modal(label: 'label', group: 'group');
        $newButton = $button->buttons(new Button(label: 'foo', group: 'group'));

        $this->assertSame($button, $newButton);
        $this->assertSame(1, $button->getButtons()->count());
    }

    public function testLinkableInterfaceMethods()
    {
        $this->linkToTests(new Modal(label: 'label', group: 'group'));
    }

    public function testInterfaceGetterMethods()
    {
        $button = new Modal(label: 'label', group: 'group');

        $this->assertSame('label', $button->getName());
        $this->assertSame('label', $button->getLabel());
        $this->assertSame('group', $button->getGroup());
        $this->assertSame('', $button->getUrl());
        $this->assertSame(null, $button->getIcon());
    }

    public function testInterfaceSetterMethods()
    {
        $button = new Modal(label: 'label', group: 'group');
        $button->name('Name')->label('Label')->group('Group')->icon('Icon');

        $this->assertSame('Name', $button->getName());
        $this->assertSame('Label', $button->getLabel());
        $this->assertSame('Group', $button->getGroup());
        $this->assertSame('Icon', $button->getIcon());
    }

    public function testWithUrlMethod()
    {
        $button = new Modal(label: 'label', group: 'group');
        $newButton = $button->withUrl('url');

        $this->assertFalse($button === $newButton);
    }

    public function testWithEntityMethod()
    {
        $button = new Modal(label: 'label', group: 'group');
        $newButton = $button->withEntity(new Entity());

        $this->assertFalse($button === $newButton);
    }

    public function testSearchableButtonsMethodIsFluent()
    {
        $button = new Modal(label: 'label', group: 'group');
        $newButton = $button->searchableButtons(false);

        $this->assertSame($button, $newButton);
    }

    public function testSearchableButtonsPlaceholderMethodIsFluent()
    {
        $button = new Modal(label: 'label', group: 'group');
        $newButton = $button->searchableButtonsPlaceholder('Search...');

        $this->assertSame($button, $newButton);
    }

    public function testConfiguresModalTraitDefaultsAndSetters()
    {
        $button = new Modal(label: 'label', group: 'group');

        $this->assertSame('Apply', $button->getModalButtonLabel());
        $this->assertSame('', $button->getModalPosition());
        $this->assertSame('modal-l', $button->getModalSize());
        $this->assertSame('modal-fade', $button->getModalAnimation());

        $button->modalButtonLabel('Save')
            ->modalPosition('bottom', 'right')
            ->modalSize('modal-m')
            ->modalAnimation('modal-scale');

        $this->assertSame('Save', $button->getModalButtonLabel());
        $this->assertSame('bottom right', $button->getModalPosition());
        $this->assertSame('modal-m', $button->getModalSize());
        $this->assertSame('modal-scale', $button->getModalAnimation());
    }

    public function testRenderWithoutButtons()
    {
        $button = new Modal(label: 'label', group: 'group')->buttons();

        $this->assertSame('', $button->render(Factory::createView()));
    }

    public function testRenderWithButtons()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')->buttons(
            new Button(label: 'foo', group: 'group'),
            new Button(label: 'bar', group: 'group'),
        );

        $searchPlaceholder = $view->etrans('Search');
        $cancel = $view->etrans('Cancel');

        $expected = '<div class="crud-button-modal-trigger" data-modal-trigger="modal-label">'
            .'<span class="button text-xs" aria-haspopup="true" aria-controls="modal-label" data-button="label">label</span>'
            .'</div>'
            .'<div class="modal crud-button-modal  modal-fade" data-modal=\'{&quot;id&quot;:&quot;modal-label&quot;}\'>'
            .'<div class="modal-background"></div>'
            .'<div class="modal-content modal-l">'
            .'<div class="modal-head">'
            .'<input name="buttons_search" type="search" class="small fit" placeholder="'.$searchPlaceholder.'">'
            .'</div>'
            .'<div class="modal-body">'
            .'<div class="modal-buttons">'
            .'<button data-button-search="" class="button text-xs" data-button="foo">foo</button>'
            .'<button data-button-search="" class="button text-xs" data-button="bar">bar</button>'
            .'</div>'
            .'</div>'
            .'<div class="modal-foot">'
            .'<span class="link modal-close">'.$cancel.'</span>'
            .'</div>'
            .'</div>'
            .'</div>';

        $this->assertSame($expected, $button->render($view));
    }

    public function testRenderMethodWithPrimary()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->primary();

        $html = $button->render($view);

        $this->assertStringContainsString(
            '<span class="button text-xs primary" aria-haspopup="true" aria-controls="modal-label" data-button="label">label</span>',
            $html
        );
    }

    public function testRenderMethodWithRaw()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->raw();

        $html = $button->render($view);

        $this->assertStringContainsString(
            '<span class="button text-xs raw" aria-haspopup="true" aria-controls="modal-label" data-button="label">label</span>',
            $html
        );
    }

    public function testRenderMethodWithIcon()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->icon('foo');

        $html = $button->render($view);

        $this->assertStringContainsString(
            '<span class="button text-xs" aria-haspopup="true" aria-controls="modal-label" data-button="label"><i>foo</i>label</span>',
            $html
        );
    }

    public function testRenderMethodWithAttr()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->attr('data-foo', 'value');

        $html = $button->render($view);

        $this->assertStringContainsString(
            '<span data-foo="value" class="button text-xs" aria-haspopup="true" aria-controls="modal-label" data-button="label">label</span>',
            $html
        );
    }

    public function testRenderMethodLabelIsEscaped()
    {
        $view = Factory::createView();

        $button = new Modal(label: '<p>label</p>', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->name('name');

        $html = $button->render($view);

        $this->assertStringContainsString(
            '<span class="button text-xs" aria-haspopup="true" aria-controls="modal-name" data-button="name">&lt;p&gt;label&lt;/p&gt;</span>',
            $html
        );
        $this->assertStringContainsString('data-modal-trigger="modal-name"', $html);
        $this->assertStringContainsString(
            'data-modal=\'{&quot;id&quot;:&quot;modal-name&quot;}\'',
            $html
        );
    }

    public function testRenderWithoutSearchableButtons()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->searchableButtons(false);

        $html = $button->render($view);

        $this->assertStringNotContainsString('<div class="modal-head">', $html);
        $this->assertStringNotContainsString('name="buttons_search"', $html);
        // no data-button-search attr set on buttons either, since that only happens
        // inside the ungrouped loop regardless of searchableButtons — verify class/name still render:
        $this->assertStringContainsString('data-button="foo"', $html);
    }

    public function testRenderWithSearchableButtonsPlaceholder()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->searchableButtonsPlaceholder('Find a button');

        $html = $button->render($view);

        $this->assertStringContainsString(
            '<input name="buttons_search" type="search" class="small fit" placeholder="Find a button">',
            $html
        );
    }

    public function testRenderWithCustomModalPositionSizeAndAnimation()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->modalPosition('bottom', 'right')
            ->modalSize('modal-m')
            ->modalAnimation('modal-scale');

        $html = $button->render($view);

        // no empty-string entry in the merge this time (position is non-empty), so single spaces:
        $this->assertStringContainsString(
            'class="modal crud-button-modal bottom right modal-scale"',
            $html
        );
        $this->assertStringContainsString('class="modal-content modal-m"', $html);
    }

    public function testRenderWithEntitySetsModalIdSuffix()
    {
        $view = Factory::createView();

        $button = new Modal(label: 'label', group: 'group')
            ->buttons(new Button(label: 'foo', group: 'group'))
            ->withEntity(new Entity(['id' => 42]));

        $html = $button->render($view);

        $this->assertStringContainsString('data-modal-trigger="modal-label42"', $html);
        $this->assertStringContainsString('aria-controls="modal-label42"', $html);
    }
}