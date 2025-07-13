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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\UploadErrorException;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Media\Exception\CreateUploadedFileException;
use Tobento\App\Media\Exception\UploadException;
use Tobento\App\Media\Exception\UploadedFileException;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\App\Media\FileStorage\FileWriterInterface;
use Tobento\App\Media\FileStorage\FileWriter;
use Tobento\App\Media\FileStorage\Writer;
use Tobento\App\Media\FileStorage\WriteResponseInterface;
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\App\Media\Upload\UploadedFileFactoryInterface;
use Tobento\App\Media\Upload\ValidatorInterface;
use Tobento\App\Media\Upload\Validator;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\Picture\Definition\ArrayDefinition;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\DefinitionsInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Support\Str;
use Tobento\Service\Validation\Rule\Passes;
use Tobento\Service\Validation\ValidationInterface;
use Tobento\Service\View\ViewInterface;

/**
 * FileSource field enables you to upload a single file only.
 */
class FileSource extends AbstractField
{
    /**
     * @var string
     */
    protected string $storageName = 'uploads';
    
    /**
     * @var null|string
     */
    protected null|string $storageField = null;
    
    /**
     * @var string|callable
     */
    protected $folderPath = '';
    
    /**
     * @var null|array<array-key, string>
     */
    protected null|array $allowedFileExtensions = null;
    
    /**
     * @var null|int
     */
    protected null|int $maxFileSizeInKb = null;
    
    /**
     * @var null|callable
     */
    protected $validator = null;
    
    /**
     * @var null|callable
     */
    protected $fileWriter = null;
    
    /**
     * @var null|WriteResponseInterface
     */
    protected $writeResponse = null;
    
    /**
     * @var null|DefinitionInterface
     */
    protected null|DefinitionInterface $pictureDefinition = null;
    
    /**
     * @var bool
     */
    protected bool $pictureQueue = false;
    
    /**
     * @var null|string
     */
    protected null|string $imageEditorTemplate = null;
    
    /**
     * @var null|string
     */
    protected null|string $pictureEditorTemplate = null;
    
    /**
     * @var array<array-key, string>
     */
    protected array $pictureEditorDefinitions = [];
    
    /**
     * @var array<array-key, string>
     */
    protected array $messageLevelsToDisplay = [];
    
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
        $this->process('store:before|update:before', [$this, 'processBeforeSave']);
        $this->process('store|update', [$this, 'processSave']);
        $this->process('edit', [$this, 'processEdit']);
        $this->process('delete', [$this, 'processDelete']);
        $this->process('show', [$this, 'processShowFile']);
        
        // call validate as to add rule:
        $this->validate([]);
        
        $this->picture(definition: new ArrayDefinition('crud-file-src', [
            'img' => [
                'src' => [100, 100],
                'loading' => 'lazy',
            ],
            'sources' => [
                [
                    'srcset' => [
                        '' => [100, 100],
                    ],
                    'type' => 'image/webp',
                ],
            ],
        ]));
        
        $this->configure();
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
     * Sets the storage name.
     *
     * @param string $name
     * @return static $this
     */
    public function storage(string $name): static
    {
        $this->storageName = $name;
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
     * Sets the storage from field.
     *
     * @param string $name
     * @return static $this
     */
    public function storageFromField(string $name): static
    {
        $this->storageField = $name;
        return $this;
    }

    /**
     * Returns the storage field.
     *
     * @return string
     */
    public function getStorageField(): string
    {
        return $this->storageField ?: $this->name().'.storage';
    }
    
    /**
     * Sets the folder path.
     *
     * @param string|callable $path
     * @return static $this
     */
    public function folder(string|callable $path): static
    {
        $this->folderPath = $path;
        return $this;
    }

    /**
     * Sets the allowed extensions.
     *
     * @param string ...$extension
     * @return static $this
     */
    public function allowedExtensions(string ...$extension): static
    {
        $this->allowedFileExtensions = $extension;
        return $this;
    }
    
    /**
     * Returns the allowed extensions.
     *
     * @return array<array-key, string>
     */
    public function getAllowedExtensions(): array
    {
        return is_array($this->allowedFileExtensions)
            ? $this->allowedFileExtensions
            : ['jpg', 'png', 'gif', 'webp'];
    }
    
    /**
     * Returns the accept attribute for the input field.
     *
     * @return string
     */
    public function acceptAttribute(): string
    {
        $acceptAttributes = array_map(
            fn (string $extension): string => sprintf('.%s', $extension),
            $this->getAllowedExtensions()
        );
        
        return implode(',', $acceptAttributes);
    }
    
    /**
     * Sets the max file size in Kb.
     *
     * @param int $kb
     * @return static $this
     */
    public function maxFileSizeInKb(null|int $kb): static
    {
        $this->maxFileSizeInKb = $kb;
        return $this;
    }
    
    /**
     * Sets the validator to be used.
     *
     * @param callable $validator
     * @return static $this
     */
    public function validator(callable $validator): static
    {
        $this->validator = $validator;
        return $this;
    }
    
    /**
     * Sets the file writer.
     *
     * @param callable $fileWriter
     * @return static $this
     */
    public function fileWriter(callable $fileWriter): static
    {
        $this->fileWriter = $fileWriter;
        return $this;
    }

    /**
     * Sets the picture definition.
     *
     * @param array|DefinitionInterface $definition
     * @return static $this
     */
    public function picture(null|array|DefinitionInterface $definition): static
    {
        if (is_array($definition)) {
            $definition = new ArrayDefinition(name: 'crud-file-src', definition: $definition);
        }
        
        $this->pictureDefinition = $definition;
        return $this;
    }
    
    /**
     * Return the picture definition.
     *
     * @param array|DefinitionInterface $definition
     * @return null|DefinitionInterface
     */
    public function getPictureDefinition(): null|DefinitionInterface
    {
        return $this->pictureDefinition;
    }
    
    /**
     * Set if the picture generation should be queued.
     *
     * @param bool $queue
     * @return static $this
     */
    public function pictureQueue(bool $queue): static
    {
        $this->pictureQueue = $queue;
        return $this;
    }
    
    /**
     * Sets the image editor template to be used.
     *
     * @param null|string $template
     * @return static $this
     */
    public function imageEditor(null|string $template): static
    {
        $this->imageEditorTemplate = $template;
        return $this;
    }
    
    /**
     * Sets the picture editor definitions.
     *
     * @param null|string $template
     * @param array<array-key, string> $definitions
     * @return static $this
     */
    public function pictureEditor(null|string $template, array $definitions): static
    {
        $this->pictureEditorTemplate = $template;
        $this->pictureEditorDefinitions = $definitions;
        return $this;
    }

    /**
     * Sets the file write response.
     *
     * @param null|WriteResponseInterface $writeResponse
     * @return static $this
     */
    public function writeResponse(null|WriteResponseInterface $writeResponse): static
    {
        $this->writeResponse = $writeResponse;
        return $this;
    }
    
    /**
     * Returns the file write response.
     *
     * @return null|WriteResponseInterface
     */
    public function getWriteResponse(): null|WriteResponseInterface
    {
        return $this->writeResponse;
    }
    
    /**
     * Sets the message levels to display.
     *
     * @param string ...$level
     * @return static $this
     */
    public function displayMessages(string ...$level): static
    {
        $this->messageLevelsToDisplay = $level;
        return $this;
    }
    
    /**
     * Returns the configured uploaded file validator.
     *
     * @return ValidatorInterface
     */
    protected function configureValidator(): ValidatorInterface
    {
        if (is_callable($this->validator)) {
            return call_user_func($this->validator);
        }
        
        return new Validator(
            allowedExtensions: $this->allowedFileExtensions ?: ['jpg', 'png', 'gif', 'webp'],
            strictFilenameCharacters: true,
            maxFilenameLength: 255,
            maxFileSizeInKb: $this->maxFileSizeInKb,
        );
    }
    
    /**
     * Returns the configured file writer.
     *
     * @param StorageInterface $storage
     * @return FileWriterInterface
     */
    protected function configureFileWriter(StorageInterface $storage): FileWriterInterface
    {
        if (is_callable($this->fileWriter)) {
            return call_user_func_array($this->fileWriter, [$storage]);
        }

        return new FileWriter(
            storage: $storage,
            filenames: FileWriter::ALNUM, // RENAME, ALNUM, KEEP
            duplicates: FileWriter::RENAME, // RENAME, OVERWRITE, DENY
            folders: FileWriter::ALNUM, // or KEEP
            folderDepthLimit: 5,
            writers: [
                new Writer\ImageWriter(
                    imageProcessor: new ImageProcessor(
                        actions: [
                            'orientate' => [],
                            'resize' => ['width' => 2000],
                        ],
                    ),
                ),
                new Writer\SvgSanitizerWriter(),
            ],
        );
    }
    
    /**
     * Processes the index action.
     *
     * @param FileSource $field
     * @param ViewInterface $view
     * @param StoragesInterface $storages
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processIndexFile(FileSource $field, ViewInterface $view, StoragesInterface $storages): void
    {
        $path = $field->entity()->get($field->name(), '');
        $storageName = $field->entity()->get($field->getStorageField(), $field->getStorageName());

        if (empty($path)) {
            $field->html('');
            return;
        }
        
        if ($storages->get($storageName)->exists(path: $path)) {
            $file = $storages->get($storageName)->with('stream', 'mimeType')->file(path: $path);
            
            if ($file->isHtmlImage() && $this->pictureDefinition) {
                $field->html((string)$view->picture(
                    path: $path,
                    resource: $storageName,
                    definition: $this->pictureDefinition,
                    queue: $this->pictureQueue,
                )->imgAttr('alt', $file->path()));
            } else {
                $field->html($view->esc($file->path()));
            }
            
            return;
        }

        $field->html('');
    }
    
    /**
     * Processes the before save action.
     *
     * @param ActionInterface $action
     * @param FileSource $field
     * @param InputInterface $input
     * @param StoragesInterface $storages
     * @param UploadedFileFactoryInterface $uploadedFileFactory
     * @return void
     */
    public function processBeforeSave(
        ActionInterface $action,
        FileSource $field,
        InputInterface $input,
        StoragesInterface $storages,
        UploadedFileFactoryInterface $uploadedFileFactory
    ): void {
        if (! $input->has($field->name())) {
            return;
        }

        $inputFile = $input->get($field->name());
        
        // Handle different input files:
        switch (true) {
            case $inputFile instanceof UploadedFileInterface:
                return;
            case is_array($inputFile):
                // Support uploading files file storages (e.g. copy action or filemanager):
                $storageName = $field->entity()->get($field->getStorageField(), $field->getStorageName());
                $storageName = $inputFile['storage'] ?? $storageName;
                $path = $inputFile['path'] ?? '';
                
                if (!is_string($storageName) || !is_string($path)) {
                    $input->set($field->name(), new UploadErrorException(
                        message: 'Unable to upload file.',
                    ));
                    return;
                }
                
                if (! $storages->has($storageName)) {
                    $input->set($field->name(), new UploadErrorException(
                        message: 'Unable to upload the file :path as the file storage :storage does not exist.',
                        parameters: [':path' => $path, ':storage' => $storageName],
                    ));
                    return;
                }
                
                $storage = $storages->get($storageName);
                
                try {
                    $file = $storage->with('stream', 'mimeType', 'size')->file(path: $path);
                    $uploadedFile = $uploadedFileFactory->createFromStorageFile(file: $file);
                    $input->set($field->name(), $uploadedFile);
                } catch (FileNotFoundException|CreateUploadedFileException $e) {
                    $input->set($field->name(), new UploadErrorException(
                        message: 'Unable to upload the file :path from the file storage :storage: :message',
                        parameters: [':path' => $path, ':storage' => $storageName, ':message' => $e->getMessage()],
                    ));
                }
                
                return;
        }
    }
    
    /**
     * Processes the save action.
     *
     * @param ActionInterface $action
     * @param FileSource $field
     * @param InputInterface $input
     * @param StoragesInterface $storages
     * @param PictureGeneratorInterface $pictureGenerator
     * @param EventDispatcherInterface $eventDispatcher
     * @param ResponserInterface $responser
     * @return void
     */
    public function processSave(
        ActionInterface $action,
        FileSource $field,
        InputInterface $input,
        StoragesInterface $storages,
        PictureGeneratorInterface $pictureGenerator,
        EventDispatcherInterface $eventDispatcher,
        ResponserInterface $responser,
    ): void {
        // no file at all:
        if (! $input->has($field->name())) {
            if ($action->entity()->has($field->name())) {
                $input->set($field->name(), $action->entity()->get($field->name()));
            }
            return;
        }
        
        $inputFile = $input->get($field->name());
        
        // Handle different input files:
        switch (true) {
            case empty($inputFile):
                // Delete old file if exists:
                $path = $field->entity()->get($field->name(), '');
                $storageName = $field->entity()->get($field->getStorageField(), $field->getStorageName());
                $storage = $storages->get($storageName);
                
                if (
                    $path !== ''
                    && $storage->exists(path: $path)
                ) {
                    $storage->delete(path: $path);
                    
                    $this->deletedFileSource($pictureGenerator, $eventDispatcher, $path);
                    
                    if (in_array('notice', $this->messageLevelsToDisplay)) {
                        $responser->messages()->add(
                            level: 'notice', 
                            message: 'Deleted file :path',
                            parameters: [':path' => $path],
                        );
                    }
                }
                
                $input->set($field->name(), '');
                return;
            case $inputFile instanceof UploadedFileInterface:
                // handle no file selected:
                if ($inputFile->getError() === 4) {
                    if ($action->entity()->has($field->name())) {
                        $input->set($field->name(), $action->entity()->get($field->name()));
                    } else {
                        $input->delete($field->name());
                    }
                    return;
                }
                
                // handle folder:
                $storage = $storages->get($field->getStorageName());
                
                if (is_callable($this->folderPath)) {
                    $folderPath = call_user_func_array($this->folderPath, [$storage]);
                } else {
                    $folderPath = $this->folderPath;
                }
                                
                if (! $storage->folderExists($folderPath)) {
                    $storage->createFolder($folderPath);
                }
                
                // handle upload:
                $writer = $this->configureFileWriter($storage);
                
                try {
                    $writeResponse = $writer->writeUploadedFile($inputFile, $folderPath);
                    $field->writeResponse($writeResponse);
                } catch (WriteException $e) {
                    if (in_array('error', $this->messageLevelsToDisplay)) {
                        $responser->messages()->add(level: 'error', message: $e->getMessage(), parameters: $e->parameters());
                    }
                    
                    return;
                }
                
                $messages = $writeResponse->messages()->only(levels: $this->messageLevelsToDisplay);

                // add messages to responser and prepend file path to messages:
                foreach($messages as $message) {
                    $responser->messages()->addMessage($message->withMessage($writeResponse->path().': '.$message->message()));
                }
                
                $input->set($field->name(), $writeResponse->path());
                
                // Delete old file if exists:
                $path = $field->entity()->get($field->name(), '');
                $storageName = $field->entity()->get($field->getStorageField(), $field->getStorageName());
                $storage = $storages->get($storageName);
                
                if (
                    $path !== ''
                    && $path !== $writeResponse->path()
                    && $storage->exists(path: $path)
                ) {
                    $storage->delete(path: $path);
                    
                    $this->deletedFileSource($pictureGenerator, $eventDispatcher, $path);
                    
                    if (in_array('notice', $this->messageLevelsToDisplay)) {
                        $responser->messages()->add(
                            level: 'notice', 
                            message: 'Deleted file :path',
                            parameters: [':path' => $path],
                        );
                    }
                }
                
                return;
        }
    }
    
    /**
     * Processes the create action.
     *
     * @param FileSource $field
     * @param ViewInterface $view
     * @return void
     */
    public function processCreate(
        ActionInterface $action,
        FileSource $field,
        ViewInterface $view,
        StoragesInterface $storages
    ): void {
        $this->processEdit($action, $field, $view, $storages);
    }
    
    /**
     * Processes the edit action.
     *
     * @param ActionInterface $action
     * @param FileSource $field
     * @param ViewInterface $view
     * @param StoragesInterface $storages
     * @param null|DefinitionsInterface $definitions
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processEdit(
        ActionInterface $action,
        FileSource $field,
        ViewInterface $view,
        StoragesInterface $storages,
        null|DefinitionsInterface $definitions = null,
    ): void {
        // File:
        $file = null;
        $picture = null;
        $imageEditorUrl = null;
        $pictureEditorUrl = null;
        $path = $field->entity()->get($field->name(), '');
        $storageName = $field->entity()->get($field->getStorageField(), $field->getStorageName());

        if ($storages->get($storageName)->exists(path: $path)) {
            $file = $storages->get($storageName)->with('stream', 'mimeType')->file(path: $path);
            
            if ($file->isHtmlImage() && $this->pictureDefinition) {
                $picture = $view->picture(
                    path: $path,
                    resource: $storageName,
                    definition: $this->pictureDefinition,
                    queue: $this->pictureQueue,
                )->imgAttr('alt', $file->path());
            }
            
            if ($file->isHtmlImage() && $this->imageEditorTemplate) {
                $imageEditorUrl = $view->routeUrl(
                    'media.image.editor',
                    ['template' => $this->imageEditorTemplate, 'storage' => $storageName, 'path' => $file->path()]
                );
            }
            
            if ($file->isHtmlImage() && $definitions && !empty($this->pictureEditorTemplate)) {
                $pictureEditorUrl = $view->routeUrl(
                    'media.picture.editor',
                    [
                        'template' => $this->pictureEditorTemplate,
                        'storage' => $storageName,
                        'path' => $file->path(),
                        'definitions' => $this->pictureEditorDefinitions,
                    ]
                );
            }
        }
        
        // View:
        $field->html($view->render(
            view: 'crud/field/file-source',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'file' => $file,
                'picture' => $picture,
                'storageName' => $storageName,
                'imageEditorUrl' => $imageEditorUrl,
                'pictureEditorUrl' => $pictureEditorUrl,
            ],
        ));
    }
    
    /**
     * Processes the show action.
     *
     * @param ActionInterface $action
     * @param FileSource $field
     * @param ViewInterface $view
     * @param StoragesInterface $storages
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processShowFile(
        ActionInterface $action,
        FileSource $field,
        ViewInterface $view,
        StoragesInterface $storages
    ): void {
        $file = null;
        $picture = null;
        $path = $field->entity()->get($field->name(), '');
        $storageName = $field->entity()->get($field->getStorageField(), $field->getStorageName());

        if ($storages->get($storageName)->exists(path: $path)) {
            $file = $storages->get($storageName)->with('stream', 'mimeType', 'width')->file(path: $path);
            
            if ($file->isHtmlImage() && $this->pictureDefinition) {
                $picture = $view->picture(
                    path: $path,
                    resource: $storageName,
                    definition: $this->pictureDefinition,
                    queue: $this->pictureQueue,
                )->imgAttr('alt', $file->path());
            }
        }
        
        // View:
        $field->html($view->render(
            view: 'crud/field/show/file-source',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'file' => $file,
                'picture' => $picture,
                'storageName' => $storageName,
            ],
        ));
    }
    
    /**
     * Processes the delete action.
     *
     * @param ActionInterface $action
     * @param FileSource $field
     * @param InputInterface $input
     * @param StoragesInterface $storages
     * @param PictureGeneratorInterface $pictureGenerator
     * @param EventDispatcherInterface $eventDispatcher
     * @param ResponserInterface $responser
     * @return void
     */
    public function processDelete(
        ActionInterface $action,
        FileSource $field,
        InputInterface $input,
        StoragesInterface $storages,
        PictureGeneratorInterface $pictureGenerator,
        EventDispatcherInterface $eventDispatcher,
        ResponserInterface $responser,
    ): void {
        $storageName = $field->entity()->get($field->getStorageField(), $field->getStorageName());
        $storage = $storages->get($storageName);
        
        // Delete old file if exists:
        $path = $field->entity()->get($field->name(), '');

        if (
            $path !== ''
            && $storage->exists(path: $path)
        ) {
            $storage->delete(path: $path);
            
            $this->deletedFileSource($pictureGenerator, $eventDispatcher, $path);

            if (in_array('notice', $this->messageLevelsToDisplay)) {
                $responser->messages()->add(
                    level: 'notice', 
                    message: 'Deleted file :path',
                    parameters: [':path' => $path],
                );
            }
        }
    }
    
    /**
     * Deletes the generated pictures for the given path.
     *
     * @param PictureGeneratorInterface $pictureGenerator
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $path
     * @return void
     */
    protected function deletedFileSource(
        PictureGeneratorInterface $pictureGenerator,
        EventDispatcherInterface $eventDispatcher,
        string $path
    ): void {
        if ($this->pictureDefinition) {
            $pictureGenerator->pictureRepository()->delete(
                path: $path,
                definition: $this->pictureDefinition,
            );
        }
        
        $eventDispatcher->dispatch(new \Tobento\App\Crud\Event\FileSourceDeleted($path));
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
                ? [$parameter, $this->uploadValidationRule()]
                : array_merge($parameter, [$this->uploadValidationRule()]);
        }

        $this->validate = $parameters;
        return $this;
    }
    
    /**
     * Returns the upload validation rule.
     *
     * @return Passes
     */
    protected function uploadValidationRule(): Passes
    {
        return new Passes(
            passes: function(mixed $value, ValidationInterface $validation): bool {
                
                if ($value === null) {
                    return true;
                }
                
                $uploadedFiles = is_array($value) ? $value : [$value];
                $validator = $this->configureValidator();
                $valid = true;

                foreach($uploadedFiles as $file) {
                    try {
                        if ($file instanceof UploadErrorException) {
                            throw $file;
                        }
                        
                        if (!$file instanceof UploadedFileInterface) {
                            throw new UploadException('The uploaded file is invalid.');
                        }

                        $validator->validateUploadedFile($file);
                    } catch (UploadException $e) {
                        if (
                            $e instanceof UploadedFileException
                            && $e->uploadedFile()->getError() === 4 // no file uploaded
                        ) {
                            continue;
                        }

                        $validation->errors()->add(
                            level: 'error',
                            message: $e->getMessage(),
                            parameters: $e->parameters(),
                            key: $validation->key(),  
                        );
                        
                        $valid = false;
                    }
                }
                
                return $valid;
            },
            errorMessage: 'The uploaded file is invalid.',
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