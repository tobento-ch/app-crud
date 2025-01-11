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
use Tobento\App\Crud\Filter\Menu;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class MenuTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = Menu::new(name: 'foo');
        
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
        
        $filter = Menu::new(name: 'foo bar');
    }
    
    public function testWithFieldName()
    {
        $filter = Menu::new(name: 'foo', field: 'bar');
        $this->assertSame('bar', $filter->fieldName());
    }
    
    public function testApplyValue()
    {
        $filter = Menu::new(name: 'foo', field: 'sku')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ]);
        
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
    
    public function testApplyValueWithItemsUsingClosure()
    {
        $filter = Menu::new(name: 'foo', field: 'sku')
            ->items(function() {
                return [
                    ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                    ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
                ];
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
        $filter = Menu::new(name: 'foo', field: 'sku')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ]);
        
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
    
    public function testApplyValueIsIngoredIfItemDoesNotExist()
    {
        $filter = Menu::new(name: 'foo', field: 'sku')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ]);
        
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
    
    public function testApplyAppliesFieldEvenIfNotExists()
    {
        $filter = Menu::new(name: 'foo', field: 'bar')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ]);
        
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
        $filter = Menu::new(name: 'foo')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ]);
        
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
    
    public function AtestApplyWithInvalidValueDoesNotApply()
    {
        $filter = Menu::new(name: 'foo', field: 'sku')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ]);
        
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
    
    public function testApplyWithLikeComaprison()
    {
        $filter = Menu::new(name: 'foo', field: 'bar')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ])
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
        $filter = Menu::new(name: 'foo', field: 'bar')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ])
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
        $filter = Menu::new(name: 'foo', field: 'bar')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ])
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
        $filter = Menu::new(name: 'foo')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ])
            ->after(function(Menu $filter) {
                if (!is_string($filter->getActive())) {
                    return;
                }

                $filter->setWhereParameters(['field' => ['=' => $filter->getActive()]]);
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
    
    public function testRender()
    {
        $filter = Menu::new(name: 'foo', field: 'sku')
            ->items([
                ['id' => 'blue', 'name' => 'Blue', 'parent' => null],
                ['id' => 'red', 'name' => 'Red', 'parent' => 'blue'],
            ])
            ->group('header')
            ->label('LABEL')
            ->description('DESC');
        
        $filter->apply(
            input: new Input(['foo' => 'red']),
            filters: new Filters(),
            action: Index::new()->setFields(new Fields(
                Field\Text::new(name: 'sku'),
            )),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('<ul class="menu-v spaced"><li><a href="?filter[foo]=none">---</a></li><li><a href="?filter[foo]=blue">Blue</a><ul class="active"><li class="active"><a class="active" href="?filter[foo]=red">Red</a></li></ul></li></ul>', $rendered);
        $this->assertStringContainsString('label for="filter_foo"', $rendered);
    }
    
    public function testRenderDoesNotSetValueIfNotApplied()
    {
        $filter = Menu::new(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<ul class="menu-v spaced"><li><a href="?filter[sku]=none">---</a></li></ul>', $rendered);
    }
    
    public function testRenderWithoutLabel()
    {
        $filter = Menu::new(name: 'sku', field: 'sku');
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('label for', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = Menu::new(name: 'sku', field: 'sku')->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}