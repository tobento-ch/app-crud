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

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Support\Str;
use Tobento\Service\View\ViewInterface;

/**
 * TextEditor
 */
class TextEditor extends AbstractField
{
    use Traits\Hidden;
    
    /**
     * @var array
     */
    protected array $editorConfig = [];
    
    /**
     * Create a new TextEditor.
     *
     * @param string $name
     * @param null|string $label
     */
    final public function __construct(
        string $name,
        null|string $label = null,
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->process('index', [$this, 'processIndexAction']);
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->process('show', [$this, 'processShowAction']);
        $this->process('store', [$this, 'processStore']);
        $this->process('update', [$this, 'processUpdate']);
        $this->configure();
    }
    
    /**
     * Sets the editor config.
     *
     * @param array $config E.g. ['toolbar' => ['bold', 'italic']]
     * @return static $this
     */
    public function editorConfig(array $config): static
    {
        /*
        ['toolbar' => [
            'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'bold', 'italic', 'underline', 'strike',
            'ol', 'ul', 'quote', 'pre', 'code',
            'undo', 'redo', 'sourcecode', 'clear',
            'link', 'tables', 'style.fonts', 'style.text.colors'
        ]]*/
        
        $this->editorConfig = $config;
        return $this;
    }
    
    /**
     * Returns the editor config.
     *
     * @return array
     */
    public function getEditorConfig(): array
    {
        return $this->editorConfig;
    }
    
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param TextEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        TextEditor $field,
        ViewInterface $view
    ): void {
        if ($field->isHidden()) {
            $field->html('');
            return;
        }
        
        $attributes = $field->getAttributes();
        $attributes['data-editor'] = $field->getEditorConfig();
        $attributes = array_merge(
            $field->getHtmlValidationAttributes(
                $action->name(),
                'textarea',
                $view->trans($field->label())
            ),
            $attributes,
        );
        
        $field->html($view->render(
            view: 'crud/field/text-editor',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'attributes' => $attributes,
            ],
        ));
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndexAction(FieldInterface $field): void
    {
        $html = (string)$field->entity()->get($field->name(), '', $field->locale());
        
        $html = str_replace('<', ' <', $html);
        $html = strip_tags($html);
        $html = str_replace('&nbsp;', '', $html);
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);
        $html = mb_strimwidth($html, 0, 100, '...');
        
        $field->html(Str::esc($html));
    }
    
    /**
     * Processes the show action.
     *
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     */
    public function processShowAction(FieldInterface $field, ViewInterface $view): void
    {
        $field->html($view->render(
            view: 'crud/field/show/text-editor',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
            ],
        ));
    }
}