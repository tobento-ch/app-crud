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

use Closure;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\Service\View\ViewInterface;

/**
 * Group field enables you to group fields.
 */
class Group extends AbstractField implements ParentFieldsAwareInterface
{
    /**
     * @var array<int, FieldInterface>
     */
    protected array $fields = [];

    /**
     * @var bool
     */
    protected bool $prependGroupName = false;
    
    /**
     * @var bool
     */
    protected bool $displayAsCard = false;
    
    /**
     * @var bool
     */
    protected bool $displayLabel = false;
    
    /**
     * @var array<string, string>
     */
    protected array $renameFields = [];
    
    /**
     * @var array<string, Closure>
     */
    protected array $fieldModifiers = [];
    
    /**
     * @var null|Closure
     */
    protected null|Closure $fieldsModifier = null;
    
    /**
     * @var array<array-key, string>
     */
    protected array $fieldsToRemove = [];    
    
    /**
     * Create a new Group.
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
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->indexable(false);
        $this->configure();
        $this->storable(false);
    }

    /**
     * Create a new instance.
     *
     * @param string $name
     * @param null|string $label
     * @return static
     */
    public static function new(string $name, null|string $label = null): static
    {
        return new static($name, $label);
    }

    /**
     * Set whether to prepend group name to fields.
     *
     * @param bool $prepend
     * @return static $this
     */
    public function prependGroupName(bool $prepend = true): static
    {
        $this->prependGroupName = $prepend;
        return $this;
    }
    
    /**
     * Set if to display the items as cards.
     *
     * @param bool $card
     * @return static $this
     */
    public function displayAsCard(bool $card = true): static
    {
        $this->displayAsCard = $card;
        return $this;
    }
    
    /**
     * Set if to display the label.
     *
     * @param bool $display
     * @return static
     */
    public function displayLabel(bool $display = true): static
    {
        $this->displayLabel = $display;
        return $this;
    }

    /**
     * Renames fields.
     *
     * @param array<string, string> $names
     * @return static $this
     */
    public function renameFields(array $names): static
    {
        $this->renameFields = $names;
        return $this;
    }
    
    /**
     * Modify the given field.
     *
     * @param string $name
     * @param Closure $modifier fn (FieldInterface $field): void {}
     * @return static $this
     */
    public function modifyField(string $name, Closure $modifier): static
    {
        $this->fieldModifiers[$name] = $modifier;
        return $this;
    }
    
    /**
     * Modify fields.
     *
     * @param Closure $modifier fn (FieldsInterface $fields): FieldsInterface {}
     * @return static $this
     */
    public function modifyFields(Closure $modifier): static
    {
        $this->fieldsModifier = $modifier;
        return $this;
    }
    
    /**
     * Sets the filds to remove.
     *
     * @param string ...$names
     * @return static $this
     */
    public function removeField(string ...$names): static
    {
        $this->fieldsToRemove = $names;
        return $this;
    }
    
    /**
     * Sets the fields.
     *
     * @param FieldInterface ...$fields
     * @return static $this
     */
    public function fields(FieldInterface ...$fields): static
    {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * Returns the fields.
     *
     * @param ActionInterface $action
     * @return FieldsInterface
     */
    public function getFields(ActionInterface $action): FieldsInterface
    {
        return $this->configureFields(new Fields(...$this->fields), $action);
    }
    
    /**
     * Returns the configured fields.
     *
     * @param FieldsInterface $fields
     * @param ActionInterface $action
     * @return FieldsInterface
     */
    protected function configureFields(FieldsInterface $fields, ActionInterface $action): FieldsInterface
    {
        if (!empty($this->fieldsToRemove)) {
            $fields = $fields->filter(fn (FieldInterface $field) => !in_array($field->name(), $this->fieldsToRemove));
        }
        
        foreach($fields as $field) {
            if (isset($this->renameFields[$field->name()])) {
                $field->rename($this->renameFields[$field->name()]);
            }
            
            if ($this->prependGroupName) {
                $field->rename($this->name().'.'.$field->name());
            }
            
            if ($action->name() !== 'index' && $this->displayAsCard) {
                $field->parent($this->name());
            }
                        
            $field->group($this->groupName());
            
            if (isset($this->fieldModifiers[$field->name()])) {
                $this->fieldModifiers[$field->name()]($field);
            }
        }
        
        if ($this->fieldsModifier instanceof Closure) {
            return call_user_func($this->fieldsModifier, $fields);
        }
        
        return $fields;
    }
    
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param Group $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        Group $field,
        ViewInterface $view
    ): void {
        if ($this->displayAsCard === false) {
            $field->html('');
            return;
        }

        $fields = $action->fields()->parent($field->name());
        
        $field->html($view->render(
            view: 'crud/field/group',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'fields' => $fields,
                'asCard' => $this->displayAsCard,
                'displayLabel' => $this->displayLabel,
            ],
        ));
    }
}