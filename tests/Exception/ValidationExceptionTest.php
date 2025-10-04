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
use Tobento\App\Crud\Exception\ValidationException;
use Tobento\App\Crud\Action\Index;
use Tobento\Service\Validation\Validation;
use Tobento\Service\Validation\Rule;

class ValidationExceptionTest extends TestCase
{
    public function testException()
    {
        $validation = new Validation(rule: new Rule\Same(), value: 'value', key: 'password');
        $action = new Index();
        $e = new ValidationException(validation: $validation, action: $action);
        
        $this->assertInstanceof(ActionProcessException::class, $e);
        $this->assertTrue($validation === $e->validation());
        $this->assertTrue($action === $e->action());
        $this->assertSame(null, $e->redirectActionName());
        $this->assertSame('The password and [0] must match.', $e->getMessage());
        
        $e = new ValidationException(
            validation: $validation,
            action: $action,
            redirectActionName: 'index',
            message: 'custom',
        );
        
        $this->assertSame('index', $e->redirectActionName());
        $this->assertSame('custom', $e->getMessage());
    }
}