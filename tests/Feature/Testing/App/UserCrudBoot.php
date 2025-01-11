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

namespace Tobento\App\Crud\Test\Feature\Testing\App;

use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;

class UserCrudBoot extends Boot
{
    public function boot(Crud $crud)
    {
        $crud->routeController(UserCrudController::class, localized: true);
    }
}