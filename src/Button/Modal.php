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

use Tobento\App\Crud\Action\Traits\ConfiguresModal;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Str;
use Tobento\Service\Tag\Tag;
use Tobento\Service\Tag\TagInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Modal button.
 */
final class Modal extends AbstractButton implements ButtonsAwareInterface
{
    use ConfiguresModal;
    
    /**
     * @var null|ButtonsInterface
     */
    private null|ButtonsInterface $buttons = null;
    
    private bool $searchableButtons = true;
    
    private null|string $searchableButtonsPlaceholder = null;    
    
    /**
     * Create a new Modal instance.
     *
     * @param string $label
     * @param string $group
     * @param null|string $icon
     */
    public function __construct(
        string $label,
        string $group,
        null|string $icon = null,
    ) {
        $this->label = $label;
        $this->group = $group;
        $this->icon = $icon;
        $this->attributes = new Attributes();
    }
    
    /**
     * Sets the buttons.
     *
     * @param ButtonInterface $buttons
     * @return static $this
     */
    public function buttons(ButtonInterface ...$buttons): static
    {
        $this->buttons = new Buttons(...$buttons);
        return $this;
    }
    
    /**
     * Returns a new instance with the given buttons.
     *
     * @param ButtonsInterface $buttons
     * @return static
     */
    public function withButtons(ButtonsInterface $buttons): static
    {
        $new = clone $this;
        $new->buttons = $buttons;
        return $new;
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function getButtons(): ButtonsInterface
    {
        return $this->buttons ?: new Buttons();
    }
    
    /**
     * Enables or disables button search within the modal.
     *
     * @param bool $searchable
     * @return static
     */
    public function searchableButtons(bool $searchable = true): static
    {
        $this->searchableButtons = $searchable;
        return $this;
    }
    
    /**
     * Sets the search input placeholder.
     *
     * @param string $placeholder
     * @return static
     */
    public function searchableButtonsPlaceholder(string $placeholder): static
    {
        $this->searchableButtonsPlaceholder = $placeholder;
        return $this;
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
        if ($this->getButtons()->count() === 0) {
            return '';
        }
        
        if ($this->searchableButtons) {
            $view->asset('assets/crud/button-modal.js')->attr('type', 'module');
        }
                
        // Create unique modal id
        $modalId = sprintf('modal-%s%s', $this->getName(), (string)$this->entity?->id());
        $modalId = preg_replace('/[^a-zA-Z0-9-_]/', '', $modalId);

        // Build trigger tag (same pattern as Dropdown)
        $tag = $this->tag($modalId);

        // If icon is set, replace tag HTML with icon + label
        if ($this->icon) {
            $icon = $view->icon($this->icon);
            $html = (string)$icon->label($view->esc($this->getLabel()));
            $tag = $tag->withHtml($html);
        }

        // Render trigger button
        $html  = '<div class="crud-button-modal-trigger" data-modal-trigger="'.$view->esc($modalId).'">';
        $html .= $tag->render();
        $html .= '</div>';
        
        // Modal attributes
        $modalAttributes = new Attributes([
            'class' => 'modal crud-button-modal',
            'data-modal' => [
                'id' => $modalId,
            ],
        ]);
        
        $modalAttributes->add('class', $this->getModalPosition());
        $modalAttributes->add('class', $this->getModalAnimation());
        
        // Modal content attributes
        $modalContentAttributes = new Attributes(['class' => 'modal-content']);
        $modalContentAttributes->add('class', $this->getModalSize());
        
        // Render modal dialog
        $html .= '<div'.(string)$modalAttributes.'>';
        $html .= '<div class="modal-background"></div>';
        $html .= '<div'.(string)$modalContentAttributes.'>';

        // Modal head
        if ($this->searchableButtons) {

            $searchPlaceholder = is_null($this->searchableButtonsPlaceholder)
                ? $view->etrans('Search')
                : $view->esc($this->searchableButtonsPlaceholder);
            
            $html .= '<div class="modal-head">';
            $html .= '<input name="buttons_search" type="search" class="small fit" placeholder="'.$searchPlaceholder.'">';
            $html .= '</div>';
        }
        
        // Modal body
        $html .= '<div class="modal-body">';
        
        // Build groups
        $grouped = [];
        $ungrouped = [];
        
        foreach ($this->getButtons() as $button) {
            if ($button instanceof Descriptive && $button->getVisualGroup() !== '') {
                $button->getButton()->attr('data-button-search', '');
                $grouped[$button->getVisualGroup()][] = $button;
            } else {
                $ungrouped[] = $button;
            }
        }

        // Render ungrouped buttons
        if (!empty($ungrouped)) {
            $html .= '<div class="modal-buttons">';
            foreach ($ungrouped as $button) {
                $button->attr('data-button-search', '');
                $html .= $button->render($view);
            }
            $html .= '</div>';            
        }

        // Render grouped descriptive buttons
        foreach ($grouped as $groupName => $buttons) {
            $html .= '<div class="modal-group">';

            if ($groupName !== '') {
                $html .= '<div class="modal-group-title">'.$view->esc($groupName).'</div>';
            }

            foreach ($buttons as $button) {
                $html .= $button->render($view);
            }

            $html .= '</div>';
        }
        
        $html .= '</div>'; // modal-body
        
        // Modal foot
        $html .= '<div class="modal-foot">';
        $html .= '<span class="link modal-close">'.$view->etrans('Cancel').'</span>';
        $html .= '</div>';
        
        $html .= '</div>'; // modal-content
        $html .= '</div>'; // modal

        return $html;
    }
    
    /**
     * Returns the tag of the button.
     *
     * @param $modalId
     * @return TagInterface
     */
    private function tag(string $modalId): TagInterface
    {
        $tag = new Tag(
            name: 'span', // do not use button, as it could be inside a form and will be submitted!
            html: Str::esc($this->getLabel()),
            attributes: $this->attributes,
        );
        
        if (! $tag->attributes()->has('class')) {
            $tag->class(value: 'button text-xs');
        }
        
        if ($this->raw) {
            $tag->class(value: 'raw');
        } elseif ($this->primary) {
            $tag->class(value: 'primary');
        }
        
        $tag->attr(name: 'aria-haspopup', value: 'true');
        $tag->attr(name: 'aria-controls', value: $modalId);
        $tag->attr(name: 'data-button', value: $this->getName());
        
        return $tag;
    }
}