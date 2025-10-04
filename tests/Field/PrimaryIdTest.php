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

namespace Tobento\App\Crud\Test\Field;

use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\new Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;

class PrimaryIdTest extends AbstractField
{
    public function testField()
    {
        $field = new Field\PrimaryId(name: 'name');
        $this->assertInstanceof(new Field\PrimaryId::class, $field);
        $this->assertInstanceof(new Field\Text::class, $field);
        
        $this->assertFalse($field->isCreatable());
        $this->assertFalse($field->isEditable());
        $this->assertFalse($field->isShowable());
    }
}