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

namespace Tobento\App\Translation;

use Tobento\App\Crud\Test\Factory;

if (!function_exists(__NAMESPACE__.'\trans')) {
    function trans(string $message, array $parameters = [], null|string $locale = null): string {
        return Factory::createTranslator()->trans(
            message: $message,
            parameters: $parameters,
            locale: $locale,
        );
    }
}