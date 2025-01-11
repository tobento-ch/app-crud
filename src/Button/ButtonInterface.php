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

use Tobento\App\Crud\Url\Linkable;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\Service\View\ViewInterface;

/**
 * ButtonInterface
 */
interface ButtonInterface extends Linkable
{
    /**
     * Sets the name.
     *
     * @param string $name
     * @return static $this
     */
    public function name(string $name): static;
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets the label.
     *
     * @param string $label
     * @return static $this
     */
    public function label(string $label): static;
    
    /**
     * Returns the label.
     *
     * @return string
     */
    public function getLabel(): string;
    
    /**
     * Sets the group.
     *
     * @param string $group
     * @return static $this
     */
    public function group(string $group): static;
    
    /**
     * Returns the group.
     *
     * @return string
     */
    public function getGroup(): string;
    
    /**
     * Sets the icon.
     *
     * @param string $icon
     * @return static $this
     */
    public function icon(string $icon): static;
    
    /**
     * Returns the icon.
     *
     * @return null|string
     */
    public function getIcon(): null|string;
    
    /**
     * Sets a button as primary.
     *
     * @param bool $primary
     * @return static $this
     */
    public function primary(bool $primary = true): static;
    
    /**
     * Sets a button as raw.
     *
     * @param bool $raw
     * @return static $this
     */
    public function raw(bool $raw = true): static;
    
    /**
     * Sets a button attribute.
     *
     * @param string $name
     * @param mixed $value
     * @return static $this
     */
    public function attr(string $name, mixed $value = null): static;
    
    /**
     * Removes a button attribute.
     *
     * @param string $name
     * @return static $this
     */
    public function removeAttr(string $name): static;
    
    /**
     * Sets wheter to ask confirmation for the button action.
     *
     * @param bool|string $text
     * @return static $this
     */
    public function askConfirmation(bool|string $text = true): static;
    
    /**
     * Sets whether to use ajax to perform the button action.
     *
     * @param bool|string $text
     * @return static $this
     */
    public function ajaxAction(bool|string $text = true): static;

    /**
     * Returns a new instance with the specified url.
     *
     * @param string $url
     * @return static
     */
    public function withUrl(string $url): static;
    
    /**
     * Returns the url.
     *
     * @return string
     */
    public function getUrl(): string;
    
    /**
     * Returns a new instance with the specified entity.
     *
     * @param EntityInterface $entity
     * @return static
     */
    public function withEntity(EntityInterface $entity): static;
    
    /**
     * Returns the html of the button. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string;
}