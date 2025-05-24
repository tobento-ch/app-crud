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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;

/**
 * File field enables you to upload a single file only.
 */
class File extends AbstractField implements FieldsAwareInterface
{
    /**
     * @var array<int, FieldInterface>
     */
    protected array $fields = [];
    
    /**
     * @var string
     */
    protected string $storageName = 'uploads';
    
    /**
     * @var null|callable
     */
    protected $fileSource = null;
    
    /**
     * @var null|string
     */
    protected null|string $storeFilenameToField = null;
    
    /**
     * @var null|callable
     */
    protected $filenameModifier = null;
    
    /**
     * @var bool
     */
    protected bool $orderable = false;
    
    /**
     * Create a new File.
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
        $this->process('store|update', [$this, 'processSave']);
        $this->process('edit', [$this, 'processEdit']);
        $this->process('show', [$this, 'processShowFile']);
        $this->configure();
        $this->storable(false);
        $this->group($this->label());
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
     * Returns the label.
     *
     * @return string
     */
    public function label(): string
    {
        return $this->label ?: '';
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
     * Set whether the file is orderable.
     *
     * @param bool $orderable
     * @return static $this
     */
    public function orderable(bool $orderable = true): static
    {
        $this->orderable = $orderable;
        return $this;
    }
    
    /**
     * Returns whether the file is orderable.
     *
     * @return bool
     */
    public function isOrderable(): bool
    {
        return $this->orderable;
    }

    /**
     * Sets the file source to be used.
     *
     * @param null|callable $callable
     * @return static $this
     */
    public function fileSource(null|callable $callable): static
    {
        $this->fileSource = $callable;
        return $this;
    }
    
    /**
     * Set a field name to store the filename to on store action only.
     *
     * @param string $field
     * @return static $this
     */
    public function storeFilenameTo(string $field, null|callable $modify = null): static
    {
        if (is_null($modify)) {
            $modify = static function (string $filename): string {
                $filename = pathinfo($filename, PATHINFO_FILENAME);
                return strtr($filename, ['_' => ' ', '-' => ' ']);
            };
        }
        
        $this->storeFilenameToField = $field;
        $this->filenameModifier = $modify;
        return $this;
    }
    
    /**
     * Returns the file storage name.
     *
     * @return string
     */
    public function getStorageName(): string
    {
        return $this->storageName;
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
     * @psalm-suppress RedundantCondition
     */
    public function getFields(ActionInterface $action): FieldsInterface
    {
        if ($this->isTranslatable()) {
            $src = null;
            
            // get first found locale:
            foreach (array_keys($action->getLocales()) as $locale) {
                if ($src = $action->entity()->get($this->name().'.src.'.$locale)) {
                    break;
                }
            }

            if (empty($src) && in_array($action->name(), ['create', 'edit', 'copy', 'show'])) {
                $fields = [
                    $this->configureFileSource(Field\FileSource::new('src.'.$action->getLocale(), '')),
                ];
            } else {
                $srcs = [];
                
                foreach($action->getLocales() as $locale => $localeName) {
                    $srcs[] = $this->configureFileSource(Field\FileSource::new('src.'.$locale, $localeName));
                }
                
                $fields = [...$srcs, ...$this->fields];
            }            
        } else {
            $src = $action->entity()->get($this->name().'.src');

            if (empty($src) && in_array($action->name(), ['create', 'edit', 'copy', 'show'])) {
                $fields = [
                    $this->configureFileSource(Field\FileSource::new('src', '')),
                ];
            } else {
                $fields = [
                    $this->configureFileSource(Field\FileSource::new('src', 'Src')),
                    ...$this->fields,
                ];
            }
        }
        
        $fields[] = Field\Value::new('storage')->showable(false)->value($this->getStorageName());
        
        foreach($fields as $field) {
            if ($field instanceof FieldsAwareInterface) {
                throw new InvalidArgumentException('Subfields are not supported!');
            }
            
            $field->rename($this->name().'.'.$field->name());
            $field->parent($this->name());
            $field->group($this->groupName());
        }
        
        return new Fields(...$fields);
    }
    
    /**
     * Returns the accept attribute for the input field.
     *
     * @return string
     */
    public function acceptAttribute(): string
    {
        $field = $this->configureFileSource(Field\FileSource::new('src', ''));
        return $field->acceptAttribute();
    }
    
    /**
     * Processes the show action.
     *
     * @param FieldInterface $field
     * @param EntityInterface $entity
     * @return void
     */
    public function processIndexFile(ActionInterface $action, FieldInterface $field): void
    {
        if ($this->isTranslatable()) {
            $srcField = $action->fields()->get($field->name().'.src.'.$action->getLocale());
        } else {
            $srcField = $action->fields()->get($field->name().'.src');
        }
        
        if ($srcField) {
            $field->html($srcField->render());
        }
    }
    
    /**
     * Processes the save action.
     *
     * @param ActionInterface $action
     * @param File $field
     * @param InputInterface $input
     * @param StoragesInterface $storages
     * @param ResponserInterface $responser
     * @return void
     */
    public function processSave(
        ActionInterface $action,
        File $field,
        InputInterface $input,
        StoragesInterface $storages,
        ResponserInterface $responser,
    ): void {
        // delete file if file src is deleted:
        if (empty($input->get($field->name().'.src'))) {
            $input->set($field->name(), []);
            $field->storable(true);
            return;
        }
        
        if (is_null($this->storeFilenameToField)) {
            return;
        }
        
        $fieldToStore = $action->fields()->get($field->name().'.'.$this->storeFilenameToField);
        
        if (is_null($fieldToStore)) {
            return;
        }
        
        $fields = $action->fields();
        
        if ($fieldToStore->isTranslatable()) {
            foreach(array_keys($action->getLocales()) as $locale) {
                $this->applyFilenameToInput($fields, $field, $input, $action->getLocale(), $locale);
            }
        } else {
            $this->applyFilenameToInput($fields, $field, $input, $action->getLocale());
        }
    }

    /**
     * Apply filename to input.
     *
     * @param FieldsInterface $fields
     * @param File $field
     * @param InputInterface $input
     * @param string $defaultLocale
     * @param null|string $locale
     * @return void
     */
    protected function applyFilenameToInput(
        FieldsInterface $fields,
        File $field,
        InputInterface $input,
        string $defaultLocale,
        null|string $locale = null,
    ) {
        if ($field->isTranslatable()) {
            $loc = $locale ?: $defaultLocale;
            $fieldSrc = $fields->get($field->name().'.src.'.$loc);
            
            if (
                is_null($fieldSrc)
                || ($fieldSrc instanceof FileSource && is_null($fieldSrc->getWriteResponse()))
            ) {
                $fieldSrc = $fields->get($field->name().'.src.'.$defaultLocale);
            }
        } else {
            $fieldSrc = $fields->get($field->name().'.src');
        }
        
        $inputKey = $locale ? $this->storeFilenameToField.'.'.$locale : $this->storeFilenameToField;
        $inputKey = $field->name().'.'.$inputKey;
        
        if (
            $fieldSrc instanceof FileSource
            && !is_null($fieldSrc->getWriteResponse())
            && empty($input->get($inputKey))
        ) {
            $filename = $this->modifyFilename($fieldSrc->getWriteResponse()->originalFilename(), $locale);
            $input->set($inputKey, $filename);
        }
    }
    
    /**
     * Processes the create action.
     *
     * @param File $field
     * @param ViewInterface $view
     * @return void
     */
    public function processCreate(
        ActionInterface $action,
        File $field,
        ViewInterface $view,
        StoragesInterface $storages
    ): void {
        $this->processEdit($action, $field, $view, $storages);
    }
    
    /**
     * Processes the edit action.
     *
     * @param ActionInterface $action
     * @param File $field
     * @param ViewInterface $view
     * @param StoragesInterface $storages
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processEdit(
        ActionInterface $action,
        File $field,
        ViewInterface $view,
        StoragesInterface $storages
    ): void {
        $fields = $action->fields()->parent($field->name());
        $pictureQueue = false;
        $picture = null;
        $file = null;
        $path = null;
        
        if ($field->isTranslatable()) {
            $fileSrcField = $fields->get($field->name().'.src.'.$action->getLocale());
            
            // get first found locale path:
            foreach (array_keys($action->getLocales()) as $locale) {
                if ($path = $action->entity()->get($field->name().'.src.'.$locale, '')) {
                    break;
                }
            }
        } else {
            $fileSrcField = $fields->get($field->name().'.src');
            $path = $field->entity()->get($field->name().'.src', '');
        }
        
        $pictureDefinition = $fileSrcField instanceof FileSource
            ? $fileSrcField->getPictureDefinition()
            : null;
        
        $storageName = $field->entity()->get($field->name().'.storage', $field->getStorageName());

        if ($storages->get($storageName)->exists(path: $path)) {
            $file = $storages->get($storageName)->with('stream', 'mimeType', 'url')->file(path: $path);

            if ($file->isHtmlImage() && $pictureDefinition) {
                $picture = $view->picture(
                    path: $file->path(),
                    resource: $storageName,
                    definition: $pictureDefinition,
                    queue: $pictureQueue,
                )->imgAttr('alt', $file->path());
            }
        }
        
        $field->html($view->render(
            view: 'crud/field/file',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'file' => $file,
                'picture' => $picture,
                'fields' => $fields,
            ],
        ));
    }
    
    /**
     * Processes the show action.
     *
     * @param ActionInterface $action
     * @param FieldInterface $field
     * @param ViewInterface $view
     * @param StoragesInterface $storages
     * @return void
     */
    public function processShowFile(
        ActionInterface $action,
        FieldInterface $field,
        ViewInterface $view,
        StoragesInterface $storages
    ): void {
        $field->html($view->render(
            view: 'crud/field/show/file',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'fields' => $action->fields()->showable()->parent($field->name()),
            ],
        ));
    }

    /**
     * Returns the modified filename.
     *
     * @param string $filename
     * @param null|string $locale
     * @return string
     */
    protected function modifyFilename(string $filename, null|string $locale): string
    {
        if (is_callable($this->filenameModifier)) {
            return call_user_func($this->filenameModifier, $filename, $locale);
        }
        
        return $filename;
    }
    
    /**
     * Returns the configured file source.
     *
     * @param Field\FileSource $fileSource
     * @return Field\FileSource
     */
    protected function configureFileSource(Field\FileSource $fileSource): Field\FileSource
    {
        if (is_callable($this->fileSource)) {
            call_user_func_array($this->fileSource, [$fileSource]);
        }
        
        $this->storageName = $fileSource->getStorageName();
        $fileSource->storageFromField($this->name().'.storage');
        
        return $fileSource;
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