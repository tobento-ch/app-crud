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

use Stringable;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Str;
use Tobento\Service\View\ViewInterface;

/**
 * Descriptive
 */
final class Descriptive extends AbstractButton
{
    /**
     * Create a new Descriptive instance.
     *
     * @param ButtonInterface $button
     * @param string|Stringable $description
     * @param null|string $visualGroup
     */
    public function __construct(
        private ButtonInterface $button,
        private string|Stringable $description = '',
        private null|string $visualGroup = null
    ) {
        $this->label = $button->getLabel();
        $this->group = $button->getGroup();
        $this->icon = $button->getIcon();
        $this->name = $button->getName();
        $this->attributes = new Attributes();
    }
    
    /**
     * Returns the button rendered inside the descriptive block.
     *
     * @return ButtonInterface
     */
    public function getButton(): ButtonInterface
    {
        return $this->button;
    }

    /**
     * Returns the descriptive text shown below the label.
     *
     * @return string|Stringable
     */
    public function getDesc(): string|Stringable
    {
        return $this->description;
    }

    /**
     * Returns the visual group name for this descriptive item.
     *
     * @return null|string
     */
    public function getVisualGroup(): null|string
    {
        return $this->visualGroup;
    }

    /**
     * Returns the html of the button. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function render(ViewInterface $view): string
    {
        // Render the inner button
        if ($this->getDesc() === '') {
            return $this->getButton()->render($view);
        }

        $html = '<div class="crud-btn-descriptive" data-button="">';
        $html .= '<div class="descriptive-btn">'.$this->getButton()->render($view).'</div>';
        $html .= '<div class="descriptive-desc">'.$view->esc($this->getDesc()).'</div>';
        $html .= '</div>';

        return $html;
    }
}