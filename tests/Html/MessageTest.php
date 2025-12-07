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
use Tobento\App\Crud\Html\Message;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Htmlable;

class MessageTest extends TestCase
{
    public function testImplementsInterfaces()
    {
        $msg = new Message();
        $this->assertInstanceof(Stringable::class, $msg);
        $this->assertInstanceof(Htmlable::class, $msg);
    }
    
    public function testEmptyMessageReturnsEmptyString()
    {
        $msg = new Message();
        $this->assertSame('', (string)$msg);
    }
    
    public function testTitle()
    {
        $msg = new Message(title: 'Title <p>lorem</p>');
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body"><h3 class="title text-s">Title &lt;p&gt;lorem&lt;/p&gt;</h3></div></div>',
            (string)$msg
        );
    }
    
    public function testTitleWithHtmlString()
    {
        $msg = new Message(title: new HtmlString('Title <p>lorem</p>'));
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body">Title <p>lorem</p></div></div>',
            (string)$msg
        );
    }
    
    public function testText()
    {
        $msg = new Message(text: 'Text <p>lorem</p>');
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body"><p class="text-xs">Text &lt;p&gt;lorem&lt;/p&gt;</p></div></div>',
            (string)$msg
        );
    }
    
    public function testTextWithHtmlString()
    {
        $msg = new Message(text: new HtmlString('Title <p>lorem</p>'));
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body">Title <p>lorem</p></div></div>',
            (string)$msg
        );
    }
    
    public function testList()
    {
        $msg = new Message(list: ['foo', 'bar']);
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body"><ul class="bulleted text-xs"><li>foo</li><li>bar</li></ul></div></div>',
            (string)$msg
        );
    }
    
    public function testKeyedList()
    {
        $msg = new Message(keyedList: ['foo' => 'Foo', 'bar' => 'Bar']);
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body"><div class="text-xs"><div class="title">foo</div><div class="mb-s">Foo</div><div class="title">bar</div><div class="mb-s">Bar</div></div></div></div>',
            (string)$msg
        );
    }
    
    public function testSummary()
    {
        $msg = new Message(text: 'Text', summary: 'Summary <p>lorem</p>');
        $this->assertSame(
            '<details><summary class="link my-xs">Summary &lt;p&gt;lorem&lt;/p&gt;</summary><div class="crud-message mt-s"><div class="crud-message-body"><p class="text-xs">Text</p></div></div></details>',
            (string)$msg
        );
    }
    
    public function testSummaryWithOpen()
    {
        $msg = new Message(text: 'Text', summary: 'Summary', open: true);
        $this->assertSame(
            '<details open><summary class="link my-xs">Summary</summary><div class="crud-message mt-s"><div class="crud-message-body"><p class="text-xs">Text</p></div></div></details>',
            (string)$msg
        );
    }
    
    public function testIcon()
    {
        $msg = new Message(text: 'Text', icon: '<svg></svg>');
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-icon">&lt;svg&gt;&lt;/svg&gt;</div><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testIconWithHtmlString()
    {
        $msg = new Message(text: 'Text', icon: new HtmlString('<svg></svg>'));
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-icon"><svg></svg></div><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testSuccess()
    {
        $msg = new Message(text: 'Text', success: true);
        $this->assertSame(
            '<div class="crud-message alert success"><div class="crud-message-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg></div><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testWarning()
    {
        $msg = new Message(text: 'Text', warning: true);
        $this->assertSame(
            '<div class="crud-message alert warning"><div class="crud-message-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l6 0" /></svg></div><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testDanger()
    {
        $msg = new Message(text: 'Text', danger: true);
        $this->assertSame(
            '<div class="crud-message alert danger"><div class="crud-message-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg></div><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testInfo()
    {
        $msg = new Message(text: 'Text', info: true);
        $this->assertSame(
            '<div class="crud-message alert info"><div class="crud-message-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg></div><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testIconWithAlertDisplaysIcon()
    {
        $msg = new Message(text: 'Text', icon: new HtmlString('<svg></svg>'), success: true);
        $this->assertSame(
            '<div class="crud-message alert success"><div class="crud-message-icon"><svg></svg></div><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testAttributes()
    {
        $msg = new Message(text: 'Text', attributes: ['class' => 'foo']);
        $this->assertSame(
            '<div class="foo crud-message"><div class="crud-message-body"><p class="text-xs">Text</p></div></div>',
            (string)$msg
        );
    }
    
    public function testDisplayAsField()
    {
        $msg = new Message(text: 'Text', displayAsField: true);
        $this->assertSame(
            '<div class="field field-crud"><div class="field-label"></div><div class="field-body"><div class="crud-message"><div class="crud-message-body"><p class="text-xs">Text</p></div></div></div></div>',
            (string)$msg
        );
    }
    
    public function testListMethodWithAttributes()
    {
        $msg = new Message(text: 'Text')->list(items: ['foo', 'bar'], attributes: ['class' => 'numbered']);
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body"><p class="text-xs">Text</p><ul class="numbered"><li>foo</li><li>bar</li></ul></div></div>',
            (string)$msg
        );
    }
    
    public function testKeyedListMethodWithAttributes()
    {
        $msg = new Message(text: 'Text')->keyedList(items: ['foo' => 'Foo'], attributes: ['class' => 'my-m']);
        $this->assertSame(
            '<div class="crud-message"><div class="crud-message-body"><p class="text-xs">Text</p><div class="my-m"><div class="title">foo</div><div class="mb-s">Foo</div></div></div></div>',
            (string)$msg
        );
    }
    
    public function testIsEmptyMethod()
    {
        $this->assertTrue(new Message()->isEmpty());
        $this->assertFalse(new Message(text: 'Text')->isEmpty());
    }
}