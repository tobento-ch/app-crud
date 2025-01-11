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
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\App\Crud\Action\Index;
use RuntimeException;

class EntityNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $action = Index::new();
        $e = new EntityNotFoundException(id: 'foo', action: $action);
        
        $this->assertInstanceof(RuntimeException::class, $e);
        $this->assertSame('foo', $e->id());
        $this->assertTrue($action === $e->action());
        $this->assertSame('Entity with the id foo not found', $e->getMessage());
        
        $e = new EntityNotFoundException(id: 5, action: $action, message: 'Custom');
        $this->assertSame(5, $e->id());
        $this->assertSame('Custom', $e->getMessage());
    }
}