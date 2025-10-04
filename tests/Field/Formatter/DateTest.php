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
use Tobento\App\Crud\Field\Formatter\Date;
use Tobento\App\Crud\Field;
use Tobento\Service\Dater\DateFormatter;

class DateTest extends TestCase
{
    public function testFormatIsUsed()
    {
        $date = new Date(format: 'EE, dd. MMMM yyyy');
        
        $this->assertSame(
            'Wed, 16. April 2025',
            (string)$date(value: '2025-04-16', field: new Field\Text(name: 'name'))
        );
    }
    
    public function testFormatIsDeterminedByFieldType()
    {
        $date = new Date();
        
        $this->assertSame(
            'Wed, 16. April 2025',
            (string)$date(value: '2025-04-16', field: new Field\Text(name: 'name')->type('date'))
        );
    }
    
    public function testWithDateFormatter()
    {
        $date = new Date(dateFormatter: new DateFormatter(locale: 'de_DE'));
        
        $this->assertSame(
            'So., 16. März 2025, 00:00',
            (string)$date(value: '2025-03-16', field: new Field\Text(name: 'name'))
        );
    }
    
    public function testStringValue()
    {
        $date = new Date();
        
        $this->assertSame(
            'Wed, 16. April 2025, 00:00',
            (string)$date(value: '2025-04-16', field: new Field\Text(name: 'name'))
        );
    }
    
    public function testArrayValue()
    {
        $date = new Date();
        
        $this->assertSame(
            '<span class="crud-values"><span>Wed, 16. April 2025, 00:00</span><span>Thu, 17. April 2025, 00:00</span></span>',
            (string)$date(value: ['2025-04-16', '2025-04-17'], field: new Field\Text(name: 'name'))
        );
    }
    
    public function testSkipsInvalidValue()
    {
        $date = new Date();
        
        $this->assertSame(
            '<span class="crud-values"></span>',
            (string)$date(value: [[]], field: new Field\Text(name: 'name'))
        );
    }

    public function testEmptyValue()
    {
        $date = new Date();
        
        $this->assertSame(
            '',
            (string)$date(value: '', field: new Field\Text(name: 'name'))
        );
        
        $this->assertSame(
            '',
            (string)$date(value: [], field: new Field\Text(name: 'name'))
        );
    }
}