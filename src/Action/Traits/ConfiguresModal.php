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

namespace Tobento\App\Crud\Action\Traits;

/**
 * Provides modal configuration options for bulk actions,
 * including button label, position, and size.
 */
trait ConfiguresModal
{
    /**
     * The label shown on the modal's primary action button.
     */
    protected string $modalButtonLabel = 'Apply';

    /**
     * Modal position classes (e.g. ["bottom", "right"]).
     */
    protected array $modalPosition = [];

    /**
     * Modal size class (e.g. "modal-m").
     */
    protected string $modalSize = 'modal-l';

    /**
     * Modal animation class (e.g. "modal-scale", "modal-fade", "modal-swing")
     */
    protected string $modalAnimation = 'modal-fade';
    
    /**
     * Sets the label for the modal's primary button.
     *
     * @param string $label
     * @return static
     */
    public function modalButtonLabel(string $label): static
    {
        $this->modalButtonLabel = $label;
        return $this;
    }

    /**
     * Returns the label for the modal's primary button.
     *
     * @return string
     */
    public function getModalButtonLabel(): string
    {
        return $this->modalButtonLabel;
    }

    /**
     * Sets one or more modal position classes.
     * Examples: "center", "top", "bottom", "left", "right".
     *
     * @param string ...$positions
     * @return static
     */
    public function modalPosition(string ...$positions): static
    {
        $this->modalPosition = $positions ?: [];
        return $this;
    }

    /**
     * Returns modal position classes as a space-separated string.
     *
     * @return string
     */
    public function getModalPosition(): string
    {
        return implode(' ', $this->modalPosition);
    }

    /**
     * Sets the modal size class.
     * Examples: "modal-s", "modal-m", "modal-l", "modal-xl".
     *
     * @param string $size
     * @return static
     */
    public function modalSize(string $size): static
    {
        $this->modalSize = $size;
        return $this;
    }

    /**
     * Returns the modal size class.
     *
     * @return string
     */
    public function getModalSize(): string
    {
        return $this->modalSize;
    }
    
    /**
     * Sets the modal animation class.
     *
     * @param string $animation
     * @return static
     */
    public function modalAnimation(string $animation): static
    {
        $this->modalAnimation = trim($animation);
        return $this;
    }

    /**
     * Returns the modal animation class.
     *
     * @return string
     */
    public function getModalAnimation(): string
    {
        return $this->modalAnimation;
    }
}