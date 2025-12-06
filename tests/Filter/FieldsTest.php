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
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\Fields;
use Tobento\App\Crud\Filter\Input;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\View\ViewInterface;

class FieldsTest extends TestCase
{
    protected function createRepository(): RepositoryInterface
    {
        return Factory::createStorageRepository(
            table: 'users',
            columns: [
                new Column\Id(),
                new Column\Text('name'),
                new Column\Text('type'),
            ],
        );
    }
    
    public function testToFilterMethodReturnsNoneIfNoFieldsSpecified()
    {
        $this->assertSame([], new Fields()->toFilters());
        $this->assertSame([], new Fields()->only('sku', 'title')->toFilters());
        $this->assertSame([], new Fields()->except('sku', 'title')->toFilters());
    }
    
    public function testToFilterMethodReturnsFilters()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
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
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            ))
            ->only('sku', 'unknown');
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame(1, count($filters));
        $this->assertSame('field.sku', $filter?->name());
    }
    
    public function testToFilterMethodWithExcept()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
            ))
            ->except('id', 'unknown');
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame(1, count($filters));
        $this->assertSame('field.sku', $filter?->name());
    }
    
    public function testToFilterMethodWithGroupAndOpen()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Text(name: 'id'),
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
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Text(name: 'id'),
                new Field\Text(name: 'sku'),
                new Field\Text(name: 'title'),
            ));
                
        $this->assertSame(1, count($fields->except('title', 'sku')->toFilters()));
        $this->assertSame(2, count($fields->only('id', 'sku')->toFilters()));
        $this->assertSame(2, count($fields->except('title')->toFilters()));
        $this->assertSame(3, count($fields->only('id', 'sku', 'title')->toFilters()));
    }
    
    public function testRender()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Text(name: 'id', label: 'LABEL'),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input aria-label="LABEL" id="filter_field_id" name="filter[field][id]" type="search">', $rendered);
    }
    
    public function testRendersSelectElementIfRadiosField()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Radios(name: 'color')->options(['blue' => 'Blue', 'red' => 'Red']),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select aria-label="Color" id="filter_field_color" name="filter[field][color]">', $rendered);
    }
    
    public function testRendersSelectElementIfSelectFieldNotMultiple()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Select(name: 'color', label: 'LABEL')->options(['blue' => 'Blue', 'red' => 'Red']),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select aria-label="LABEL" id="filter_field_color" name="filter[field][color]">', $rendered);
    }
    
    public function testUsesContainsComparisonIfSelectFieldMultiple()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Select(name: 'color', label: 'LABEL')
                    ->attributes(['multiple'])
                    ->options(['blue' => 'Blue', 'red' => 'Red']),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame('contains', $filter->getComparison());
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select aria-label="LABEL" id="filter_field_color" name="filter[field][color]">', $rendered);
    }
    
    public function testUsesContainsComparisonIfCheckboxesField()
    {
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Checkboxes(name: 'color', label: 'LABEL')
                    ->options(['blue' => 'Blue', 'red' => 'Red']),
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertSame('contains', $filter->getComparison());
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<select aria-label="LABEL" id="filter_field_color" name="filter[field][color]">', $rendered);
    }
    
    public function testRendersOptionsFilterIfSingleOptionsField()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'red']);
        $repo->create(['name' => 'blue']);
        
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\SingleOptions(name: 'name')
                    ->repository($repo)
                    ->toOption(function(object $item, ViewInterface $view, Field\SingleOptions $options): Field\Option {        
                        return new Field\Option(
                            value: (string)$item->get('id'),
                            text: (string)$item->get('name'),
                        );
                    })
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertInstanceof(Filter\Options::class, $filter);
        $this->assertSame('=', $filter->getComparison());
    }
    
    public function testRendersOptionsFilterIfOptionsField()
    {
        $repo = $this->createRepository();
        $repo->create(['name' => 'red']);
        $repo->create(['name' => 'blue']);
        
        $fields = new Fields()
            ->fields(new Field\Fields(
                new Field\Options(name: 'name')
                    ->repository($repo)
                    ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
                        return new Field\Option(
                            value: (string)$item->get('id'),
                            text: (string)$item->get('name'),
                        );
                    })
            ));
        
        $filters = $fields->toFilters();
        $filter = $filters[0] ?? null;
        
        $this->assertInstanceof(Filter\Options::class, $filter);
        $this->assertSame('contains', $filter->getComparison());
    }
}