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

namespace Tobento\App\Crud\Test\Field;

use Tobento\App\AppFactory;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;

class HtmlTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Html(name: 'name');
        $this->assertInstanceof(Field\Html::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Html(name: 'name'));
        $this->renderTests(new Field\Html(name: 'name'));
        $this->nameTests(Field\Html::class);
        $this->groupTests(Field\Html::class);
        $this->translatableTests(new Field\Html(name: 'name'));
        $this->localeTests(new Field\Html(name: 'name'));
        $this->assertFalse(new Field\Html(name: 'name')->isStorable());
        $this->assertFalse(new Field\Html(name: 'name')->isIndexable());
        $this->creatableTests(new Field\Html(name: 'name'));
        $this->editableTests(new Field\Html(name: 'name'));
        $this->entityTests(new Field\Html(name: 'name'));
        $this->validateTests(Field\Html::class);
        $this->requiredTextTests(Field\Html::class);
        $this->optionalTextTests(Field\Html::class);
        $this->infoTextTests(Field\Html::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Html::class);
        $this->processStoreTests(Field\Html::class);
        $this->processUpdateTests(Field\Html::class);
        $this->processShowTests(Field\Html::class);
    }
    
    public function testProcessRenderMethod()
    {
        $field = new Field\Html(name: 'name')->content(html: '<p>foo</p>');
        $field->processRender(field: $field, app: new AppFactory()->createApp());
        $this->assertSame('<p>foo</p>', $field->render());
    }
    
    public function testProcessRenderMethodUsingCallable()
    {
        $field = new Field\Html(name: 'name')->content(function(Field\Html $field): string {
            return '<p>foo</p>';
        });
        $field->processRender(field: $field, app: new AppFactory()->createApp());
        $this->assertSame('<p>foo</p>', $field->render());
    }
}