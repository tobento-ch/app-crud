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
use Tobento\App\Crud\Filter\FieldsSortOrder;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Action\Index;
use Tobento\App\Crud\Test\Factory;

class FieldsSortOrderTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = new FieldsSortOrder();
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('sort', $filter->name());
        $this->assertSame('', $filter->fieldName());
        $this->assertSame('heading', $filter->getGroup());
        $this->assertSame('footer', $filter->group('footer')->getGroup());
        $this->assertTrue($filter->isOpen());
        $this->assertFalse($filter->open(false)->isOpen());
        $this->assertTrue($filter->open(true)->isOpen());
        $this->assertSame(['sort' => []], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame([], $filter->getLimitParameter());
        $this->assertSame([], $filter->getBeforeCallables());
        $this->assertSame([], $filter->getAfterCallables());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplySort()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'asc', 'title' => 'desc']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc', 'title' => 'desc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc', 'title' => 'desc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertSame('desc', $filter->getValueFor('title'));
        $this->assertSame(null, $filter->getValueFor('foo'));
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplySortSkipsInvalidField()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'asc', 'invalid' => 'desc']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertSame(null, $filter->getValueFor('invalid'));
    }
    
    public function testApplySortSkipsInvalidFieldAsArray()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'asc', []]]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertSame(null, $filter->getValueFor('invalid'));
    }
    
    public function testApplySortSkipsInvalidSortValue()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'asc', 'title' => 'invalid']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertSame(null, $filter->getValueFor('invalid'));
    }
    
    public function testApplySortSkipsInvalidSortValueAsArray()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'asc', 'title' => []]]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertSame(null, $filter->getValueFor('invalid'));
    }
    
    public function testApplySortSkipsInvalidValue()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => 'invalid']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
            )),
        );
        
        $this->assertSame(['sort' => []], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertFalse($filter->isActive());
    }
    
    public function testApplyResortSetsAsc()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['resort' => 'sku']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
    }
    
    public function testApplyResortSetsDescIfAscBefore()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'asc'], 'resort' => 'sku']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'desc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'desc'], $filter->getOrderByParameters());
        $this->assertSame('desc', $filter->getValueFor('sku'));
    }
    
    public function testApplyResortRemovesIfDescBefore()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'desc'], 'resort' => 'sku']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['sort' => []], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame(null, $filter->getValueFor('sku'));
    }
    
    public function testApplyResortIngoresInvalid()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['resort' => 'invalid']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['sort' => []], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame(null, $filter->getValueFor('sku'));
    }
    
    public function testApplyWithDefault()
    {
        $filter = new FieldsSortOrder()->addDefault(name: 'sku', value: 'asc');
        
        $filter->apply(
            input: new Input([]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyWithDefaultNotAppliedIfInput()
    {
        $filter = new FieldsSortOrder()->addDefault(name: 'sku', value: 'asc');
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'desc']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'desc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'desc'], $filter->getOrderByParameters());
        $this->assertSame('desc', $filter->getValueFor('sku'));
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyWithActive()
    {
        $filter = new FieldsSortOrder()->addActive(name: 'sku', value: 'asc');
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'desc']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyWithActiveGetsAppliedAfterApply()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'desc']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );
        
        $filter->addActive(name: 'sku', value: 'asc');
        
        $this->assertSame(['sort' => ['sku' => 'asc']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => 'asc'], $filter->getOrderByParameters());
        $this->assertSame('asc', $filter->getValueFor('sku'));
        $this->assertTrue($filter->isActive());
    }    
    
    public function testApplyClearsSorted()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(['sort' => ['sku' => 'asc', 'title' => 'desc']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );
        
        $filter->apply(
            input: new Input([]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            )),
        );        
        
        $this->assertSame(['sort' => []], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertFalse($filter->isActive());
    }
    
    public function testIsSortable()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );

        $this->assertTrue($filter->isSortable('id'));
        $this->assertTrue($filter->isSortable('sku'));
        $this->assertFalse($filter->isSortable('foo'));
    }
    
    public function testRender()
    {
        $filter = new FieldsSortOrder();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            )),
        );
        
        // does nothing render!
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('', $rendered);
    }
}