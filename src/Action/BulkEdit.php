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

use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\Service\View\ViewInterface;
use Closure;
use Throwable;

/**
 * BulkEdit
 */
final class BulkEdit extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    
    /**
     * @var array<array-key, string>
     */
    private array $fieldNames = [];
    
    /**
     * Create a new BulkEdit.
     *
     * @param string $name Must be sluggable and only of [a-z-] characters.
     * @param null|string $title
     */
    public function __construct(
        protected string $name,
        null|string $title = null,
    ) {
        if ((bool) preg_match('/^[a-z-_.]+$/u', $name) === false) {
            throw new \InvalidArgumentException(
                sprintf('The name %s must only contain [a-z-] characters', $name)
            );
        }
        
        $this->title = $title ?: $name;
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        
        $this->view('crud/bulk/edit');
    }
    
    /**
     * Create a new instance.
     *
     * @param string $name Must be sluggable and only of [a-z-] characters.
     * @param null|string $title
     * @return static
     */
    public static function new(
        string $name,
        null|string $title = null
    ): static {
        return new static($name, $title);
    }
    
    /**
     * Returns the name. Must be sluggable and only of [a-z-] characters.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Sets the field(s).
     *
     * @param string ...$names
     * @return static $this
     */
    public function field(string ...$names): static
    {
        $this->fieldNames = $names;
        return $this;
    }
    
    /**
     * Sets the fields.
     *
     * @param FieldsInterface $fields
     * @return static $this
     */
    public function setFields(FieldsInterface $fields): static
    {
        $this->fields = $fields->filter(fn (FieldInterface $f): bool => in_array($f->name(), $this->fieldNames));
        return $this;
    }
    
    /**
     * Returns the process bulk action.
     *
     * @return callable
     */
    public function getBulkProcessAction(): callable
    {
        return [$this, 'processBulk'];
    }
    
    /**
     * Process bulk action.
     *
     * @return void
     * @throws ActionProcessException
     */
    public function processBulk(): void
    {
        $input = $this->getInput();
        $repository = $this->controller()->repository();
        $updateAction = $this->actions()->get('update');
        
        if (! $updateAction instanceof Action\Update) {
            throw new ActionNotFoundException(actionName: 'update');
        }

        $updateAction->setFields($this->fields());
        $updateAction->setInput($input);
        
        $ids = $input->get('ids', []);

        $attributes = $input->collection()
            ->onlyPresent($this->fields()->getNames())
            ->all();

        if (empty($attributes)) {
            return;
        }
        
        foreach(array_values($ids) as $id) {
            
            if (!is_string($id) && !is_int($id)) {
                continue;
            }
            
            $entity = $repository->findById(id: $id);
            
            if (is_null($entity)) {
                continue;
            }
            
            $entity = $this->controller()->createEntityFromObject($entity);
            $updateAction->setEntity($entity);

            // Check if entity can be updated:
            if (! $updateAction->isUpdatable($updateAction->entity())) {
                continue;
            }
            
            $this->actionProcessor()->processFields(action: $updateAction, entity: $entity);
            
            $updatedItem = $this->controller()->updateEntity($id, $attributes, $updateAction->entity());
            $entity = $this->controller()->createEntityFromObject($updatedItem);

            // Process updated fields action:
            $this->actionProcessor()->processFieldsAction(
                action: $updateAction,
                actionName: 'updated',
                entity: $entity,
            );
        }
    }    
    
    /**
     * Returns the html of action. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string
    {
        $indexAction = $this->actions()->get('index');
        $createAction = $this->actions()->get('create');
        
        if (is_null($indexAction) || is_null($createAction)) {
            return '';
        }
        
        $fields = $indexAction
            ->fields()
            ->filter(fn (FieldInterface $f): bool => in_array($f->name(), $this->fieldNames));
        
        $createAction->setFields($fields);
        $this->actionProcessor->processFields(action: $createAction, entity: new Entity());
        $this->setFields($createAction->fields());
        
        return $view->render(
            view: $this->getView(),
            data: [
                'action' => $this,
            ],
        );
    }
}