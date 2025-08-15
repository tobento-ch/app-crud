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
use Tobento\App\Crud\Exception\ResourceTypeNotFoundException;
use RuntimeException;

class ResourceTypeNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new ResourceTypeNotFoundException(type: 'name');
        
        $this->assertInstanceof(RuntimeException::class, $e);
        $this->assertSame('name', $e->type());
        $this->assertSame('Resource type name not found.', $e->getMessage());
        
        $e = new ResourceTypeNotFoundException(type: 'name', message: 'Custom');
        $this->assertSame('Custom', $e->getMessage());
    }
}