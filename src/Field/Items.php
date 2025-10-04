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

use Tobento\App\Crud\Action;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Support\Str;
use Tobento\Service\View\ViewInterface;
use InvalidArgumentException;
use LogicException;

/**
 * Items
 */
class Items extends AbstractField implements FieldsAwareInterface
{
    /**
     * @var null|FieldsInterface
     */
    protected null|FieldsInterface $fields = null;
    
    /**
     * @var bool
     */
    protected bool $displayAsCard = false;
    
    /**
     * @var bool
     */
    protected bool $withoutLabel = false;
    
    /**
     * @var string
     */
    protected string $addText = 'Add new item';
    
    /**
     * @var null|int
     */
    protected null|int $defaultItems = null;
    
    /**
     * Create a new Items.
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
        $this->process('index', [$this, 'processIndexItems']);
        $this->process('create', [$this, 'processCreate']);
        $this->process('copy', [$this, 'processCreate']);
        $this->process('edit', [$this, 'processEdit']);
        $this->process('update:before', [$this, 'processBeforeUpdate']);
        $this->process('store|update', [$this, 'processSave']);
        $this->process('show', [$this, 'processShow']);
        $this->configure();
        $this->storable(false);
    }
    
    /**
     * Set if the attribute is translatable.
     *
     * @param bool $translatable
     * @return static $this
     */
    public function translatable(bool $translatable = true): static
    {
        throw new InvalidArgumentException('Field does not support translations');
    }
    
    /**
     * Returns whether the attribute is storable.
     *
     * @return bool
     */
    public function isStorable(): bool
    {
        // must never be storable if has items, otherwise all input data for the field name gets stored
        // instead of the defined items fields only.
        return $this->storable;
    }
    
    /**
     * Set if to display the label on create and edit action.
     *
     * @return static
     */
    public function withoutLabel(): static
    {
        $this->withoutLabel = true;
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
     * Set the add new item text.
     *
     * @param string $text
     * @return static $this
     */
    public function addText(string $text): static
    {
        $this->addText = $text;
        return $this;
    }
    
    /**
     * Returns the add new item text.
     *
     * @return string
     */
    public function getAddText(): string
    {
        return $this->addText;
    }
    
    /**
     * Set the default items number to display.
     *
     * @param int $num
     * @return static $this
     */
    public function defaultItems(int $num): static
    {
        $this->defaultItems = $num;
        return $this;
    }
    
    /**
     * Set if to display the first item.
     *
     * @param array $item
     * @return string
     */
    public function toItemLabel(array $item): string
    {
        $texts = [];
        
        foreach($item as $field) {
            $value = $field->entity()->get($field->name());
            $texts[] = json_encode($value);
        }
        
        return implode(', ', $texts);
    }
    
    /**
     * Sets the fields.
     *
     * @param FieldInterface ...$fields
     * @return static $this
     */
    public function fields(FieldInterface ...$fields): static
    {
        foreach($fields as $field) {
            if ($field instanceof FieldsAwareInterface) {
                throw new InvalidArgumentException('Subfields are not supported!');
            }

            $field->parent($this->name());
            $field->group($this->groupName());
        }
        
        $this->fields = new Fields(...$fields);
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
        $items = $action->getInput()->get($this->name(), []);
        
        $itemsCount = count($action->getInput()->get($this->name(), []));
        
        if ($itemsCount === 0 && !in_array($action->name(), ['store', 'update'])) {
            $itemsCount = count($action->entity()->get($this->name(), []));
        }
        
        if ($itemsCount === 0 && $this->defaultItems && in_array($action->name(), ['create', 'edit'])) {
            $itemsCount = $this->defaultItems;
        }
        
        if (is_null($this->fields)) {
            throw new LogicException('You need to define the fields first!');
        }
        
        // Create fields:
        $fields = [];
        $key = 1;
        
        for ($i = 1; $i <= $itemsCount; $i++) {
            // skip empty items which will be deleted.
            if (array_key_exists($i, $items) && empty($items[$i])) {
                continue;
            }
            
            foreach($this->fields as $field) {
                $field = clone $field;
                $field->rename($this->name().'.'.$key.'.'.$field->name());
                $field->attributes($field->getAttributes() + ['data-index' => (string)$key]);
                $fields[] = $field;
            }
            
            $key++;
        }
        
        if (count($fields) === 0 && $action->getInput()->has($this->name())) {
            $this->storable(true);
        }
        
        return new Fields(...$fields);
    }
    
    /**
     * Processes the index action.
     *
     * @param FieldInterface $field
     * @return void
     */
    public function processIndexItems(ActionInterface $action, FieldInterface $field): void
    {
        $data = json_encode($field->entity()->get($this->name(), []), JSON_PRETTY_PRINT);
        $data = mb_strimwidth($data, 0, 100, '...');
        $field->html('<pre>'.$data.'</pre>');
    }
    
    /**
     * Processes the create action.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     */
    public function processCreate(
        ActionProcessorInterface $actionProcessor,
        ActionInterface $action,
        FieldInterface $field,
        ViewInterface $view
    ): void {
        $this->processEdit($actionProcessor, $action, $field, $view);
    }
    
    /**
     * Processes the edit action.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     */
    public function processEdit(
        ActionProcessorInterface $actionProcessor,
        ActionInterface $action,
        FieldInterface $field,
        ViewInterface $view
    ): void {
        if (is_null($this->fields)) {
            throw new LogicException('You need to define the fields first!');
        }
        
        $items = [];
        
        foreach($action->fields()->parent($field->name()) as $f) {
            $index = $f->getAttributes()['data-index'] ?? 1;
            $items[$index][] = $f;
        }
        
        // template field:
        $fields = [];
        foreach($this->fields as $tf) {
            $tf->rename($this->name().'.{num}.'.$tf->name());
            $fields[] = $tf;
        }
        
        $action = clone $action;
        $action->setFields(new Fields(...$fields));
        $actionProcessor->processFields($action, new Entity());

        $field->html($view->render(
            view: 'crud/field/items',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'templateFields' => $action->fields(),
                'items' => $items,
                'asCard' => $this->displayAsCard,
                'withoutLabel' => $this->withoutLabel,
            ],
        ));
    }
    
    /**
     * Processes the save action.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @return void
     */
    public function processBeforeUpdate(
        ActionProcessorInterface $actionProcessor,
        ActionInterface $action,
        FieldInterface $field,
    ): void {
        $itemIdsToDelete = [];
        $itemsNew = [];
        $key = 1;
        $items = $action->getInput()->get($this->name(), []);
        
        if (empty($items)) {
            $items = $action->entity()->get($this->name(), []);
            $itemIdsToDelete = array_keys($items);
        } else {
            foreach($items as $id => $item) {
                if (empty($item)) {
                    $itemIdsToDelete[] = $id;
                    continue;
                }
                $itemsNew[$key] = $item;
                $key++;
            }
        }
        
        $this->deleteItems($actionProcessor, $action, $field, $itemIdsToDelete);
        
        $action->getInput()->set($field->name(), $itemsNew);
    }

    /**
     * Processes the store action.
     *
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @param InputInterface $input
     * @return void
     */
    public function processSave(
        ActionInterface $action,
        FieldInterface $field,
        InputInterface $input,
    ): void {
        if (! $input->has($field->name())) {
            return;
        }
        
        if (!is_array($input->get($field->name()))) {
            $input->set($field->name(), []);
        }
    }
    
    /**
     * Deletes the given items.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @param array<array-key, int> $itemIds
     * @return void
     */
    protected function deleteItems(
        ActionProcessorInterface $actionProcessor,
        ActionInterface $action,
        FieldInterface $field,
        array $itemIds,
    ): void {
        if (empty($itemIds)) {
            return;
        }
                
        $fields = [];
        
        foreach($itemIds as $id) {
            foreach($this->fields as $f) {
                $f = clone $f;
                $f->rename($this->name().'.'.$id.'.'.$f->name());
                $f->attributes($f->getAttributes() + ['data-index' => (string)$id]);
                $fields[] = $f;
            }
        }
        
        $deleteAction = $action->actions()->get('delete');
        
        if (! $deleteAction instanceof Action\Delete) {
            throw new ActionNotFoundException(actionName: 'delete');
        }
        
        $deleteAction->setFields(new Fields(...$fields));
        $deleteAction->setEntity($action->entity());
        
        $actionProcessor->processFields(action: $deleteAction, entity: $action->entity());
        
        $actionProcessor->processFieldsAction(action: $deleteAction, actionName: 'deleted');
        
        foreach($itemIds as $id) {
            $action->entity()->delete($field->name().'.'.$id);
        }
    }
}