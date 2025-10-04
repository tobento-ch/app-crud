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

namespace Tobento\App\Crud\Action;

use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\EntityInterface;
use Closure;

/**
 * Show
 */
final class ShowJson extends AbstractAction
{
    /**
     * Create a new ShowJson.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        protected null|string|Closure $title = null,
    ) {
        $this->route('{name}.show', function(EntityInterface $entity): array {
            return ['id' => $entity->id(), 'type' => 'json'];
        });
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'show.json';
    }
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        $title = parent::title();
        
        return $title === 'Show.json' ? 'Show JSON' : $title;
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function buttons(): ButtonsInterface
    {
        if ($this->buttons instanceof ButtonsInterface) {
            return $this->buttons;
        }
        
        $this->buttons = new Buttons();
        
        return $this->applyButtonsConfig($this->buttons);
    }
}