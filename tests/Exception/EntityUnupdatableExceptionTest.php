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
use Tobento\App\Crud\Exception\EntityUnupdatableException;
use Tobento\App\Crud\Action\Update;
use RuntimeException;

class EntityUnupdatableExceptionTest extends TestCase
{
    public function testException()
    {
        $action = Update::new();
        $e = new EntityUnupdatableException(id: 'foo', action: $action);
        
        $this->assertInstanceof(RuntimeException::class, $e);
        $this->assertSame('foo', $e->id());
        $this->assertTrue($action === $e->action());
        $this->assertSame('', $e->getMessage());
        
        $e = new EntityUnupdatableException(id: 5, action: $action, message: 'Custom');
        $this->assertSame(5, $e->id());
        $this->assertSame('Custom', $e->getMessage());
        
        $action = Update::new();
        $action->unupdatable([5], 'Reason');
        $e = new EntityUnupdatableException(id: 5, action: $action, message: '');
        $this->assertSame('Reason', $e->getMessage());
    }
}