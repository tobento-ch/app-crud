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
use Tobento\App\Crud\Filter\Options;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\Option;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\View\ViewInterface;

class OptionsTest extends TestCase
{
    protected function createRepository(): RepositoryInterface
    {
        return Factory::createStorageRepository(
            table: 'users',
            columns: [
                new Column\Id(),
                new Column\Text('sku'),
                new Column\Text('type'),
            ],
        );
    }
    
    public function testDefaultInterfaceMethods()
    {
        $filter = new Options(name: 'foo');
        
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
        $this->assertSame(1, count($filter->getBeforeCallables()));
        $this->assertSame([], $filter->getAfterCallables());
        $this->assertFalse($filter->isActive());
    }

    public function testInvalidNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $filter = new Options(name: 'foo bar');
    }
    
    public function testWithFieldName()
    {
        $filter = new Options(name: 'foo', field: 'bar');
        $this->assertSame('bar', $filter->fieldName());
    }
    
    public function testApplyValue()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => '2']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => '2'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => '2']], $filter->getWhereParameters());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyValueIgnoresInvalidValue()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => 'inexistence']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame([], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
    }
    
    public function testApplyAppliesFieldEvenIfNotExists()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'bar')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => '1']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => '1'], $filter->getAppliedParameters());
        $this->assertSame(['bar' => ['=' => '1']], $filter->getWhereParameters());
        $this->assertSame('=', $filter->getComparison());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyWithInvalidValueDoesNotApply()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => [[]]]),
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
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo.bar', field: 'sku')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => ['bar' => '2']]),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => ['bar' => '2']], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => '2']], $filter->getWhereParameters());
    }
    
    public function testApplyWithLikeComaprison()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->comparison('like')
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => '1']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => '1'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['like' => '%1%']], $filter->getWhereParameters());
        $this->assertSame('like', $filter->getComparison());
    }
    
    public function testApplyWithInvalidComaprisonFallsbackToDefault()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->comparison('invalid')
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => '2']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'foo'),
            )),
        );
        
        $this->assertSame(['foo' => '2'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => '2']], $filter->getWhereParameters());
        $this->assertSame('=', $filter->getComparison());
    }

    public function testApplyWithDefinedSelected()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->selected('2')
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => '2'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => '2']], $filter->getWhereParameters());
    }
    
    public function testApplyWithDefinedSelectedNotAppliedIfInput()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->selected('2')
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => '1']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $this->assertSame(['foo' => '1'], $filter->getAppliedParameters());
        $this->assertSame(['sku' => ['=' => '1']], $filter->getWhereParameters());
    }
    
    public function testApplyClearsParameters()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => '1']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );
        
        $filter->apply(
            input: new Input([]),
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
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->group('header')
            ->label('LABEL')
            ->description('DESC')
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $filter->apply(
            input: new Input(['foo' => '2']),
            filters: new Filters(),
            action: new Index()->setFields(new Fields(
                new Field\Text(name: 'sku'),
            )),
        );

        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('<input data-field-input name="filter[foo]" type="hidden" value="2">', $rendered);
    }
    
    public function testRenderDoesNotSetValueIfNotApplied()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input data-field-input name="filter[foo]" type="hidden" value>', $rendered);
    }
    
    public function testRenderDottedName()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'options.foo', field: 'sku')
            ->repository($repo)
            ->toOption(function(object $item, ViewInterface $view, Options $options): Option {        
                return new Option(
                    value: (string)$item->get('id'),
                    text: (string)$item->get('sku'),
                );
            });
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<input data-field-input name="filter[options][foo]" type="hidden" value>', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $repo = $this->createRepository();
        $repo->create(['sku' => 'red']);
        $repo->create(['sku' => 'blue']);
        
        $filter = new Options(name: 'foo', field: 'sku')
            ->repository($repo)
            ->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}