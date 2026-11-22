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
     * The original names of the fields by object id, as the fields get renamed.
     *
     * @var array<int, string>
     */
    protected array $originalNames = [];

    /**
     * @var bool
     */
    protected bool $prependGroupName = false;

    /**
     * @var bool
     */
    protected bool $displayAsField = false;
    
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
     * Set if to display the items as field.
     *
     * @param bool $field
     * @return static $this
     */
    public function displayAsField(bool $field = true): static
    {
        $this->displayAsField = $field;
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
        $this->originalNames = [];
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
        // remove the fields before creating the fields, as filtering the fields would clone them:
        $fields = array_filter(
            $this->fields,
            fn (FieldInterface $field): bool => !in_array($this->originalName($field), $this->fieldsToRemove)
        );
        
        return $this->configureFields(new Fields(...$fields), $action);
    }
    
    /**
     * Returns the configured fields.
     *
     * The fields are renamed from their original name, so the result is the
     * same no matter how often the fields are configured.
     *
     * @param FieldsInterface $fields
     * @param ActionInterface $action
     * @return FieldsInterface
     */
    protected function configureFields(FieldsInterface $fields, ActionInterface $action): FieldsInterface
    {
        foreach($fields as $field) {
            $name = $this->originalName($field);
            
            if (isset($this->renameFields[$name])) {
                $name = $this->renameFields[$name];
            }
            
            if ($this->prependGroupName) {
                $name = $this->name().'.'.$name;
            }
            
            $field->rename($name);
            
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
     * Returns the original name of the given field, before it got renamed.
     *
     * @param FieldInterface $field
     * @return string
     */
    protected function originalName(FieldInterface $field): string
    {
        return $this->originalNames[spl_object_id($field)] ??= $field->name();
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
                'displayAsField' => $this->displayAsField,
                'asCard' => $this->displayAsCard,
                'displayLabel' => $this->displayLabel,
            ],
        ));
    }
}