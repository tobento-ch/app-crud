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

namespace Tobento\App\Crud\Field;

use Tobento\App\AppInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;

/**
 * Html
 */
class Html extends AbstractField
{
    use Traits\Hidden;
    
    /**
     * @var callable|string
     */
    protected $content = '';
    
    /**
     * Create a new Html instance.
     *
     * @param string $name
     */
    final public function __construct(
        string $name,
    ) {
        $this->name = $name;
        $this->process('create|edit|index|show', [$this, 'processRender']);
        $this->indexable(false);
        $this->storable(false);
        $this->showable(false);
        $this->configure();
    }
    
    /**
     * Sets the html content.
     *
     * @param callable|string $html Must be escaped.
     * @return static $this
     */
    public function content(callable|string $html): static
    {
        $this->content = $html;
        return $this;
    }
    
    /**
     * Returns the html content.
     *
     * @return callable|string
     */
    public function getContent(): callable|string
    {
        return $this->content;
    }
    
    /**
     * Processes the store action.
     *
     * @param Html $field
     * @param AppInterface $app
     * @return void
     */
    public function processRender(Html $field, AppInterface $app): void
    {
        if ($field->isHidden()) {
            $field->html('');
            return;
        }
        
        if (is_callable($field->getContent())) {
            $field->html($app->call($field->getContent(), ['field' => $field]));
            return;
        }
        
        $field->html($field->getContent());
    }
}