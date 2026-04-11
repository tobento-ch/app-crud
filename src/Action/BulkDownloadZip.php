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

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\FilterProcessorInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\Repository\FileFolderRepository;
use Tobento\Service\FileStorage\Repository\FileRepository;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\FileResponser;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;
use ZipArchive;
use function Tobento\App\Translation\trans;

class BulkDownloadZip extends Action\AbstractAction implements Action\BulkActionInterface
{
    use HasActionProcessor;
    use Traits\HandleBulk;
    use Traits\InteractsWithRequest;
    use Traits\ConfiguresModal;
    
    /**
     * @var bool
     */
    protected bool $onlyPublicStorages = false;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $allowedStorages = null;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $excludedStorages = null;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $allowedExtensions = null;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $excludedExtensions = null;
    
    /**
     * @var null|array<int, string>
     */
    protected null|array $allowedFields = null;
    
    /**
     * @var bool
     */
    protected bool $preserveStructure = false;
    
    /**
     * @var null|callable
     */
    protected $modifyFields = null;
    
    /**
     * Create a new instance.
     *
     * @param string $name Must be sluggable and only of [a-z-] characters.
     * @param null|string $title
     */
    public function __construct(
        protected string $name = 'download-zip',
        null|string $title = null,
    ) {
        if ((bool) preg_match('/^[a-z-_.]+$/u', $name) === false) {
            throw new \InvalidArgumentException(
                sprintf('The name %s must only contain [a-z-_.] characters', $name)
            );
        }
        
        $this->title = $title ?: $name;
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        
        $this->view('crud/bulk/modal');
        $this->modalButtonLabel(trans('Generate'));
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
     * Returns a namespaced field name for this action.
     *
     * @param string $suffix Field-specific suffix.
     * @return string
     */
    public function fieldName(string $suffix): string
    {
        return $this->name() . '_' . $suffix;
    }
    
    /**
     * Restricts ZIP downloads to public storages only.
     *
     * @return static
     */
    public function onlyPublicStorages(): static
    {
        $this->onlyPublicStorages = true;
        return $this;
    }
    
    /**
     * Limits ZIP processing to the specified storage names.
     *
     * @param string ...$storages
     * @return static
     */
    public function onlyStorages(string ...$storages): static
    {
        $this->allowedStorages = $storages;
        return $this;
    }

    /**
     * Excludes the specified storage names from ZIP processing.
     *
     * @param string ...$storages
     * @return static
     */
    public function exceptStorages(string ...$storages): static
    {
        $this->excludedStorages = $storages;
        return $this;
    }
    
    /**
     * Limits ZIP contents to files with the given extensions.
     *
     * @param string ...$extensions
     * @return static
     */
    public function onlyExtensions(string ...$extensions): static
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }
    
    /**
     * Excludes files with the given extensions from the ZIP.
     *
     * @param string ...$extensions
     * @return static
     */
    public function exceptExtensions(string ...$extensions): static
    {
        $this->excludedExtensions = array_map('strtolower', $extensions);
        return $this;
    }
    
    /**
     * Restricts ZIP processing to the specified field names.
     *
     * @param string ...$fields
     * @return static
     */
    public function onlyFields(string ...$fields): static
    {
        $this->allowedFields = $fields;
        return $this;
    }
    
    /**
     * Keeps the original folder structure inside the ZIP.
     *
     * @return static
     */
    public function preserveFolderStructure(): static
    {
        $this->preserveStructure = true;
        return $this;
    }

    /**
     * Flattens all files into the ZIP root directory.
     *
     * @return static
     */
    public function flatten(): static
    {
        $this->preserveStructure = false;
        return $this;
    }
    
    /**
     * Modify fields.
     *
     * @param callable $callback
     *    fn (
     *        ActionInterface $action,
     *        FieldsInterface $fields,
     *        DownloadZipBulkAction $download
     *    ): iterable<FieldInterface>|FieldsInterface
     * @return static
     */
    public function modifyFields(callable $callback): static
    {
        $this->modifyFields = $callback;
        return $this;
    }
    
    /**
     * Returns the handler processing the action.
     *
     * @return callable(mixed...): \Psr\Http\Message\ResponseInterface
     */
    public function getHandler(): callable
    {
        return [$this, 'handle'];
    }
    
    /**
     * Handle action.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function handle(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        return $this->handleBulk(
            action: $this,
            actionProcessor: $actionProcessor,
            requester: $requester,
            responser: $responser,
        );
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
     * @param RequesterInterface $requester
     * @param FilterProcessorInterface $filterProcessor
     * @param ResponserInterface $responser
     * @param FileResponser $fileResponser
     * @return ResponseInterface
     * @throws ActionProcessException
     * @psalm-suppress RedundantCondition
     * @psalm-suppress NoValue
     */
    public function processBulk(
        RequesterInterface $requester,
        FilterProcessorInterface $filterProcessor,
        ResponserInterface $responser,
        FileResponser $fileResponser,
    ): null|ResponseInterface {
        // Process action for validation e.g.
        $storeAction = new Action\Store();
        $storeAction->setController($this->controller());
        $this->actionProcessor()->preprocessAction(action: $storeAction);
        
        $storeAction->setInput($this->fetchInput(requester: $requester, action: $storeAction, fresh: true));
        
        $fields = Fields::fromIterable($this->configureFields($storeAction));
        $storeAction->setFields($fields);
        
        $this->actionProcessor()->processFields(action: $storeAction, entity: new Entity());
        
        // Get input data
        $input = $storeAction->getInput();
        
        // Preserve folder structure
        $preserve = $input->get($this->fieldName('preserve_structure'));

        if ($preserve === '1') {
            $this->preserveFolderStructure();
        } elseif ($preserve === '0') {
            $this->flatten();
        }

        // Only public storages
        if ($input->get($this->fieldName('only_public_storages')) === '1') {
            $this->onlyPublicStorages();
        }

        // Only extensions
        $onlyExt = $input->get($this->fieldName('only_extensions'));
        if (!empty($onlyExt) && is_string($onlyExt)) {
            $exts = array_filter(array_map('trim', explode(',', $onlyExt)));
            if ($exts) {
                $this->onlyExtensions(...$exts);
            }
        }

        // Exclude extensions
        $exceptExt = $input->get($this->fieldName('except_extensions'));
        if (!empty($exceptExt) && is_string($exceptExt)) {
            $exts = array_filter(array_map('trim', explode(',', $exceptExt)));
            if ($exts) {
                $this->exceptExtensions(...$exts);
            }
        }
        
        // Only fields
        $onlyFields = $input->get($this->fieldName('only_fields'));
        if (!empty($onlyFields) && is_array($onlyFields)) {
            $this->onlyFields(...$onlyFields);
        }
        
        // Selection Mode
        $selectionMode = $input->get($this->fieldName('selection_mode'), 'ids'); // or filtered

        // Ids selection mode
        if ($selectionMode === 'ids') {
            $ids = $input->get('ids', []);
            $where = [$this->controller()->entityIdName() => ['in' => $ids]];
            $orderBy = [];
        } else {
            // filtered selection mode
            // Get Index action for filters
            $indexAction = $this->actions()->get('index');

            if (is_null($indexAction)) {
                throw new ActionNotFoundException(actionName: 'index');
            }

            $indexAction->setFields($this->controller()->getConfiguredFields(action: $indexAction));

            // Handle filters:
            $filters = $this->controller()->getConfiguredFilters($indexAction);
            $filters = $filterProcessor->processFilters(filters: $filters, action: $indexAction);

            $where = $filters->getWhereParameters();
            $orderBy = $filters->getOrderByParameters();
        }
        
        $repository = $this->controller()->repository();
        
        // Create a temporary ZIP file
        $tmpZip = tempnam(sys_get_temp_dir(), 'zip_');
        $zip = new ZipArchive();

        $zip->open($tmpZip, ZipArchive::OVERWRITE);
        
        $zipedFiles = $this->zipFiles(zip: $zip, repository: $repository, where: $where, orderBy: $orderBy);

        $zip->close();

        if (! $zipedFiles) {
            $responser->messages()->add(
                level: 'info',
                message: trans('No files found to ZIP.'),
            );
            return null;
        }
        
        // Download ZIP
        return $fileResponser->download(
            file: $tmpZip,
            name: $input->get($this->fieldName('name'), 'files.zip'),
            contentType: 'application/zip',
        );
    }
    
    /**
     * Returns the html of action. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string
    {
        $view->asset('assets/crud/live.js')->attr('type', 'module');
        
        $indexAction = $this->actions()->get('index');
        
        if (is_null($indexAction)) {
            return '';
        }
        
        $createAction = $this->actions()->get('create');
        
        if (is_null($createAction)) {
            $createAction = new Action\Create();
            $createAction->setInput($indexAction->getInput());
        }
        
        $createAction->setContainer($this->container());
        $fields = Fields::fromIterable($this->configureFields($createAction));
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
    
    /**
     * Returns whether to display the button to perform the action.
     *
     * @return bool
     */
    public function displayButton(): bool
    {
        return true;
    }

    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        $fields = Fields::fromIterable($this->configureDefaultFields($action));

        if ($this->modifyFields) {
            $modified = ($this->modifyFields)($action, $fields, $this);
            return Fields::fromIterable($modified);
        }

        return $fields;
    }
    
    /**
     * Returns the configured default fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureDefaultFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Text(
            name: $this->fieldName('name'),
            label: trans('ZIP Filename')
        )
            ->group(trans('Options'))
            ->validate('required|htmlclean')
            ->defaultValue(ucfirst($this->controller()->resourceName()));

        yield new Field\Select(
            name: $this->fieldName('selection_mode'),
            label: trans('Records to ZIP')
        )
            ->group(trans('Options'))
            ->options([
                'ids' => trans('Selected Records'),
                'filtered' => trans('All Filtered Records'),
            ]);
            
        yield new Field\Radios(
            name: $this->fieldName('preserve_structure'),
            label: trans('Preserve Folder Structure')
        )
            ->group(trans('Options'))
            ->options(['0' => trans('No'), '1' => trans('Yes')])
            ->selected(value: '0', action: 'create|edit')
            ->displayInline();

        yield new Field\Radios(
            name: $this->fieldName('only_public_storages'),
            label: trans('Only Public Storages')
        )
            ->group(trans('Filters'))
            ->options(['0' => trans('No'), '1' => trans('Yes')])
            ->selected(value: '0', action: 'create|edit')
            ->displayInline();

        yield new Field\Text(
            name: $this->fieldName('only_extensions'),
            label: trans('Only Extensions')
        )
            ->group(trans('Filters'))
            ->validate('string|maxLen:2000')
            ->infoText(trans('Comma seprated: jpg, pdf'));

        yield new Field\Text(
            name: $this->fieldName('except_extensions'),
            label: trans('Exclude Extensions')
        )
            ->group(trans('Filters'))
            ->validate('string|maxLen:2000')
            ->infoText(trans('Comma seprated: jpg, pdf'));
        
        $fileFieldOptions = $this->fileFieldOptions();
        
        if (count($fileFieldOptions) > 1) {
            yield new Field\Checkboxes(
                name: $this->fieldName('only_fields'),
                label: trans('Only File Fields')
            )
                ->group(trans('Filters'))
                ->options($fileFieldOptions);            
        }
    }
    
    /**
     * Returns an array of file‑field options.
     *
     * @return array<string, string> Field name => label
     */
    protected function fileFieldOptions(): array
    {
        $fields = $this->controller()->getConfiguredFields(action: $this);

        $options = [];

        foreach ($fields as $field) {

            if (
                $field instanceof Field\FileSource
                || $field instanceof Field\File
                || $field instanceof Field\Files
            ) {
                $options[$field->name()] = $field->label() ?: ucfirst($field->name());
            }
        }

        return $options;
    }
    
    /**
     * Adds files from the given repository to the ZIP archive.
     *
     * The method selects the appropriate zipping strategy based on the
     * repository type. Returns true if at least one file was added.
     *
     * @param ZipArchive $zip The ZIP archive to write into.
     * @param RepositoryInterface $repository The repository providing file entities.
     * @param array $where Filtering conditions for selecting files.
     * @param array $orderBy Sorting parameters for file selection.
     * @return bool True if one or more files were zipped, otherwise false.
     */
    protected function zipFiles(
        ZipArchive $zip,
        RepositoryInterface $repository,
        array $where,
        array $orderBy
    ): bool {
        if (
            $repository instanceof FileRepository
            || $repository instanceof FileFolderRepository
        ) {
            return $this->zipFilesFromFileRepository($zip, $repository, $where, $orderBy);
        }
        
        return $this->zipFilesFromRepositoryUsingFields($zip, $repository, $where, $orderBy);
    }
    
    /**
     * Adds files from a FileRepository or FileFolderRepository to the ZIP archive.
     *
     * Iterates over all matching file entities and writes their contents into
     * the ZIP. Folders are skipped. Returns true if at least one file was added.
     *
     * @param ZipArchive $zip The ZIP archive to write into.
     * @param FileRepository|FileFolderRepository $repository The file-based repository.
     * @param array $where Filtering conditions for selecting files.
     * @param array $orderBy Sorting parameters for file selection.
     * @return bool True if one or more files were zipped, otherwise false.
     */
    protected function zipFilesFromFileRepository(
        ZipArchive $zip,
        FileRepository|FileFolderRepository $repository,
        array $where,
        array $orderBy
    ): bool {
        $storages = $this->container()->get(StoragesInterface::class);
        
        $hasFiles = false;
        
        foreach ($repository->findAll(where: $where, orderBy: $orderBy) as $file) {
            if (!$file instanceof FileInterface) {
                continue;
            }

            $storageName = $file->storageName();
            $ext = strtolower($file->extension());

            if (!empty($this->allowedStorages)
                && !in_array($storageName, $this->allowedStorages, true)
            ) {
                continue;
            }

            if (!empty($this->excludedStorages)
                && in_array($storageName, $this->excludedStorages, true)
            ) {
                continue;
            }

            if ($this->onlyPublicStorages) {
                if (!$storages->has($storageName)) {
                    continue;
                }
                
                if (! $storages->get($storageName)->isPublic()) {
                    continue;
                }
            }

            if (!empty($this->allowedExtensions)
                && !in_array($ext, $this->allowedExtensions, true)
            ) {
                continue;
            }

            if (!empty($this->excludedExtensions)
                && in_array($ext, $this->excludedExtensions, true)
            ) {
                continue;
            }

            // Add file to ZIP
            $zipPath = $this->preserveStructure ? $file->path() : $file->name();
            $zip->addFromString($zipPath, $file->content() ?? '');
            $hasFiles = true;
        }

        return $hasFiles;
    }
    
    /**
     * Adds files to the ZIP archive by reading FileField and FilesField values
     * from entities returned by the repository. Returns true if at least one
     * file was added.
     *
     * @param ZipArchive $zip The ZIP archive to write into.
     * @param RepositoryInterface $repository The repository providing entities.
     * @param array $where Filtering conditions for selecting entities.
     * @param array $orderBy Sorting parameters for entity selection.
     *
     * @return bool True if one or more files were zipped, otherwise false.
     */
    protected function zipFilesFromRepositoryUsingFields(
        ZipArchive $zip,
        RepositoryInterface $repository,
        array $where,
        array $orderBy
    ): bool {
        // Get fields configured for this controller
        $fields = $this->controller()->getConfiguredFields(action: $this);

        // Collect only FileField and FilesField
        $fileFields = [];
        foreach ($fields as $field) {
            if (
                $field instanceof Field\FileSource
                || $field instanceof Field\File
                || $field instanceof Field\Files
            ) {
                $fileFields[] = $field;
            }
        }

        // Filter allowed fields
        if (!empty($this->allowedFields)) {
            $fileFields = array_filter($fileFields, function ($field) {
                return in_array($field->name(), $this->allowedFields, true);
            });
        }
        
        if (empty($fileFields)) {
            return false;
        }
        
        $hasFiles = false;
        
        foreach ($repository->findAll(where: $where, orderBy: $orderBy) as $entity) {
            // Convert raw objects into CRUD Entity
            if (is_object($entity)) {
                $entity = $this->controller()->createEntityFromObject($entity);
            } else {
                continue;
            }
            
            foreach ($fileFields as $field) {
                if ($this->addZipFilesFromFileField($zip, $entity, $field)) {
                    $hasFiles = true;
                }
            }
        }

        return $hasFiles;
    }

    /**
     * Adds all files referenced by the given file‑related field to the ZIP archive.
     *
     * @param ZipArchive $zip The ZIP archive to add files to.
     * @param EntityInterface $entity The entity containing the field values.
     * @param Field\File|Field\Files|Field\FileSource $field The file field to process.
     * @return bool True if at least one file was added.
     */
    protected function addZipFilesFromFileField(
        ZipArchive $zip,
        EntityInterface $entity,
        Field\File|Field\Files|Field\FileSource $field,
    ): bool {
        $value = $entity->get($field->name());
        
        if (empty($value)) {
            return false;
        }

        // Normalize to array for FilesField
        $items = is_array($value) && array_is_list($value)
            ? $value
            : [$value];

        $added = false;
                
        foreach ($items as $item) {
            // 1. Resolve the src (supports translated values)
            if ($field instanceof Field\FileSource) {
                // FileSource: $item is a string or translated array
                $src = is_array($item) ? reset($item) : $item;
            } else {
                // FileField / FilesField: $item must be an array with 'src'
                if (!is_array($item) || !isset($item['src'])) {
                    continue;
                }

                $src = $item['src'];
                if (is_array($src)) {
                    $src = reset($src);
                }
            }

            if (!is_string($src) || $src === '') {
                continue;
            }
            
            // 2. Resolve the storage name
            if ($field instanceof Field\FileSource) {
                // FileSource: storage is NOT in the value
                $storage =
                    $entity->get($field->getStorageField()) // storageFromField()
                    ?: $field->getStorageName(); // default storage on field
            } else {
                // FileField / FilesField: storage is inside the value
                $storage = $item['storage'] ?? null;
            }
            
            if (!$storage) {
                continue;
            }
            
            // 3. Load file content from storage
            $content = $this->loadFileContent($storage, $src);

            if ($content === null) {
                continue;
            }

            // 4. Add to ZIP
            $zipPath = $this->preserveStructure ? $src : basename($src);
            $zip->addFromString($zipPath, $content);
            $added = true;
        }

        return $added;
    }
    
    /**
     * Returns the file content from the given storage or null if not found.
     *
     * @param string $storageName
     * @param string $path
     * @return null|string
     */
    protected function loadFileContent(string $storageName, string $path): null|string
    {
        $storages = $this->container()->get(StoragesInterface::class);
        
        if (!$storages->has($storageName)) {
            return null;
        }
        
        $storage = $storages->get($storageName);
        
        if ($this->onlyPublicStorages && !$storage->isPublic()) {
            return null;
        }
        
        if (!empty($this->allowedStorages) && !in_array($storageName, $this->allowedStorages, true)) {
            return null;
        }

        if (!empty($this->excludedStorages) && in_array($storageName, $this->excludedStorages, true)) {
            return null;
        }
        
        try {
            $file = $storage->with('stream', 'mimeType')->file(path: $path);
            
            $ext = strtolower($file->extension());
            
            // Only allow specific extensions
            if (
                is_array($this->allowedExtensions)
                && !in_array($ext, $this->allowedExtensions, true)
            ) {
                return null;
            }

            // Exclude specific extensions
            if (
                is_array($this->excludedExtensions)
                && in_array($ext, $this->excludedExtensions, true)
            ) {
                return null;
            }
            
            return $file->content();
        } catch (FileNotFoundException $e) {
            return null;
        }
    }
}