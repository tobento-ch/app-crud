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
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Crud\Action\Index;
use RuntimeException;

class ActionProcessExceptionTest extends TestCase
{
    public function testException()
    {
        $action = new Index();
        $e = new ActionProcessException(action: $action);
        
        $this->assertInstanceof(RuntimeException::class, $e);
        $this->assertTrue($action === $e->action());
        $this->assertSame('Processing index action failed.', $e->getMessage());
        
        $e = new ActionProcessException(action: $action, message: 'Custom');
        $this->assertSame('Custom', $e->getMessage());
    }
}