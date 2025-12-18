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

namespace Tobento\App\Crud\Test\Html;

use Stringable;
use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Html\InputWrap;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Htmlable;

class InputWrapTest extends TestCase
{
    public function testImplementsInterfaces()
    {
        $iw = new InputWrap(input: 'foo');
        $this->assertInstanceof(Stringable::class, $iw);
        $this->assertInstanceof(Htmlable::class, $iw);
    }
    
    public function testEmptyWrapReturnsEmptyString()
    {
        $iw = new InputWrap(input: '');
        $this->assertSame('', (string)$iw);
    }
    
    public function testInputOnly()
    {
        $this->assertSame('foo', (string)new InputWrap(input: 'foo'));
        
        $this->assertSame(
            '&lt;input class=&quot;smallA&quot; type=&quot;text&quot;&gt;',
            (string)new InputWrap(input: '<input class="smallA" type="text">')
        );
        
        $this->assertSame(
            '<input class="small" type="text">',
            (string)new InputWrap(input: new HtmlString('<input class="small" type="text">'))
        );
    }
    
    public function testWithPrefix()
    {
        $this->assertSame(
            '<div class="input-wrap"><div class="prefix">prefix</div>foo</div>',
            (string)new InputWrap(input: 'foo', prefix: 'prefix')
        );
        
        $this->assertSame(
            '<div class="input-wrap"><div class="prefix">&lt;p&gt;prefix&lt;/p&gt;</div>foo</div>',
            (string)new InputWrap(input: 'foo', prefix: '<p>prefix</p>')
        );
        
        $this->assertSame(
            '<div class="input-wrap"><p>prefix</p>foo</div>',
            (string)new InputWrap(input: 'foo', prefix: new HtmlString('<p>prefix</p>'))
        );
    }
    
    public function testWithPrefixAttributes()
    {
        $this->assertSame(
            '<div class="input-wrap"><div class="bar prefix">prefix</div>foo</div>',
            (string)new InputWrap(input: 'foo', prefix: 'prefix', prefixAttributes: ['class' => 'bar'])
        );
        
        // no added as Htmlable
        $this->assertSame(
            '<div class="input-wrap"><p>prefix</p>foo</div>',
            (string)new InputWrap(input: 'foo', prefix: new HtmlString('<p>prefix</p>'), prefixAttributes: ['class' => 'bar'])
        );
    }
    
    public function testWithSuffix()
    {
        $this->assertSame(
            '<div class="input-wrap">foo<div class="suffix">suffix</div></div>',
            (string)new InputWrap(input: 'foo', suffix: 'suffix')
        );
        
        $this->assertSame(
            '<div class="input-wrap">foo<div class="suffix">&lt;p&gt;suffix&lt;/p&gt;</div></div>',
            (string)new InputWrap(input: 'foo', suffix: '<p>suffix</p>')
        );
        
        $this->assertSame(
            '<div class="input-wrap">foo<p>suffix</p></div>',
            (string)new InputWrap(input: 'foo', suffix: new HtmlString('<p>suffix</p>'))
        );
    }
    
    public function testWithSuffixAttributes()
    {
        $this->assertSame(
            '<div class="input-wrap">foo<div class="bar suffix">suffix</div></div>',
            (string)new InputWrap(input: 'foo', suffix: 'suffix', suffixAttributes: ['class' => 'bar'])
        );
        
        // no added as Htmlable
        $this->assertSame(
            '<div class="input-wrap">foo<p>suffix</p></div>',
            (string)new InputWrap(input: 'foo', suffix: new HtmlString('<p>suffix</p>'), suffixAttributes: ['class' => 'bar'])
        );
    }
    
    public function testWithPrefixAndSuffix()
    {
        $this->assertSame(
            '<div class="input-wrap"><div class="prefix">prefix</div>foo<div class="suffix">suffix</div></div>',
            (string)new InputWrap(input: 'foo', prefix: 'prefix', suffix: 'suffix')
        );
    }
}