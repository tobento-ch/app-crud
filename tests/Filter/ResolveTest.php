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

namespace Tobento\App\Crud\Test\Filter;

use PHPUnit\Framework\TestCase;
use Tobento\App\Crud\Filter\Resolve;

class ResolveTest extends TestCase
{
    public function testResolve()
    {
        $callable = function () {
            return [];
        };
        
        $resolve = new Resolve(callable: $callable, resolved: null);
        $this->assertSame($callable, $resolve->callable());
    }
}