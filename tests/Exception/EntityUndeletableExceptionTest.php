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

namespace Tobento\App\Crud\Test\Exception;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Exception\EntityUndeletableException;
use Tobento\App\Crud\Action\Delete;
use RuntimeException;

class EntityUndeletableExceptionTest extends TestCase
{
    public function testException()
    {
        $action = new Delete();
        $e = new EntityUndeletableException(id: 'foo', action: $action);
        
        $this->assertInstanceof(RuntimeException::class, $e);
        $this->assertSame('foo', $e->id());
        $this->assertTrue($action === $e->action());
        $this->assertSame('', $e->getMessage());
        
        $e = new EntityUndeletableException(id: 5, action: $action, message: 'Custom');
        $this->assertSame(5, $e->id());
        $this->assertSame('Custom', $e->getMessage());
        
        $action = new Delete();
        $action->undeletable([1], 'Reason');
        $e = new EntityUndeletableException(id: 'foo', action: $action);
        $this->assertSame('Reason', $e->getMessage());
    }
}