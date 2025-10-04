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
use Tobento\App\Crud\Filter\Locale;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\Filters;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;

class LocaleTest extends TestCase
{
    public function testDefaultInterfaceMethods()
    {
        $filter = new Locale();
        
        $this->assertInstanceof(FilterInterface::class, $filter);
        $this->assertSame('locale', $filter->name());
        $this->assertSame('', $filter->fieldName());
        $this->assertSame('header', $filter->getGroup());
        $this->assertSame('footer', $filter->group('footer')->getGroup());
        $this->assertTrue($filter->isOpen());
        $this->assertFalse($filter->open(false)->isOpen());
        $this->assertTrue($filter->open(true)->isOpen());
        $this->assertSame(['locale' => 'en'], $filter->getAppliedParameters());
        $this->assertSame([], $filter->getWhereParameters());
        $this->assertSame([], $filter->getOrderByParameters());
        $this->assertSame([], $filter->getLimitParameter());
        $this->assertSame([], $filter->getBeforeCallables());
        $this->assertSame([], $filter->getAfterCallables());
        $this->assertTrue($filter->isActive());
    }
    
    public function testApplyDefaultLocale()
    {
        $filter = new Locale(name: 'foo');
        $action = new Index();
        
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN'], $action->getLocales());
        
        $filter->apply(
            input: new Input(['foo' => 'en']),
            filters: new Filters(),
            action: $action,
        );
        
        $this->assertSame(['foo' => 'en'], $filter->getAppliedParameters());
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN'], $action->getLocales());
    }
    
    public function testApplyLocale()
    {
        $filter = new Locale();
        $action = new Index()->locales(['en' => 'EN', 'de' => 'DE']);
        
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN', 'de' => 'DE'], $action->getLocales());
        
        $filter->apply(
            input: new Input(['locale' => 'de']),
            filters: new Filters(),
            action: $action,
        );
        
        $this->assertSame(['locale' => 'de'], $filter->getAppliedParameters());
        $this->assertSame('de', $action->getLocale());
        $this->assertSame(['de' => 'DE', 'en' => 'EN'], $action->getLocales());
    }
    
    public function testApplyInvalidLocaleFallsbackToDefault()
    {
        $filter = new Locale();
        $action = new Index();
        
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN'], $action->getLocales());
        
        $filter->apply(
            input: new Input(['locale' => 'de']),
            filters: new Filters(),
            action: $action,
        );
        
        $this->assertSame(['locale' => 'en'], $filter->getAppliedParameters());
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN'], $action->getLocales());
    }
    
    public function testApplyInvalidArrayLocaleFallsbackToDefault()
    {
        $filter = new Locale();
        $action = new Index();
        
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN'], $action->getLocales());
        
        $filter->apply(
            input: new Input(['locale' => []]),
            filters: new Filters(),
            action: $action,
        );
        
        $this->assertSame(['locale' => 'en'], $filter->getAppliedParameters());
        $this->assertSame('en', $action->getLocale());
        $this->assertSame(['en' => 'EN'], $action->getLocales());
    }
    
    public function testRender()
    {
        $filter = new Locale()->group('header')->label('LABEL')->description('DESC');
        
        $filter->apply(
            input: new Input(['locale' => 'de']),
            filters: new Filters(),
            action: new Index()->locales(['en' => 'EN', 'de' => 'DE']),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('LABEL', $rendered);
        $this->assertStringContainsString('DESC', $rendered);
        $this->assertStringContainsString('<span class="wrap-v"><input id="filter_locale_1" name="filter[locale]" type="radio" value="en"><label for="filter_locale_1">EN</label></span><span class="wrap-v"><input id="filter_locale_2" name="filter[locale]" type="radio" value="de" checked><label for="filter_locale_2">DE</label></span>', $rendered);
        $this->assertStringContainsString('label for="filter_locale"', $rendered);
    }
    
    public function testRenderDottedName()
    {
        $filter = new Locale(name: 'options.locale')->label('LABEL');
        
        $filter->apply(
            input: new Input(['locale' => 'de']),
            filters: new Filters(),
            action: new Index(),
        );
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringContainsString('<span class="wrap-v"><input id="filter_options_locale_1" name="filter[options][locale]" type="radio" value="en" checked><label for="filter_options_locale_1">EN</label></span>', $rendered);
        $this->assertStringContainsString('label for="filter_options_locale"', $rendered);
    }

    public function testRenderWithoutLabel()
    {
        $filter = new Locale();
        
        $rendered = $filter->render(Factory::createView());
        $this->assertStringNotContainsString('label for', $rendered);
    }
    
    public function testRendersCustomView()
    {
        $filter = new Locale()->view('custom/crud/filter');
        
        // empty as view does not exist, but we know that it is changable:
        $this->assertSame('', $filter->render(Factory::createView()));
    }
}