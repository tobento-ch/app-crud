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

namespace Tobento\App\Crud\Test\Filter;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Action\Index;
use Tobento\App\Crud\Filter\Checkboxes;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class CheckboxesTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = Checkboxes::new(name: 'foo');
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('foo', $filter->name());
        $this->assertSame('', $filter->fieldName());
        $this->assertSame('header', $filter->getGroup());
        $this->assertSame('footer', $filter->group('footer')->getGroup());
        $this->assertTrue($filter->isOpen());
        $this->assertFalse($filter->open(false)->isOpen());
        $this->assertTrue($filter->open(true)->isOpen());
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame([], $filter->getLimitParameter());
        $this->assertSame([], $filter->getBeforeCallables());
        $this->assertSame([], $filter->getAfterCallables());
        $this->assertFalse($filter->isActive());
    }

    public function testInvalidNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $filter = Checkboxes::new(name: 'foo bar');
    }
    
    public function testWithFieldName()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'bar');
        $this->assertSame('bar', $filter->fieldName());
    }
    
    public function testApplyValue()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => ['blue']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['in' => ['blue']]], $filter->getWhereParameters());
    }
    
    public function testApplyValueWithOptionsUsingClosure()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'sku')
            ->options(function() {
                return ['blue' => 'Blue', 'red' => 'Red'];
            });
        
        foreach($filter->getBeforeCallables() as $callable) {
            $callable->resolved($callable->callable()());
        }
        
        $filter->apply(
            input: new Input(['foo' => ['blue']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['in' => ['blue']]], $filter->getWhereParameters());
    }    
    
    public function testApplyValueIgnoresNoneValue()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => ['_none']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testApplyValueIsIngoredIfOptionDoesNotExist()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => ['green']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testApplyAppliesFieldEvenIfNotExists()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'bar')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => ['blue']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['in' => ['blue']]], $filter->getWhereParameters());
        $this->assertSame('in', $filter->getComparison());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplySkipsWhereParamsIfFieldIsNotSet()
    {
        $filter = Checkboxes::new(name: 'foo')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => ['blue']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplyWithInvalidValueDoesNotApply()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => [[]]]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplyWithDottedName()
    {
        $filter = Checkboxes::new(name: 'foo.bar', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => ['bar' => ['blue']]]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['bar' => ['blue']]], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['in' => ['blue']]], $filter->getWhereParameters());
    }
    
    public function testApplyWithDottedNameDoesNotSetAppliedParamsIfInvalid()
    {
        $filter = Checkboxes::new(name: 'foo.bar', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => [[]]]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testApplyWithInvalidComaprisonFallsbackToDefault()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'bar')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->comparison('invalid');
        
        $filter->apply(
            input: new Input(['foo' => ['blue']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['in' => ['blue']]], $filter->getWhereParameters());
        $this->assertSame('in', $filter->getComparison());
    }
    
    public function testApplyUsingAfterMethod()
    {
        $filter = Checkboxes::new(name: 'foo')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->after(function(Checkboxes $filter) {
                if (empty($filter->getSelected())) {
                    return;
                }

                $filter->setWhereParameters(['field' => ['in' => $filter->getSelected()]]);
            });
        
        $filter->apply(
            input: new Input(['foo' => ['blue']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'foo'),
            )),
        );
        
        foreach($filter->getAfterCallables() as $callable) {
            $callable($filter);
        }
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame(['field' => ['in' => ['blue']]], $filter->getWhereParameters());
    }
    
    public function testApplyWithDefinedSelected()
    {
        $filter = Checkboxes::new(name: 'foo', field: 'sku')
            ->selected(['blue'])
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['in' => ['blue']]], $filter->getWhereParameters());
    }
    
    public function testRender()
    {
        $filter = Checkboxes::new(name: 'sku', field: 'sku')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->group('header')
            ->label('LABEL')
            ->description('DESC');
        
        $filter->apply(
            input: new Input(['sku' => ['red']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('<span class="wrap-v"><input id="filter_sku_1" name="filter[sku][]" type="checkbox" value="blue"><label for="filter_sku_1">Blue</label></span><span class="wrap-v"><input id="filter_sku_2" name="filter[sku][]" type="checkbox" value="red" checked><label for="filter_sku_2">Red</label></span><input name="filter[sku][]" type="hidden" value="_none">', $rendered);
        $this->assertStringContainsString('label for="filter_sku"', $rendered);
    }
    
    public function testRenderDoesNotSetValueIfNotApplied()
    {
        $filter = Checkboxes::new(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input name="filter[sku][]" type="hidden" value="_none">', $rendered);
    }
    
    public function testRenderDottedName()
    {
        $filter = Checkboxes::new(name: 'options.color', field: 'options')->label('LABEL');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input name="filter[options][color][]" type="hidden" value="_none">', $rendered);
        $this->assertStringContainsString('label for="filter_options_color"', $rendered);
    }
    
    public function testRenderWithAttributes()
    {
        $filter = Checkboxes::new(name: 'sku', field: 'sku')
            ->options(['blue' => 'Blue'])
            ->attributes(['data-foo' => ['key' => 'val']]);
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input data-foo=\'{&quot;key&quot;:&quot;val&quot;}\' id="filter_sku_1" name="filter[sku][]" type="checkbox" value="blue">', $rendered);
    }
    
    public function testRenderWithoutLabel()
    {
        $filter = Checkboxes::new(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('label for', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = Checkboxes::new(name: 'sku', field: 'sku')->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}