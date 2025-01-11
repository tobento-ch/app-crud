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
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\ModalButton;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class ModalButtonTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = ModalButton::new();
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('modal-button', $filter->name());
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
    
    public function testRender()
    {
        $filter = ModalButton::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Filter\Input::new(name: 'foo')->group('modal'),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="modal-button" data-modal-trigger="filters" class="button text-xs">Filters</div>', $rendered);
    }
    
    public function testRenderReturnsEmptyStringIfNoModalGroupFilters()
    {
        $filter = ModalButton::new();
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Filter\Input::new(name: 'foo')->group('header'),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('', $rendered);
    }
    
    public function testRenderWithSpecificNameAndLabel()
    {
        $filter = ModalButton::new(name: 'foo')->label('Show Filters');
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Filter\Input::new(name: 'foo')->group('modal'),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-filter="foo" data-modal-trigger="filters" class="button text-xs">Show Filters</div>', $rendered);
    }
    
    public function testRenderWithAttributes()
    {
        $filter = ModalButton::new(name: 'foo')->attributes(['data-foo' => 'Foo', 'class' => 'btn']);
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(
                Filter\Input::new(name: 'foo')->group('modal'),
            ),
            action: Index::new(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertSame('<div data-foo="Foo" class="btn" data-filter="foo" data-modal-trigger="filters">Filters</div>', $rendered);
    }
}