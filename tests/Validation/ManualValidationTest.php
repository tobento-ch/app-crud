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

namespace Tobento\App\Crud\Test\Validation;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Validation\ManualValidation;
use Tobento\Service\Collection\Collection;
use Tobento\Service\Message\Messages;
use Tobento\Service\Validation\ValidationInterface;

class ManualValidationTest extends TestCase
{
    public function testManualValidationHoldsDataCorrectly()
    {
        $errors = new Messages();
        $data = ['foo' => 'bar'];
        $valid = ['foo' => 'bar'];
        $invalid = ['baz' => 'nope'];

        $validation = new ManualValidation(
            errors: $errors,
            data: $data,
            valid: $valid,
            invalid: $invalid,
            isValid: true,
        );

        // Implements interface
        $this->assertInstanceOf(ValidationInterface::class, $validation);

        // isValid
        $this->assertTrue($validation->isValid());

        // skipped always false
        $this->assertFalse($validation->skipped());

        // errors
        $this->assertSame($errors, $validation->errors());

        // data collections
        $this->assertInstanceOf(Collection::class, $validation->data());
        $this->assertInstanceOf(Collection::class, $validation->valid());
        $this->assertInstanceOf(Collection::class, $validation->invalid());

        $this->assertEquals($data, $validation->data()->all());
        $this->assertEquals($valid, $validation->valid()->all());
        $this->assertEquals($invalid, $validation->invalid()->all());

        // rule and key always null
        $this->assertNull($validation->rule());
        $this->assertNull($validation->key());
    }
}