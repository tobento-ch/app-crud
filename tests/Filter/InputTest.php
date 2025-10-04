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
use Tobento\App\Crud\Filter\Input;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input as Ip;
use Tobento\App\Crud\Test\Factory;

class InputTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = new Input(name: 'foo');
        
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
        
        $filter = new Input(name: 'foo bar');
    }
    
    public function testWithFieldName()
    {
        $filter = new Input(name: 'foo', field: 'bar');
        $this->assertSame('bar', $filter->fieldName());
    }
    
    public function testApplyAppliesFieldEvenIfNotExists()
    {
        $filter = new Input(name: 'foo', field: 'bar');
        
        $filter->apply(
            input: new Ip(['foo' => 'value']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => 'value'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['=' => 'value']], $filter->getWhereParameters());
        $this->assertSame('=', $filter->getComparison());
        $this->assertSame('text', $filter->getType());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplySkipsWhereParamsIfFieldIsNotSet()
    {
        $filter = new Input(name: 'foo');
        
        $filter->apply(
            input: new Ip(['foo' => 'value']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => 'value'], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplyWithInvalidValueDoesNotApply()
    {
        $filter = new Input(name: 'foo', field: 'sku');
        
        $filter->apply(
            input: new Ip(['foo' => []]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplyWithDottedName()
    {
        $filter = new Input(name: 'foo.bar', field: 'sku');
        
        $filter->apply(
            input: new Ip(['foo' => ['bar' => 'value']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['bar' => 'value']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => 'value']], $filter->getWhereParameters());
    }
    
    public function testApplyWithDottedNameDoesNotSetAppliedParamsIfInvalid()
    {
        $filter = new Input(name: 'foo.bar', field: 'sku');
        
        $filter->apply(
            input: new Ip(['foo' => []]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testApplyWithDottedNameDoesNotSetAppliedParamsIfInvalidValue()
    {
        $filter = new Input(name: 'foo.bar', field: 'sku');
        
        $filter->apply(
            input: new Ip(['foo' => ['bar' => []]]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testApplyWithLikeComaprison()
    {
        $filter = new Input(name: 'foo', field: 'bar')->comparison('like');
        
        $filter->apply(
            input: new Ip(['foo' => 'value']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => 'value'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['like' => '%value%']], $filter->getWhereParameters());
        $this->assertSame('like', $filter->getComparison());
    }
    
    public function testApplyWithNotLikeComaprison()
    {
        $filter = new Input(name: 'foo', field: 'bar')->comparison('not like');
        
        $filter->apply(
            input: new Ip(['foo' => 'value']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => 'value'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['not like' => '%value%']], $filter->getWhereParameters());
        $this->assertSame('not like', $filter->getComparison());
    }
    
    public function testApplyWithInvalidComaprisonFallsbackToDefault()
    {
        $filter = new Input(name: 'foo', field: 'bar')->comparison('invalid');
        
        $filter->apply(
            input: new Ip(['foo' => 'value']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => 'value'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['=' => 'value']], $filter->getWhereParameters());
        $this->assertSame('=', $filter->getComparison());
    }
    
    public function testApplyUsingAfterMethod()
    {
        $filter = new Input(name: 'foo')
            ->after(function(Input $filter) {
                if (!is_string($filter->getSearchValue())) {
                    return;
                }

                $filter->setWhereParameters(['field' => ['=' => $filter->getSearchValue()]]);
            });
        
        $filter->apply(
            input: new Ip(['foo' => 'value']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'foo'),
            )),
        );
        
        foreach($filter->getAfterCallables() as $callable) {
            $callable($filter);
        }
        
        $this->assertSame(['foo' => 'value'], $filter->getAppliedParameters());
        $this->assertSame(['field' => ['=' => 'value']], $filter->getWhereParameters());
    }
    
    public function testApplyClearsParameters()
    {
        $filter = new Input(name: 'foo.bar', field: 'sku');
        
        $filter->apply(
            input: new Ip(['foo' => ['bar' => 'value']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $filter->apply(
            input: new Ip([]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testRender()
    {
        $filter = new Input(name: 'sku', field: 'sku')->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Ip(['sku' => 'foo']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('<input id="filter_sku" name="filter[sku]" type="text" value="foo">', $rendered);
        $this->assertStringContainsString('label for="filter_sku"', $rendered);
    }
    
    public function testRenderDoesNotSetValueIfNotApplied()
    {
        $filter = new Input(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_sku" aria-label="sku" name="filter[sku]" type="text">', $rendered);
    }
    
    public function testRenderDottedName()
    {
        $filter = new Input(name: 'options.color', field: 'options')->label('LABEL');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input id="filter_options_color" name="filter[options][color]" type="text">', $rendered);
        $this->assertStringContainsString('label for="filter_options_color"', $rendered);
    }
    
    public function testRenderWithAttributes()
    {
        $filter = new Input(name: 'sku', field: 'sku')
            ->attributes(['placeholder' => 'value', 'required', 'data-foo' => ['key' => 'val']]);
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input placeholder="value" required data-foo=\'{&quot;key&quot;:&quot;val&quot;}\' id="filter_sku" aria-label="sku" name="filter[sku]" type="text">', $rendered);
    }
    
    public function testRenderWithoutLabel()
    {
        $filter = new Input(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('label for', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = new Input(name: 'sku', field: 'sku')->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}