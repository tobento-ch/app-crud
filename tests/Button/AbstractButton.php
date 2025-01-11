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

namespace Tobento\App\Crud\Test\Button;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Button\ButtonInterface;
use Closure;

abstract class AbstractButton extends TestCase
{
    public function linkToTests(ButtonInterface $button)
    {
        $this->assertSame('link', $button->linkToUrl('link')->getLinkToUrl());
        $this->assertInstanceof(Closure::class, $button->linkToUrl(fn() => 'link')->getLinkToUrl());
        $this->assertSame('name', $button->linkToAction('name')->getLinkToAction());
        $this->assertSame(['name', ['id' => 5]], $button->linkToRoute('name', ['id' => 5])->getLinkToRoute());
    }
}