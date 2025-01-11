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
use Tobento\App\Crud\Exception\ActionNotFoundException;
use RuntimeException;

class ActionNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new ActionNotFoundException(actionName: 'index');
        
        $this->assertInstanceof(RuntimeException::class, $e);
        $this->assertSame('index', $e->actionName());
        $this->assertSame('Action index not found.', $e->getMessage());
        
        $e = new ActionNotFoundException(actionName: 'index', message: 'Custom');
        $this->assertSame('Custom', $e->getMessage());
    }
}