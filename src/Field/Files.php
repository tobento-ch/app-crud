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

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Message\MessageInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Validation\Rule\Passes;
use Tobento\Service\Validation\ValidationInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Files field enables you to upload a multiple files.
 */
class Files extends AbstractField implements FieldsAwareInterface
{
    use Traits\Hidden;
    
    /**
     * @var null|callable
     */
    protected $file = null;
    
    /**
     * @var array<array-key, FieldInterface>
     */
    protected array $fields = [];
    
    /**
     * @var null|int
     */
    protected null|int $minFiles = null;
    
    /**
     * @var null|int
     */
    protected null|int $maxFiles = null;
    
    /**
     * @var int
     */
    protected int $totalFiles = 0;
    
    /**
     * Create a new Files.
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
        $this->process('index', [$this, 'processIndexFile']);
        $this->process('create|copy', [$this, 'processCreate']);
        $this->process('store:before|update:before', [$this, 'processBeforeSave']);
        $this->process('store|update', [$this, 'processSave']);
        $this->process('edit', [$this, 'processEdit']);
        $this->process('show', [$this, 'processShowFiles']);
        $this->configure();
        $this->storable(false);
        
        // call validate as to add rule:
        $this->validate([]);
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
        // Only on deleting, it needs to be storable as to remove it.
        return $this->storable;
    }
    
    /**
     * Sets the file field to be used.
     *
     * @param null|callable $callable
     * @return static $this
     */
    public function file(null|callable $callable): static
    {
        $this->file = $callable;
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
        $this->fields[] = new Text('order')
            ->type('hidden')
            ->attributes(['data-order' => ''])
            ->showable(false)
            ->validate('digit');
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
        return $this->createFields($action);
    }
    
    /**
     * Returns the fields.
     *
     * @param ActionInterface $action
     * @param bool $withSubfields
     * @return FieldsInterface
     */
    protected function createFields(ActionInterface $action, bool $withSubfields = false): FieldsInterface
    {
        if (empty($this->fields)) {
            $this->fields();
        }
        
        $input = $action->getInput();
        $inputSrc = $input->get($this->name().'.src', []);
        $input->delete($this->name().'.src');
        
        // skip if no files are selected:
        if (
            count($inputSrc) === 1
            && $inputSrc[0] instanceof UploadedFileInterface
            && $inputSrc[0]->getError() === 4
        ) {
            $inputSrc = [];
        }
        
        // merge new files to input:
        if (count($inputSrc) > 0) {
            $i = count($action->getInput()->get($this->name(), []));
            
            foreach($inputSrc as $newFile) {
                if ($this->isTranslatable()) {
                    $input->set($this->name().'.'.$i++.'.src.'.$action->getLocale(), $newFile);
                } else {
                    $input->set($this->name().'.'.$i++.'.src', $newFile);
                }
            }
        }
        
        $filesCount = count($action->getInput()->get($this->name(), []));
        
        if ($filesCount === 0 && !in_array($action->name(), ['store', 'update'])) {
            $filesCount = count($action->entity()->get($this->name(), []));
        }
        
        // Create fields:
        $fields = [];
        
        for ($i = 0; $i <= $filesCount-1; $i++) {
            
            $field = $this->configureFile(new File('file', ''))
                ->fields(...array_map(fn (FieldInterface $f): FieldInterface => clone $f, $this->fields))
                ->parent($this->name())
                ->orderable($filesCount > 1);

            $field->rename($this->name().'.'.$i);
            
            if ($withSubfields) {
                foreach($field->getFields($action) as $childField) {
                    $fields[] = $childField;
                }
            }

            $fields[] = $field;
        }
        
        return new Fields(...$fields);
    }
    
    /**
     * Sets the number of files.
     *
     * @param null|int $min
     * @param null|int $max
     * @return static $this
     */
    public function numberOfFiles(null|int $min = null, null|int $max = null): static
    {
        $this->minFiles = $min;
        $this->maxFiles = $max;
        return $this;
    }
    
    /**
     * Returns the accept attribute for the input field.
     *
     * @return string
     */
    public function acceptAttribute(): string
    {
        $field = $this->configureFile(new File('file', ''));
        return $field->acceptAttribute();
    }
    
    /**
     * Processes the show action.
     *
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @return void
     */
    public function processShowFiles(
        ActionInterface $action,
        FieldInterface $field,
        ViewInterface $view,
    ): void {
        $field->html($view->render(
            view: 'crud/field/show/files',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'files' => $action->fields()->parent($field->name()),
            ],
        ));
    }
    
    /**
     * Processes the show action.
     *
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @return void
     */
    public function processIndexFile(ActionInterface $action, FieldInterface $field): void
    {
        $fileField = $action->fields()->get($field->name().'.0.src');
        $field->html((string)$fileField?->render());
    }
    
    /**
     * Processes the save action.
     *
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @return void
     */
    public function processBeforeSave(
        ActionInterface $action,
        FieldInterface $field,
    ): void {
        $this->totalFiles = count($field->entity()->get($field->name(), []));
    }    
    
    /**
     * Processes the save action.
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
        
        $newInput = clone $input;
        $files = $newInput
            ->collection()
            ->onlyPresent($this->createFields(action: $action, withSubfields: true)->storable()->getNames())
            ->get($field->name(), []);
        
        usort($files, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        
        $input->set($field->name(), $files);
        
        $field->storable(true);
        return;
    }
    
    /**
     * Processes the create action.
     *
     * @param ActionInterface $action
     * @param Files $field
     * @param ViewInterface $view
     * @return void
     */
    public function processCreate(
        ActionInterface $action,
        Files $field,
        ViewInterface $view
    ): void {
        $this->processEdit($action, $field, $view);
    }
    
    /**
     * Processes the edit action.
     *
     * @param ActionInterface $action
     * @param Files $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processEdit(
        ActionInterface $action,
        Files $field,
        ViewInterface $view
    ): void {
        if ($field->isHidden()) {
            $field->html('');
            return;
        }
        
        $files = $action->fields()->parent($field->name());
        
        // filter out empty src so as not to display:
        $files = $files->filter(function (FieldInterface $file): bool {
            if ($file->isTranslatable()) {
                return true;
            }
            
            if ($file->entity()->get($file->name().'.src', '') === '') {
                return false;
            }
            
            return true;
        });
        
        // filter out first files message:
        $messages = $view->form()->messages()->filter(function (MessageInterface $message) use ($field): bool {
            static $keys = [];
            
            if (
                str_starts_with((string)$message->key(), $field->name())
                && !in_array($message->key(), $keys)
            ) {
                $keys[] = $message->key();
                return true;
            }
            
            return false;
        });

        $field->html($view->render(
            view: 'crud/field/files',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'files' => $files,
                'filesMessages' => $messages,
            ],
        ));
    }
    
    /**
     * Returns the configured file field.
     *
     * @param File $file
     * @return File
     */
    protected function configureFile(File $file): File
    {
        if (is_callable($this->file)) {
            call_user_func_array($this->file, [$file]);
        }
        
        return $file;
    }
    
    /**
     * Set the attribute validate parameters.
     *
     * @param mixed ...$parameters
     * @return static $this
     */
    public function validate(mixed ...$parameters): static
    {
        foreach($parameters as $key => $parameter) {
            $parameters[$key] = !is_array($parameter)
                ? [$parameter, $this->filesValidationRule()]
                : array_merge($parameter, [$this->filesValidationRule()]);
        }

        $this->validate = $parameters;
        return $this;
    }
    
    /**
     * Returns the files validation rule.
     *
     * @return Passes
     */
    protected function filesValidationRule(): Passes
    {
        return new Passes(
            passes: function(mixed $value, ValidationInterface $validation): bool {
                if (!is_array($value)) {
                    return true;
                }
                
                if (is_null($this->maxFiles) && is_null($this->minFiles)) {
                    return true;
                }
                
                $totalFiles = $this->totalFiles;
                
                foreach($value as $file) {
                    if (! array_key_exists('src', $file)) {
                        continue;
                    }
                    
                    if ($file['src'] === '') {
                        $totalFiles--;
                        continue;
                    }
                    
                    if (
                        $file['src'] instanceof UploadedFileInterface
                        && $file['src']->getError() !== 4
                    ) {
                        $totalFiles++;
                    }
                }
                
                if ($this->maxFiles && $totalFiles > $this->maxFiles) {
                    $validation->errors()->add(
                        level: 'error',
                        message: 'The :attribute must have at most :parameters[0] items.',
                        parameters: [':attribute' => $this->label(), ':parameters[0]' => $this->maxFiles],
                        context: ['rule_parameters' => [$this->maxFiles]],
                        key: $this->name(),
                    );
                    return false;
                }
                
                if ($this->minFiles && $totalFiles < $this->minFiles) {
                    $validation->errors()->add(
                        level: 'error',
                        message: 'The :attribute must have at least :parameters[0] items.',
                        parameters: [':attribute' => $this->label(), ':parameters[0]' => $this->minFiles],
                        context: ['rule_parameters' => [$this->minFiles]],
                        key: $this->name(),
                    );
                    return false;
                }
                
                return true;
            },
            errorMessage: 'The :attribute items are invalid.',
        );
    }
    
    /**
     * Sets whether the field is readonly.
     *
     * @param bool|callable $readonly
     * @return static $this
     */
    public function readonly(bool|callable $readonly = true, null|string $action = null): static
    {
        throw new InvalidArgumentException('Field does not support readonly');
    }
    
    /**
     * Sets whether the field is disabled.
     *
     * @param bool|callable $disabled
     * @return static $this
     */
    public function disabled(bool|callable $disabled = true, null|string $action = null): static
    {
        throw new InvalidArgumentException('Field does not support disabled');
    }
}