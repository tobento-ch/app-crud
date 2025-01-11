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

namespace Tobento\App\Crud\Button;

/**
 * ButtonsAwareInterface
 */
interface ButtonsAwareInterface
{
    /**
     * Sets the buttons.
     *
     * @param ButtonInterface $button
     * @return static $this
     */
    public function buttons(ButtonInterface ...$buttons): static;
    
    /**
     * Returns a new instance with the given buttons.
     *
     * @param ButtonsInterface $buttons
     * @return static
     */
    public function withButtons(ButtonsInterface $buttons): static;
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface;
}