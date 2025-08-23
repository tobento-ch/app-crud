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

namespace Tobento\App\Crud\Test\Field\Formatter;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Formatter\CssClass;
use Tobento\App\Crud\Field\Formatter\Date;
use Tobento\App\Crud\Field\Formatter\Formatters;
use Tobento\App\Crud\Field;

class FormattersTest extends TestCase
{
    public function testFormats()
    {
        $formatters = new Formatters(
            new Date(),
            new CssClass('text-700'),
        );
        
        $this->assertSame(
            '<span class="text-700">Wed, 16. April 2025, 00:00</span>',
            (string)$formatters(value: '2025-04-16', field: Field\Text::new(name: 'name'))
        );
    }
    
    public function testWithoutFormatters()
    {
        $formatters = new Formatters();
        
        $this->assertSame(
            'foo',
            $formatters(value: 'foo', field: Field\Text::new(name: 'name'))
        );
        
        $this->assertSame(
            ['foo'],
            $formatters(value: ['foo'], field: Field\Text::new(name: 'name'))
        );
    }
}