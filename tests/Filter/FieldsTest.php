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
use Tobento\App\Crud\Filter\Fields;
use Tobento\App\Crud\Filter\Input;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;

class FieldsTest extends TestCase
{
    public function testToFilterMethodReturnsNoneIfNoFieldsSpecified()
    {
        $this->assertSame([], Fields::new()->toFilters());
        $this->assertSame([], Fields::new()->only('sku', 'title')->toFilters());
        $this->assertSame([], Fields::new()->except('sku', 'title')->toFilters());
    }
    
    public function testToFilterMethodReturnsFilters()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame(2, count($filters));
        $this->assertInstanceof(Input::class, $filter);
        $this->assertSame('field.id', $filter?->name());
        $this->assertSame('id', $filter?->fieldName());
        $this->assertSame('field', $filter?->getGroup());
        $this->assertSame('search', $filter?->getType());
        $this->assertSame('like', $filter?->getComparison());
        $this->assertTrue($filter?->isOpen());
        $this->assertFalse($filter?->isActive());
    }
    
    public function testToFilterMethodWithOnly()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
            ))
            ->only('sku', 'unknown');
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame(1, count($filters));
        $this->assertSame('field.sku', $filter?->name());
    }
    
    public function testToFilterMethodWithExcept()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
            ))
            ->except('id', 'unknown');
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame(1, count($filters));
        $this->assertSame('field.sku', $filter?->name());
    }
    
    public function testToFilterMethodWithGroupAndOpen()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Text::new(name: 'id'),
            ))
            ->group('custom')
            ->open(false);
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame('custom', $filter?->getGroup());
        $this->assertFalse($filter?->isOpen());
    }
    
    public function testToFilterMethodResets()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Text::new(name: 'id'),
                Field\Text::new(name: 'sku'),
                Field\Text::new(name: 'title'),
            ));
                
        $this->assertSame(1, count($fields->except('title', 'sku')->toFilters()));
        $this->assertSame(2, count($fields->only('id', 'sku')->toFilters()));
        $this->assertSame(2, count($fields->except('title')->toFilters()));
        $this->assertSame(3, count($fields->only('id', 'sku', 'title')->toFilters()));
    }
    
    public function testRender()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Text::new(name: 'id', label: 'LABEL'),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input aria-label="LABEL" id="filter_field_id" name="filter[field][id]" type="search">', $rendered);
    }
    
    public function testRendersSelectElementIfRadiosField()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Radios::new(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select aria-label="Color" id="filter_field_color" name="filter[field][color]">', $rendered);
    }
    
    public function testRendersSelectElementIfSelectFieldNotMultiple()
    {
        $fields = Fields::new()
            ->fields(new Field\Fields(
                Field\Select::new(name: 'color', label: 'LABEL')->options(['blue' => 'Blue', 'red' => 'Red']),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select aria-label="LABEL" id="filter_field_color" name="filter[field][color]">', $rendered);
    }    
}