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

namespace Tobento\App\Crud\Test\Field;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Field\Option;

class OptionTest extends TestCase
{
    public function testValueMethod()
    {
        $option = new Option(value: 'foo');
        
        $this->assertSame('foo', $option->value());
    }
    
    public function testTextMethod()
    {
        $option = new Option(value: 'foo');
        $option->text('bar')->text('baz');
        
        $this->assertSame('<span>bar</span><span>baz</span>', $option->getHtml());
        $this->assertSame('<span>&lt;p&gt;foo&lt;/p&gt;</span>', new Option(value: 'foo')->text('<p>foo</p>')->getHtml());
    }
    
    public function testHtmlMethod()
    {
        $option = new Option(value: 'foo');
        $option->html('<p>bar</p>')->html('<p>baz</p>');
        
        $this->assertSame('<span><p>bar</p></span><span><p>baz</p></span>', $option->getHtml());
    }    
}