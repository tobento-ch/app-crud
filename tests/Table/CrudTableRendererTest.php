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

namespace Tobento\App\Crud\Test\Table;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Table\CrudTableRenderer;
use Tobento\Service\Table\RendererInterface;
use Tobento\Service\Table\Table;

class CrudTableRendererTest extends TestCase
{
    public function testThatImplementsRendererInterface()
    {
        $this->assertInstanceOf(
            RendererInterface::class,
            new CrudTableRenderer()
        );     
    }
    
    public function testRenderMethod()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');
        
        $this->assertSame(
            '',
            $renderer->render($table)
        );
    }
    
    public function testRendersRowColumns()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');

        $table->row([
            'sku' => 'shirt',
            'title' => 'Shirt',
        ]);
        
        $this->assertSame(
            '<div class="table" role="table"><div data-table-group="items"><div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">shirt</div><div class="table-col grow-1" role="cell">Shirt</div></div></div></div></div>',
            $renderer->render($table)
        );
    }
    
    public function testRendersTableAttributes()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');
        $table->attributes(['data-foo' => 'value', 'class' => 'bar']);

        $table->row([
            'sku' => 'shirt',
            'title' => 'Shirt',
        ]);
        
        $this->assertSame(
            '<div data-foo="value" class="bar table" role="table"><div data-table-group="items"><div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">shirt</div><div class="table-col grow-1" role="cell">Shirt</div></div></div></div></div>',
            $renderer->render($table)
        );
    }    
    
    public function testRendersRowColumnsAttributes()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');

        $table->row()
              ->column(key: 'sku', text: 'Sku')
              ->column(key: 'title', text: 'Title', attributes: ['data-foo' => 'Foo', 'class' => 'bar']);
        
        $this->assertSame(
            '<div class="table" role="table"><div data-table-group="items"><div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">Sku</div><div data-foo="Foo" class="bar table-col grow-1" role="cell">Title</div></div></div></div></div>',
            $renderer->render($table)
        );
    }
    
    public function testRendersRows()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');

        $table->row([
            'sku' => 'shirt',
        ]);
        
        $table->row([
            'sku' => 'cap',
        ]);
        
        $this->assertSame(
            '<div class="table" role="table"><div data-table-group="items"><div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">shirt</div></div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">cap</div></div></div></div></div>',
            $renderer->render($table)
        );
    }
    
    public function testRendersHeading()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');

        $table->row([
            'sku' => 'shirt',
        ])->heading()->id('heading');
        
        $this->assertSame(
            '<div class="table" role="table"><div data-table-group="heading"><div><div class="table-row th" role="row"><div class="table-col grow-1" role="columnheader">shirt</div></div></div></div></div>',
            $renderer->render($table)
        );
    }
    
    public function testRendersFilters()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');

        $table->row([
            'sku' => 'shirt',
        ])->id('filters');
        
        $this->assertSame(
            '<div class="table" role="table"><div data-table-group="filters"><div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">shirt</div></div></div></div></div>',
            $renderer->render($table)
        );
    }
    
    public function testRendersWithoutEscapingHtmlIfIsHtml()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');

        $table->row([
            'intro' => '<p>intro</p>',
            'desc' => '<p>desc</p>',
        ])->html('desc');
        
        $this->assertSame(
            '<div class="table" role="table"><div data-table-group="items"><div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">&lt;p&gt;intro&lt;/p&gt;</div><div class="table-col grow-1" role="cell"><p>desc</p></div></div></div></div></div>',
            $renderer->render($table)
        );        
    }
    
    public function testRendersRowsAttributes()
    {
        $renderer = new CrudTableRenderer();
        $table = new Table('products');

        $table->row([
            'sku' => 'shirt',
        ])->attributes(['data-id' => 'foo']);
        
        $table->row([
            'sku' => 'cap',
        ]);
        
        $this->assertSame(
            '<div class="table" role="table"><div data-table-group="items"><div><div data-id="foo" class="table-row" role="row"><div class="table-col grow-1" role="cell">shirt</div></div><div class="table-row" role="row"><div class="table-col grow-1" role="cell">cap</div></div></div></div></div>',
            $renderer->render($table)
        );
    }    
}