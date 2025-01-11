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
use Tobento\App\Crud\Filter\Select;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class SelectTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = Select::new(name: 'foo');
        
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
        
        $filter = Select::new(name: 'foo bar');
    }
    
    public function testWithFieldName()
    {
        $filter = Select::new(name: 'foo', field: 'bar');
        $this->assertSame('bar', $filter->fieldName());
    }
    
    public function testApplyValue()
    {
        $filter = Select::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => 'blue']], $filter->getWhereParameters());
    }
    
    public function testApplyValueWithOptionsUsingClosure()
    {
        $filter = Select::new(name: 'foo', field: 'sku')
            ->options(function() {
                return ['blue' => 'Blue', 'red' => 'Red'];
            });
        
        foreach($filter->getBeforeCallables() as $callable) {
            $callable->resolved($callable->callable()());
        }
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => 'blue']], $filter->getWhereParameters());
    }    
    
    public function testApplyValueIgnoresNoneValue()
    {
        $filter = Select::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => 'none']),
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
        $filter = Select::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => 'green']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testApplyValueWithMultiple()
    {
        $filter = Select::new(name: 'foo', field: 'sku')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple']);
        
        $filter->apply(
            input: new Input(['foo' => ['blue', 'red']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue', 'red']], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters()); // empty as not supported! using after method
    }
    
    public function testApplyValueWithMultipleIgnoresNoneValue()
    {
        $filter = Select::new(name: 'foo', field: 'sku')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple']);
        
        $filter->apply(
            input: new Input(['foo' => ['blue', 'none']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters()); // empty as not supported! using after method
    }
    
    public function testApplyValueWithMultipleValuesWillBeIngoredIfOptionDoesNotExists()
    {
        $filter = Select::new(name: 'foo', field: 'sku')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->attributes(['multiple']);
        
        $filter->apply(
            input: new Input(['foo' => ['blue', 'green']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['blue']], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters()); // empty as not supported! using after method
    }
    
    public function testApplyAppliesFieldEvenIfNotExists()
    {
        $filter = Select::new(name: 'foo', field: 'bar')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['=' => 'blue']], $filter->getWhereParameters());
        $this->assertSame('=', $filter->getComparison());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplySkipsWhereParamsIfFieldIsNotSet()
    {
        $filter = Select::new(name: 'foo')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplyWithInvalidValueDoesNotApply()
    {
        $filter = Select::new(name: 'foo', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
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
        $filter = Select::new(name: 'foo.bar', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(['foo' => ['bar' => 'blue']]),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['bar' => 'blue']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => 'blue']], $filter->getWhereParameters());
    }
    
    public function testApplyWithDottedNameDoesNotSetAppliedParamsIfInvalid()
    {
        $filter = Select::new(name: 'foo.bar', field: 'sku')->options(['blue' => 'Blue', 'red' => 'Red']);
        
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
    
    public function testApplyWithLikeComaprison()
    {
        $filter = Select::new(name: 'foo', field: 'bar')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->comparison('like');
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['like' => '%blue%']], $filter->getWhereParameters());
        $this->assertSame('like', $filter->getComparison());
    }
    
    public function testApplyWithNotLikeComaprison()
    {
        $filter = Select::new(name: 'foo', field: 'bar')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->comparison('not like');
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['not like' => '%blue%']], $filter->getWhereParameters());
        $this->assertSame('not like', $filter->getComparison());
    }
    
    public function testApplyWithInvalidComaprisonFallsbackToDefault()
    {
        $filter = Select::new(name: 'foo', field: 'bar')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->comparison('invalid');
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['=' => 'blue']], $filter->getWhereParameters());
        $this->assertSame('=', $filter->getComparison());
    }
    
    public function testApplyUsingAfterMethod()
    {
        $filter = Select::new(name: 'foo')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->after(function(Select $filter) {
                if (!is_string($filter->getSelected())) {
                    return;
                }

                $filter->setWhereParameters(['field' => ['=' => $filter->getSelected()]]);
            });
        
        $filter->apply(
            input: new Input(['foo' => 'blue']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'foo'),
            )),
        );
        
        foreach($filter->getAfterCallables() as $callable) {
            $callable($filter);
        }
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['field' => ['=' => 'blue']], $filter->getWhereParameters());
    }

    public function testApplyWithDefinedSelected()
    {
        $filter = Select::new(name: 'foo', field: 'sku')
            ->selected('blue')
            ->options(['blue' => 'Blue', 'red' => 'Red']);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => 'blue'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => 'blue']], $filter->getWhereParameters());
    }
    
    public function testApplyWithDefinedSelectedMultiple()
    {
        $filter = Select::new(name: 'foo', field: 'sku')
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
        $this->assertSame([], $filter->getWhereParameters()); // returns empty as not supported. Use after method
    }
    
    public function testRender()
    {
        $filter = Select::new(name: 'sku', field: 'sku')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->group('header')
            ->label('LABEL')
            ->description('DESC');
        
        $filter->apply(
            input: new Input(['sku' => 'red']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('<select id="filter_sku" name="filter[sku]"><option value="none">---</option><option value="blue">Blue</option><option value="red" selected>Red</option></select>', $rendered);
        $this->assertStringContainsString('label for="filter_sku"', $rendered);
    }
    
    public function testRenderDoesNotSetValueIfNotApplied()
    {
        $filter = Select::new(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select id="filter_sku" name="filter[sku]"><option value="none">---</option></select>', $rendered);
    }
    
    public function testRenderDottedName()
    {
        $filter = Select::new(name: 'options.color', field: 'options')->label('LABEL');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select id="filter_options_color" name="filter[options][color]"><option value="none">---</option></select>', $rendered);
        $this->assertStringContainsString('label for="filter_options_color"', $rendered);
    }
    
    public function testRenderWithAttributes()
    {
        $filter = Select::new(name: 'sku', field: 'sku')
            ->attributes(['mulitple', 'data-foo' => ['key' => 'val']]);
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select mulitple data-foo=\'{&quot;key&quot;:&quot;val&quot;}\' id="filter_sku" name="filter[sku]"><option value="none">---</option></select>', $rendered);
    }
    
    public function testRenderWithoutLabel()
    {
        $filter = Select::new(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('label for', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = Select::new(name: 'sku', field: 'sku')->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}