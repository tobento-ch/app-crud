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

namespace Tobento\App\Crud\Url;

use Closure;

/**
 * Linkable
 */
interface Linkable
{
    /**
     * Link to url.
     *
     * @param string|Closure $url
     * @return static $this
     */
    public function linkToUrl(string|Closure $url): static;
    
    /**
     * Returns the link to url.
     *
     * @return null|string|Closure
     */
    public function getLinkToUrl(): null|string|Closure;
    
    /**
     * Link to action.
     *
     * @param string $name
     * @return static
     */
    public function linkToAction(string $name): static;
    
    /**
     * Returns the link to action.
     *
     * @return null|string
     */
    public function getLinkToAction(): null|string;
    
    /**
     * Link to route.
     *
     * @param string $name
     * @param array|Closure $parameters
     * @return static
     */
    public function linkToRoute(string $name, array|Closure $parameters = []): static;
    
    /**
     * Returns the link to route.
     *
     * @return null|array
     */
    public function getLinkToRoute(): null|array;
}