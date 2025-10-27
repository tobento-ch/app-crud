# App Crud

A simple app CRUD.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Crud Boot](#crud-boot)
    - [Crud Controller](#crud-controller)
        - [Create Controller](#create-controller)
            - [Entity Mapping](#entity-mapping)
            - [Entity Actions](#entity-actions)
        - [Configure Fields](#configure-fields)
        - [Configure Actions](#configure-actions)
        - [Configure Filters](#configure-filters)
        - [Route Controller](#route-controller)
        - [Route Permissions](#route-permissions)
    - [Crud Write Repository](#crud-write-repository)
    - [Fields](#fields)
        - [Build in Fields](#build-in-fields)
            - [Checkboxes Field](#checkboxes-field)
            - [File Field](#file-field)
            - [Files Field](#files-field)
            - [FileSource Field](#filesource-field)
            - [Group Field](#group-field)
            - [Html Field](#html-field)
            - [Items Field](#items-field)
            - [Options Field](#options-field)
            - [PrimaryId Field](#primaryid-field)
            - [Radios Field](#radios-field)
            - [Select Field](#select-field)
            - [Single Options Field](#single-options-field)
            - [Slug Field](#slug-field)
            - [Text Field](#text-field)
            - [Textarea Field](#textarea-field)
            - [TextEditor Field](#texteditor-field)
            - [Value Field](#value-field)
        - [Different Fields Per Action](#different-fields-per-action)
        - [Validate Field](#validate-field)
        - [Translatable Field](#translatable-field)
        - [Unstorable Field](#unstorable-field)
        - [Readonly And Disabled Field](#readonly-and-disabled-field)
        - [Formatting Field Value](#formatting-field-value)
        - [Live Field](#live-field)
        - [Field Grouping](#field-grouping)
        - [Field Texts](#field-texts)
        - [Field Resolving](#field-resolving)
        - [Custom Field Action](#custom-field-action)
    - [Actions](#actions)
        - [Build in Actions](#build-in-actions)
            - [Index Action](#index-action)
            - [Create Action](#create-action)
            - [Store Action](#store-action)
            - [Edit Action](#edit-action)
            - [Update Action](#update-action)
            - [Copy Action](#copy-action)
            - [Show Action](#show-action)
            - [Show JSON Action](#show-json-action)
            - [Delete Action](#delete-action)
            - [Bulk Delete Action](#bulk-delete-action)
            - [Bulk Edit Action](#bulk-edit-action)
            - [Bulk Tree Update Action](#bulk-tree-update-action)
        - [Buttons](#buttons)
            - [Creating Buttons](#creating-buttons)
            - [Adding Buttons](#adding-buttons)
            - [Removing Buttons](#removing-buttons)
            - [Reorder Buttons](#reorder-buttons)
            - [Modify Buttons](#modify-buttons)
            - [Grouping Buttons](#grouping-buttons)
            - [Display Buttons Conditionally](#display-buttons-conditionally)
            - [Confirming Button Action](#confirming-button-action)
            - [AJAX Button Action](#ajax-button-action)
            - [Set Buttons](#set-buttons)
        - [Custom Action](#custom-action)
    - [Filters](#filters)
        - [Build in Filters](#build-in-filters)
            - [Checkboxes Filter](#checkboxes-filter)
            - [Clear Button Filter](#clear-button-filter)
            - [Columns Filter](#columns-filter)
            - [Datalist Filter](#datalist-filter)
            - [Editable Columns Filter](#editable-columns-filter)
            - [Fields Filter](#fields-filter)
            - [Fields Sort Order Filter](#fields-sort-order-filter)
            - [Group Filter](#group-filter)
            - [Input Filter](#input-filter)
            - [Locale Filter](#locale-filter)
            - [Menu Filter](#menu-filter)
            - [Modal Button Filter](#modal-button-filter)
            - [Pagination Filter](#pagination-filter)
            - [Pagination Items Per Page Filter](#pagination-items-per-page-filter)
            - [Radios Filter](#radios-filter)
            - [Select Filter](#select-filter)
            - [Views Filter](#views-filter)
        - [Filter Groups](#filter-groups)
        - [Display Filters Conditionally](#display-filters-conditionally)
        - [Filter Processor](#filter-processor)
        - [Filter Limitations](#filter-limitations)
    - [Resource Types](#resource-types)
        - [Create Resource Types](#create-resource-types)
        - [Create Resource Type](#create-resource-type)
        - [Create Controller Supporting Resource Types](#create-controller-supporting-resource-types)
        - [Configure Resource Types](#configure-resource-types)
    - [Security](#security)
    - [Testing](#testing)
        - [Crud Controller Testing](#crud-controller-testing)
            - [Seeding](#seeding)
            - [Uri Generation](#uri-generation)
            - [Asserts](#asserts)
                - [Index Action Asserts](#index-action-asserts)
                - [Form Asserts](#form-asserts)
            - [Example Tests](#example-tests)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app crud project running this command.

```
composer require tobento/app-crud
```

## Requirements

- PHP 8.4 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Crud Boot

The crud boot does the following:

* Migrates views and assets for default layout
* Implements needed interfaces

```php
use Tobento\App\AppFactory;

$app = new AppFactory()->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'vendor', 'vendor')
    ->dir($app->dir('app').'views', 'views', group: 'views')
    ->dir($app->dir('app').'trans', 'trans', group: 'trans')
    ->dir($app->dir('root').'build/public', 'public');

// Adding boots:

// you might boot error handlers:
$app->boot(\Tobento\App\Boot\ErrorHandling::class);

$app->boot(\Tobento\App\Crud\Boot\Crud::class);

// you might boot:
$app->boot(\Tobento\App\View\Boot\Breadcrumb::class);
$app->boot(\Tobento\App\View\Boot\Messages::class);

// Run the app:
$app->run();
```

## Crud Controller

With the CRUD controller you can create pages such as index, create, update, delete or custom pages for resources implementing the [Repository Interface](https://github.com/tobento-ch/service-repository#repository-interface).

### Create Controller

To create a crud controller simply extend the ```AbstractCrudController::class``` and specify a resource name with the ```RESOURCE_NAME``` constant.

Next, declare your repository class on the constructor method. You may use the [Storage Repository](https://github.com/tobento-ch/service-repository-storage) to easily create a repository.

Finally, configure the fields, actions and filters.

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;
use Tobento\Service\Repository\RepositoryInterface;

class ProductsController extends AbstractCrudController
{
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'products';
    
    /**
     * Create a new ProductController.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(
        ProductRepository $repository
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            new Field\PrimaryId('id'),
            new Field\Text('sku'),
            //...
        ];
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        return [
            new Action\Index(title: 'Products'),
            //...
        ];
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [
            new Filter\Columns()->open(false),
            //...
        ];
    }
}
```

#### Entity Mapping

You may overwrite the ```createEntityFromObject``` method to map your repository entity object to the CRUD entity.

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\Service\Support\Arrayable;

class ProductsController extends AbstractCrudController
{
    /**
     * Create entity from object.
     *
     * @param object $object
     * @return EntityInterface
     */
    public function createEntityFromObject(object $object): EntityInterface
    {
        // Default mapping:
        if ($object instanceof Arrayable) {
            return new Entity(
                attributes: $object->toArray(),
                idAttributeName: $this->entityIdName(),
            );
        }
        
        if (
            method_exists($object, 'toArray')
            && is_array($array = $object->toArray())
        ) {
            return new Entity(
                attributes: $array,
                idAttributeName: $this->entityIdName(),
            );
        }
        
        return new Entity(
            attributes: (array)$object,
            idAttributeName: $this->entityIdName(),
        );
    }
}
```

**Entity Id Name**

You may change the entity id name used as the id name of the entity.

```php
use Tobento\App\Crud\AbstractCrudController;

class ProductsController extends AbstractCrudController
{
    /**
     * Returns the entity id name.
     *
     * @return string
     */
    protected function entityIdName(): string
    {
        return 'id';
    }
}
```

#### Entity Actions

You may overwrite the following methods to customize the read and write repository actions.

**findEntities**

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Filter\FiltersInterface;

class ProductsController extends AbstractCrudController
{
    /**
     * Find entities.
     *
     * @param FiltersInterface $filters
     * @return iterable The found entities.
     */
    public function findEntities(FiltersInterface $filters): iterable
    {
        return $this->repository()->findAll(
            where: $filters->getWhereParameters(),
            orderBy: $filters->getOrderByParameters(),
            limit: $filters->getLimitParameter(),
        );
    }
}
```

**storeEntity**

```php
use Tobento\App\Crud\AbstractCrudController;

class ProductsController extends AbstractCrudController
{
    /**
     * Store entity.
     *
     * @param array $attributes
     * @return object The created entity
     */
    public function storeEntity(array $attributes): object
    {
        return $this->repository()->create(
            attributes: $attributes,
        );
    }
}
```

**updateEntity**

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Entity\EntityInterface;

class ProductsController extends AbstractCrudController
{
    /**
     * Update entity.
     *
     * @param int|string $id
     * @param array $attributes
     * @param EntityInterface $entity
     * @return object The updated entity
     */
    public function updateEntity(int|string $id, array $attributes, EntityInterface $entity): object
    {
        return $this->repository()->updateById(
            id: $id,
            attributes: $attributes,
        );
    }
}
```

**deleteEntity**

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Entity\EntityInterface;

class ProductsController extends AbstractCrudController
{
    /**
     * Delete entity.
     *
     * @param int|string $id
     * @param EntityInterface $entity
     * @return void
     */
    public function deleteEntity(int|string $id, EntityInterface $entity): void
    {
        $this->repository()->deleteById(id: $id);
    }
}
```

### Configure Fields

Use the ```configureFields``` method to configure any fields using the [Build in Fields](#build-in-fields) or creating your custom fields.

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;

class ProductsController extends AbstractCrudController
{
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            new Field\PrimaryId('id'),
            new Field\Text(name: 'sku'),
            //...
        ];
    }
}
```

### Configure Actions

Use the ```configureActions``` method to configure any actions using the [Build in Actions](#build-in-actions) or creating your [Custom Actions](#custom-action).

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;

class ProductsController extends AbstractCrudController
{
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        return [
            new Action\Index(title: 'Products'),
            new Action\Create(title: 'New product'),
            new Action\Store(),
            new Action\Edit(title: 'Edit product'),
            new Action\Update(),
            new Action\Copy(title: 'Copy product'),
            new Action\Show(),
            new Action\Delete(),
            new Action\BulkDelete(),
            new Action\BulkEdit(),
        ];
    }
}
```

### Configure Filters

Use the ```configureFilters``` method to configure any filters using the [Build in Filters](#build-in-filters) or creating your custom filters.

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;

class ProductsController extends AbstractCrudController
{
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [
            new Filter\Columns()->open(false),
            new Filter\FieldsSortOrder(),
            ...new Filter\Fields()
               ->group('field')
               ->fields($action->fields())
               ->toFilters(),
            new Filter\PaginationItemsPerPage()->open(false),
            
            // must be added last!
            new Filter\Pagination(),
        ];
    }
}
```

#### Different Filters Per Action

**Option 1**

Using the action name:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    yield new Filter\Columns()->open(false);

    if (in_array($action->name(), ['custom'])) {
        yield new Filter\FieldsSortOrder();
    }
}
```

**Option 2**

Using the action ```setFilters``` method:

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Filter;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->setFilters(new Filter\Filters(
                new Filter\Columns()->open(false),
            )),
    ];
}
```

### Route Controller

After creating the [crud controller](#create-controller) you need to add the routes for the [configured actions](#configure-actions):

**Routing via Crud Boot**

```php
use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;

class RoutesBoot extends Boot
{
    public const BOOT = [
        // you may ensure the crud boot:
        Crud::class,
    ];
    
    public function boot(Crud $crud)
    {
        $crud->routeController(App\ProductsController::class);
        
        // or:
        $crud->routeController(
            controller: App\ProductsController::class,
            // you may set specific actions only:
            only: ['index', 'show'],
            // or you may exclude specific actions:
            except: ['bulk'],
            // you may set middlewares for all routes:
            middleware: [
                SomeMiddleware::class,
            ],
            // you may localize the routes:
            localized: true,
        );
    }
}
```

**Manually Routing**

You may define the routes manually, if you need even more control:

```php
use Tobento\Service\Routing\RouterInterface;

// After adding boots
$app->booting();

$router = $this->app->get(RouterInterface::class);

$name = App\ProductsController::RESOURCE_NAME;

$router->resource($name, App\ProductsController::class);

// needed if you have configured bulk actions:
$router->post($name.'/bulk/{name}', [App\ProductsController::class, 'bulk'])
    ->name($name.'.bulk');
    
// Run the app:
$app->run();
```

You may check out the [App Http - Routing](https://github.com/tobento-ch/app-http#routing-boot) for more information on routing.

You may check out the [Routing Service](https://github.com/tobento-ch/service-routing#resource-routing) for more information on routing resources.

### Route Permissions

You may install the [App User](https://github.com/tobento-ch/app-user) and use the [Verify Route Permission Middleware](https://github.com/tobento-ch/app-user#verify-route-permission-middleware) to protect your crud routes from users without the defined permissions.

```php
use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;

class RoutesBoot extends Boot
{
    public function boot(Crud $crud)
    {
        $crud->routeController(
            controller: App\ProductsController::class,
            middleware: [
                [
                    \Tobento\App\User\Middleware\VerifyRoutePermission::class,
                    'permissions' => [
                        'products.index' => 'products',
                        'products.show' => 'products',
                        'products.create' => 'products.create',
                        'products.store' => 'products.create',
                        'products.copy' => 'products.create',
                        'products.edit' => 'products.edit',
                        'products.update' => 'products.edit',
                        'products.delete' => 'products.delete',
                        'products.bulk' => 'products.edit|products.delete',
                    ],
                ]
            ],
        );
    }
}
```

## Crud Write Repository

You may create a crud write-only repository for importing data or testing puposes for instance.

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\CrudWriteRepository;
use Tobento\Service\Repository\WriteRepositoryInterface;

$repository = new CrudWriteRepository(
    controller: $controller, // AbstractCrudController
    actionProcessor: $actionProcessor, // ActionProcessorInterface
);

var_dump($repository instanceof WriteRepositoryInterface);
// bool(true)
```

Check out the [Write Repository Interface](https://github.com/tobento-ch/service-repository#write-repository-interface) for more info.

Only the ```create```, ```updateById``` and ```deleteById``` methods are supported.

**Example**

```php
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\ValidationException;
use Tobento\Service\Repository\RepositoryCreateException;

try {
    $entity = $repository->create([
        'title' => 'Lorem ipsum',
    ]);
    
    var_dump($entity instanceof EntityInterface);
    // bool(true)
} catch (RepositoryCreateException $e) {
    // do something ...
    if ($e->getPrevious() instanceof ValidationException) {
        $errorsArray = $e->getPrevious()->validation()->errors()->toArray();
    }
}
```

**Additional Methods**

All methods will return a new instance.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;

$repository = $repository->onlyFields('id', 'title');

$repository = $repository->exceptFields('sku');

$repository = $repository->withFields(function(FieldsInterface $fields, ActionInterface $action): FieldsInterface {
    return $fields;
});

$repository = $repository->onlyActions('store', 'update');
```

## Fields

### Build in Fields

#### Checkboxes Field

The checkboxes field displays a list of checkboxes using the specified options.

```php
use Tobento\App\Crud\Field;

new Field\Checkboxes(
    name: 'colors',
    // you may set a label, otherwise name is used:
    label: 'Colors',
);
```

**Options**

Use the ```options``` method to define the options to choose:

```php
new Field\Checkboxes('colors')
    // specify the options using an array:
    ->options(['blue' => 'Blue', 'red' => 'Red'])
    
    // or using a closure (parameters are resolved by autowiring):
    ->options(fn(ColorsRepository $repo): array => $repo->findColors());
```

**Empty Option**

Use the ```emptyOption``` method to change the empty option value needed when no option is selected:

```php
new Field\Checkboxes('colors')
    ->emptyOption(value: '_none');
```

**Selected Options**

You may use the ```selected``` method to define the selected value(s):

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;

new Field\Checkboxes('colors')
    ->selected(value: ['blue', 'red'], action: 'create')
    
    // or using a closure (additional parameters are resolved by autowiring):
    ->selected(
        value: function (ActionInterface $action, FieldInterface $field): null|array {
            return ['blue', 'red'];
            //return null; // if none is selected
        },
        action: 'edit',
    )
```

**Attributes**

You may set HTML attributes assigned to each input element using the ```attributes``` method:

```php
use Tobento\App\Crud\Field;

new Field\Checkboxes('colors')->attributes(['class' => 'name']);
```

**Validation**

Data are being validated using the defined options. You may define additional rules though:

```php
new Field\Checkboxes('colors')
    ->validate('required|minItems:2|maxItems:10');
```

You may check out the [Validate Field](#validate-field) section for more detail.

#### File Field

The file field enables you to upload a single file using the [FileSource Field](#filesource-field). In addition, you can define any fields relating to the file source such an alternative text for images. The data will be stored in JSON format.

```php
use Tobento\App\Crud\Field;

new Field\File(
    name: 'file',
    // you may set a label, otherwise name is used:
    label: 'File',
);
```

**Fields**

```php
use Tobento\App\Crud\Field;

new Field\File(name: 'file')
    ->fields(
        new Field\Text('alt', 'Alternative Text')->translatable(),
        new Field\Radios('buyable', 'Buyable')->displayInline()->options(['no', 'yes']),
    );
```

**Translatable**

Use the ```translatable``` method if you want to have translatable files.

```php
use Tobento\App\Crud\Field;

new Field\File(name: 'file')
    ->translatable();
```

**FileSource Field**

Use the ```fileSource``` method to customize the [FileSource Field](#filesource-field).

```php
use Tobento\App\Crud\Field;

new Field\File(name: 'file')
    ->fileSource(function(Field\FileSource $fs): void {
        $fs->storage('uploads')
           ->allowedExtensions('jpg', 'png')
           ->imageEditor(template: 'default');
    });
```

**Storing filenames**

You may use the ```storeFilenameTo``` method to store the filenames to a certain file field.

```php
use Tobento\App\Crud\Field;

new Field\File(name: 'file')
    ->fields(
        new Field\Text('name', 'Filename')->translatable(),
    )
    ->storeFilenameTo(field: 'name');
    
    // with using a filename modifier (callable):
    ->storeFilenameTo(field: 'name', modify: static function(string $filename, null|string $locale): string {
        return $filename;
    });
```

#### Files Field

The files field enables you to upload multiple files using the [FileSource Field](#filesource-field). In addition, you can define any fields relating to the file source such an alternative text for images. The data will be stored in JSON format.

```php
use Tobento\App\Crud\Field;

new Field\Files(
    name: 'files',
    // you may set a label, otherwise name is used:
    label: 'Files',
);
```

**Fields**

```php
use Tobento\App\Crud\Field;

new Field\Files(name: 'files')
    ->fields(
        new Field\Text('alt', 'Alternative Text')->translatable(),
        new Field\Radios('buyable', 'Buyable')->displayInline()->options(['no', 'yes']),
    );
```

**File Field**

Use the ```file``` method to customize the [File Field](#file-field).

```php
use Tobento\App\Crud\Field;

new Field\Files(name: 'files')
    ->file(function(Field\File $file): void {
        $file->translatable();
        $file->fileSource(function(Field\FileSource $fs): void {
            $fs->allowedExtensions('png');
        });
    });
```

If you want translatable files, makes sure to set it on both the files and file field.

```php
use Tobento\App\Crud\Field;

new Field\Files(name: 'files')
    ->translatable()
    ->file(function(Field\File $file): void {
        $file->translatable();
        $file->fileSource(function(Field\FileSource $fs): void {
            $fs->allowedExtensions('png');
        });
    });
```

**Number Of Files**

Use the ```numberOfFiles``` method to set the ```min``` and/or ```max``` allowed files.

```php
use Tobento\App\Crud\Field;

new Field\Files(name: 'files')
    // min only;
    ->numberOfFiles(min: 1)
    // or max only:
    ->numberOfFiles(max: 10)
    // or min and max:
    ->numberOfFiles(min: 1, max: 10);
```

#### FileSource Field

The file source field enables you to upload a single file, storing the file path such as ```path/to/file.txt```. If you want to store other data such as the storage name or an alternative text for images, consider using the [File Field](#file-field).

```php
use Tobento\App\Crud\Field;

new Field\FileSource(
    name: 'file',
    // you may set a label, otherwise name is used:
    label: 'File',
);
```

**File Storage**

Use the ```storage``` method to change the storage name where to store the file. By default the ```uploads``` storage is used. Make sure your defined storage is outside the webroot such as the default configured uploads storage.

```php
new Field\FileSource('image')
    ->storage(name: 'custom-uploads');
```

Make sure the storage is configured in the ```app/config/file_storage.php``` file.

Check out the [App File Storage](https://github.com/tobento-ch/app-file-storage) to learn more about file storages in general.

**Folder**

Use the ```folder``` method to define a folder path.

```php
new Field\FileSource('image')
    ->folder(path: 'shop/products')
    
    // or using a callable:
    ->folder(static function(): string {
        return sprintf('product/%s/%s/', date('Y'), date('m'));
    });
```

**Validation**

Use the ```allowedExtensions``` method to define the allowed file extensions. By default, only ```jpg```, ```png```, ```gif``` and ```webp``` are allowed.

```php
new Field\FileSource('image')
    ->allowedExtensions('jpg', 'png')
    
    // you may set max file size in KB:
    ->maxFileSizeInKb(1000); // or null unlimited (default)
```

If you need more control validating files, use the ```validator``` method:

```php
use Tobento\App\Media\Upload\ValidatorInterface;
use Tobento\App\Media\Upload\Validator;

new Field\FileSource('image')
    ->validator(static function(): ValidatorInterface {
        return new Validator(
            allowedExtensions: ['jpg'],
            strictFilenameCharacters: true,
            maxFilenameLength: 200,
            maxFileSizeInKb: 2000,
        );
        
        // or create your custom validator implementing the ValidatorInterface!
    });
```

Check out the [Media Upload Validator](https://github.com/tobento-ch/app-media#upload-validator) section to learn more about the validator.

**File Writer**

If you need more control about writing files to the storage, use the ```fileWriter``` method:

```php
use Tobento\App\Media\FileStorage\FileWriter;
use Tobento\App\Media\FileStorage\FileWriterInterface;
use Tobento\App\Media\FileStorage\Writer;
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\Service\FileStorage\StorageInterface;

new Field\FileSource('image')
    ->fileWriter(static function(StorageInterface $storage): FileWriterInterface {
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
    });
```

Check out the [Media File Writer](https://github.com/tobento-ch/app-media#file-writer) section to learn more about the file writer.

**Modify Input Value**

Use the ```modifyInputValue``` method if you want support other files to be uploaded for instance.

```php
use Tobento\App\Media\Upload\UploadedFileFactoryInterface;

new Field\FileSource('image')
    ->modifyInputValue(
        modifier: function(mixed $value, Field\FileSource $field, UploadedFileFactoryInterface $uploadedFileFactory): mixed {
            if (is_string($value)) {
                return $uploadedFileFactory->createFromRemoteUrl($value);
            }

            return $value;
        },
        action: 'store|update', // default
    );
```

**Images**

By default, images get displayed on the index and edit page using the [Media Picture Feature](https://github.com/tobento-ch/app-media#picture-feature). You may change its default definition or setting it to null to disable it.

```php
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\Definition\ArrayDefinition;

new Field\FileSource('image')
    ->picture(definition: [
        'img' => [
            'src' => [120],
            'loading' => 'lazy',
        ],
        'sources' => [
            [
                'srcset' => [
                    '' => [120],
                ],
                'type' => 'image/webp',
            ],
        ],
    ])
    
    // or you may use a definition object implementing the DefinitionInterface:
    ->picture(definition: new ArrayDefinition('product-image', [
        'img' => [
            'src' => [120],
            'loading' => 'lazy',
        ],
        'sources' => [
            [
                'srcset' => [
                    '' => [120],
                ],
                'type' => 'image/webp',
            ],
        ],
    ]))
    
    // or you may disable it, showing just the path:
    ->picture(definition: null)
    
    // You may queue the picture generation:
    ->pictureQueue(true);
```

**Image Editor**

You may define an image editor template using the ```imageEditor``` method. Once defined, images can be edited using the [Image Editor Feature](https://github.com/tobento-ch/app-media#image-editor-feature) by clicking the edit button on the file. Make sure the feature is installed and the template is defined.

```php
new Field\FileSource('image')
    ->imageEditor(template: 'crud');
```

You will need to define an event listener in the ```app/config/event.php``` file to deleted generated images once an image is edited:

```php
'listeners' => [
    \Tobento\App\Media\Event\ImageEdited::class => [
        [\Tobento\App\Crud\Listener\ClearGeneratedPicture::class, ['definition' => 'crud-file-src']],
        
        // you add more when using different definitions:
        [\Tobento\App\Crud\Listener\ClearGeneratedPicture::class, ['definition' => 'product-image']],
    ],
],
```

**Picture Editor**

You may define a picture using the ```pictureEditor``` method. Once defined, images can be edited using the [Picture Editor Feature](https://github.com/tobento-ch/app-media#picture-editor-feature) by clicking the edit picture button on the file. Make sure the feature is installed and the template is defined.

```php
new Field\FileSource('image')
    ->pictureEditor(template: 'default', definitions: ['product-main', 'product-list']);
```

**Display Messages**

Use the ```displayMessages``` method to define the messages to be displayed.

```php
new Field\FileSource('image')
    ->displayMessages('error', 'success', 'info', 'notice');
```

**Events**

```php
use Tobento\App\Crud\Event;
```

| Event | Description |
| --- | --- |
| ```Event\FileSourceDeleted::class``` | The event will dispatch **after** a file source has been deleted. |


**Delete Generated Pictures**

If you generate pictures from the file source using the [Media Picture Feature](https://github.com/tobento-ch/app-media#picture-feature), You may want to define an event listener in the ```app/config/event.php``` file to deleted generated images once a file source is deleted:

```php
'listeners' => [
    \Tobento\App\Crud\Event\FileSourceDeleted::class => [
        \Tobento\App\Crud\Listener\DeletesGeneratedPictures::class,
    ],
],
```

#### Group Field

The group field may be used if you want to group fields.

```php
use Tobento\App\Crud\Field;

new Field\Group(name: 'seo')
    // define the fields:
    ->fields(
        new Field\Text('meta_title', 'Meta Title')->translatable(),
        new Field\Text('meta_desc', 'Meta Description')->translatable(),
    )
    // you may group the fields, otherwise groups from the defined fields are used:
    ->group('SEO')
    
    // you may prepend the group name to each field:
    ->prependGroupName()

    // you may display the fields as field layout:
    ->displayAsField()
    
    // you may display the fields as card layout:
    ->displayAsCard()
    
    // you may display the group label when displaying as card:
    ->displayLabel();
```

**Custom Groups**

The main purpose for the group field is that you can create a custom group for repeated usage:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;

class SeoFields extends Field\Group
{
    protected function configure(): void
    {
        $this->group('Seo');
        $this->fields();
    }

    public function fields(FieldInterface ...$fields): static
    {
        $this->fields = [
            new Field\Text('meta_title', 'Meta Title')->translatable(),
            new Field\Text('meta_desc', 'Meta Description')->translatable(),
        ];
        
        return $this;
    }
}
```

Usage of custom group:

```php
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;

new SeoFields(name: 'seo')
    // you may rename fields:
    ->renameFields(['meta_desc' => 'meta_description'])
    
    // you may remove fields:
    ->removeField('meta_title')
    
    // you may modify fields:
    ->modifyField(name: 'meta_title', modifier: function(FieldInterface $field): void {
        $field->translatable(false);
    })
    
    // you may modify fields:
    ->modifyFields(modifier: function(FieldsInterface $fields): FieldsInterface {
        // modify ...
        return $fields;
    });
```

#### Html Field

The html field may be used if you want to set HTML content.

```php
use Tobento\App\Crud\Field;

new Field\Html(
    name: 'title',
    // you may set a label, otherwise name is used:
    label: 'TITLE',
);
```

**Content**

Use the ```content``` method to set the HTML. Make sure any html you set is being properly escaped if needed:

```php
use Tobento\App\Crud\Field;

new Field\Html(name: 'title')->content(html: '<p>Lorem</p>');
```

In addition, you may pass a callable being resolved by autowiring:

```php
use Tobento\App\Crud\Field;
use Tobento\Service\View\ViewInterface;

new Field\Html(name: 'title')->content(function (Field\Html $field, ViewInterface $view): string {
    return $view->render('about', []);
});
```

**Defaults**

```php
use Tobento\App\Crud\Field;

new Field\Html(name: 'title')
    ->content(html: '<p>Lorem</p>')
    ->indexable(true) // default false
    ->showable(true); // default false
```

#### Items Field

The items field displays a collection of items allowing you to add, edit and delete items.

```php
use Tobento\App\Crud\Field;

new Field\Items('prices')
    // you may group the fields
    ->group('Prices') // set before defining fields!
    
    // define the fields per item:
    ->fields(
        new Field\Text('price_net', 'Price Net')
            ->type('number')
            ->attributes(['step' => 'any'])
            ->validate('decimal'),
        new Field\Text('price_gross', 'Price Gross')
            ->type('number')
            ->attributes(['step' => 'any'])
            ->validate('decimal'),
    )
    
    // you may display items as card layout:
    ->displayAsCard()
    
    // you may restrict items:
    ->validate('required|minItems:1|maxItems:30')
    
    // you may define the items number to be displayed on default:
    ->defaultItems(num: 1)
    
    // you may define a custom add text:
    ->addText('Add new price')
    
    // you may not display the label text on create and edit action:
    ->withoutLabel();
```

#### Options Field

The options field displays searchable options to choose from using the defined repository. If you have only a few options, you may consider using the [Checkboxes Field](#checkboxes-field) instead. If you want to select only one option, you may consider using the [Single Options Field](#single-options-field) instead.

```php
use Tobento\App\Crud\Field;

new Field\Options(
    name: 'categories',
    // you may set a label, otherwise name is used:
    label: 'Categories',
);
```

**Repository** (required)

Use the ```repository``` method to define the repository implementing the [Repository Interface](https://github.com/tobento-ch/service-repository#repository-interface):

```php
use Tobento\Service\Repository\RepositoryInterface;

new Field\Options('categories')
    ->repository(CategoriesRepository::class) // class-string|RepositoryInterface
    
    // you may add base where queries:
    ->baseWhere(['type' => 'blog'])
    
    // you may change the limit of the searchable options to be displayed:
    ->limit(15) // default is 25

    // you may change the column value to be stored:
    ->storeColumn('sku') // 'id' is default
    
    // you may change the search columns:
    ->searchColumns('title', 'sku'); // 'title' is default
```

**toOption** (required)

Use the ```toOption``` method to create options from the repository items:

```php
use Tobento\App\Crud\Field;
use Tobento\Service\View\ViewInterface;

new Field\Options('categories')
    ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {        
        return new Field\Option(
            value: (string)$item->get('id'),
            text: (string)$item->get('title'),
        );
    });
```

Or using option methods:

```php
use Tobento\App\Crud\Field;
use Tobento\Service\View\ViewInterface;

new Field\Options('categories')
    ->toOption(function(object $item, ViewInterface $view, Field\Options $options): Field\Option {
        return new Field\Option(value: (string)$item->get('id'))
            ->text((string)$item->get('title'))
            ->text((string)$item->get('sku'))
            ->html('html') // must be escaped!
            ->image(
                image: $item->get('image', []),
                view: $view,
            );
    });
```

**Selected Options**

You may use the ```selected``` method to define the selected value(s):

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;

new Field\Options('categories')
    ->selected(value: ['2', '5'], action: 'create')
    
    // or using a closure (additional parameters are resolved by autowiring):
    ->selected(
        value: function (ActionInterface $action, FieldInterface $field): null|array {
            return ['2', '5'];
            //return null; // if none is selected
        },
        action: 'edit',
    )
```

**Placeholder**

Use the ```placeholder``` method to define a placeholder text for the serach input element:

```php
new Field\Options('categories')
    ->placeholder(text: 'Search categories');
```

**Empty Option**

Use the ```emptyOption``` method to change the empty option value needed when no option is selected:

```php
new Field\Options('categories')
    ->emptyOption(value: '_none');
```

**Validation**

Data are being validated using the repository to query the options. You may define additional rules though:

```php
new Field\Options('categories')
    ->validate('required|minItems:2|maxItems:10');
```

You may check out the [Validate Field](#validate-field) section for more detail.

#### PrimaryId Field

The primary id field will not be displayed on the ```create```, ```edit``` and ```show``` view. In additon, it cannot be edited at all.

```php
use Tobento\App\Crud\Field;

new Field\PrimaryId(name: 'id', label: 'ID');
```

#### Radios Field

The radios field displays a list of radios using the specified options.

```php
use Tobento\App\Crud\Field;

new Field\Radios(
    name: 'colors',
    // you may set a label, otherwise name is used:
    label: 'Colors',
);
```

**Options**

Use the ```options``` method to define the options to choose:

```php
new Field\Radios('colors')
    // specify the options using an array:
    ->options(['blue' => 'Blue', 'red' => 'Red'])
    
    // or using a closure (parameters are resolved by autowiring):
    ->options(fn(ColorsRepository $repo): array => $repo->findColors());
```

**Selected Option**

You may use the ```selected``` method to define the selected value:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;

new Field\Radios('colors')
    ->selected(value: 'blue', action: 'create')
    
    // or using a closure (additional parameters are resolved by autowiring):
    ->selected(
        value: function (ActionInterface $action, FieldInterface $field): null|string {
            return 'blue';
            //return null; // if none is selected
        },
        action: 'edit',
    )
```

**Attributes**

You may set HTML attributes assigned to each radio element using the ```attributes``` method:

```php
use Tobento\App\Crud\Field;

new Field\Radios('colors')->attributes(['class' => 'name']);
```

**Display Inline**

Use the ```displayInline``` method for the radio options to be displayed inline:

```php
new Field\Radios('colors')
    ->displayInline();
```

**Validation**

Data are being validated using the defined options. You may define additional rules though:

```php
new Field\Radios('colors')
    ->validate('required');
```

You may check out the [Validate Field](#validate-field) section for more detail.

#### Select Field

The select field will be rendered as a HTML ```select``` element.

```php
use Tobento\App\Crud\Field;

new Field\Select(
    name: 'colors',
    // you may set a label, otherwise name is used:
    label: 'Colors',
);
```

**Options**

Use the ```options``` method to define the options to select:

```php
new Field\Select('colors')
    // specify the options using an array:
    ->options(['blue' => 'Blue', 'red' => 'Red'])
    
    // or using a closure (parameters are resolved by autowiring):
    ->options(fn(ColorsRepository $repo): array => $repo->findColors());
```

You may define options as groups:

```php
new Field\Select('roles')
    ->options([
        'Frontend' => [
            'guest' => 'Guest',
            'registered' => 'Registered',
        ],
        'Backend' => [
            'editor' => 'Editor',
            'administrator' => 'Aministrator',
        ],
    ]);
```

**Empty Option**

Use the ```emptyOption``` method to define an empty option which will not be saved when selected:

```php
new Field\Select('colors')
    // specify the options using an array:
    ->options(['blue' => 'Blue', 'red' => 'Red'])
    ->emptyOption(value: 'none', label: '---');
```

**Selected Options**

You may use the ```selected``` method to define the selected value(s):

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;

new Field\Select('colors')
    ->selected(value: 'value', action: 'create')
    
    // or using a closure (additional parameters are resolved by autowiring):
    ->selected(
        value: function (ActionInterface $action, FieldInterface $field): null|string|array {
            return ['blue', 'red']; // if multiple selection
            //return 'blue'; // if single selection
            //return null; // if none is selected
        },
        action: 'edit',
    )
```

**Attributes**

You may set additional HTML select attributes using the ```attributes``` method:

```php
use Tobento\App\Crud\Field;

new Field\Select('colors')->attributes(['multiple', 'size' => '10']);

new Field\Select('colors')->optionAttributes([
    // all options using wildcard:
    '*' => ['data-foo' => 'value'],
    // specific option using option value:
    'blue' => ['data-bar' => 'value'],
]);

new Field\Select('colors')->optgroupAttributes(['data-foo' => 'value']);
```

**Validation**

Data are being validated using the defined options. You may define additional rules though:

```php
new Field\Select('colors')
    ->validate('required')

// rules example if allowing multiple selection:
new Field\Select('colors')
    ->attributes(['multiple', 'size' => '10'])
    ->validate('required|minItems:2|maxItems:10')
```

You may check out the [Validate Field](#validate-field) section for more detail.

#### Single Options Field

The single options field displays searchable options to choose a single option from using the defined repository. If you have only a few options, you may consider using the [Select Field](#select-field) instead. If you want to select mutliple options consider using the [Options Field](#options-field) instead.

```php
use Tobento\App\Crud\Field;

new Field\SingleOptions(
    name: 'category',
    // you may set a label, otherwise name is used:
    label: 'Category',
);
```

**Repository** (required)

Use the ```repository``` method to define the repository implementing the [Repository Interface](https://github.com/tobento-ch/service-repository#repository-interface):

```php
use Tobento\Service\Repository\RepositoryInterface;

new Field\SingleOptions('category')
    ->repository(CategoriesRepository::class) // class-string|RepositoryInterface
    
    // you may add base where queries:
    ->baseWhere(['type' => 'blog'])
    
    // you may change the limit of the searchable options to be displayed:
    ->limit(15) // default is 25

    // you may change the column value to be stored:
    ->storeColumn('sku') // 'id' is default
    
    // you may change the search columns:
    ->searchColumns('title', 'sku'); // 'title' is default
```

**toOption** (required)

Use the ```toOption``` method to create options from the repository items:

```php
use Tobento\App\Crud\Field;
use Tobento\Service\View\ViewInterface;

new Field\SingleOptions('category')
    ->toOption(function(object $item, ViewInterface $view, Field\SingleOptions $options): Field\Option {        
        return new Field\Option(
            value: (string)$item->get('id'),
            text: (string)$item->get('title'),
        );
    });
```

Or using option methods:

```php
use Tobento\App\Crud\Field;
use Tobento\Service\View\ViewInterface;

new Field\SingleOptions('category')
    ->toOption(function(object $item, ViewInterface $view, Field\SingleOptions $options): Field\Option {
        return new Field\Option(value: (string)$item->get('id'))
            ->text((string)$item->get('title'))
            ->text((string)$item->get('sku'))
            ->html('html') // must be escaped!
            ->image(
                image: $item->get('image', []),
                view: $view,
            );
    });
```

**Selected Options**

You may use the ```selected``` method to define the selected value:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;

new Field\SingleOptions('category')
    ->selected(value: '2', action: 'create')
    
    // or using a closure (additional parameters are resolved by autowiring):
    ->selected(
        value: function (ActionInterface $action, FieldInterface $field): null|string {
            return '2';
            //return null; // if none is selected
        },
        action: 'edit',
    )
```

**Placeholder**

Use the ```placeholder``` method to define a placeholder text for the serach input element:

```php
new Field\SingleOptions('category')
    ->placeholder(text: 'Search categories');
```

**Display As Modal**

Use the ```displayAsModal``` method if you want to display a modal with the searchable options, otherwise a dropdown is displayed:

```php
new Field\SingleOptions('categories')
    ->displayAsModal();
```

**Validation**

Data are being validated using the repository to query the options. You may define additional rules though:

```php
new Field\SingleOptions('category')
    ->validate('required');
```

You may check out the [Validate Field](#validate-field) section for more detail.

#### Slug Field

The slug field generates slugs based on the provided input. For example, the input of ```Lorem Ipsum Dolor``` is usually something like ```lorem-ipsum-dolor```.

```php
use Tobento\App\Crud\Field;

new Field\Slug(
    name: 'slug',
    // you may set a label, otherwise name is used:
    label: 'SLUG',
);
```

**fromField**

You may define a field to generate the slug from when no input from the slug field is provided.

```php
use Tobento\App\Crud\Field;

new Field\Slug('slug')->fromField('title');
```

**Slugifier**

You may use the slugifier method to define a custom slugifier. By default, the slugifier named ```crud``` is used but as the slugifier not exists, the ```default``` from the ```app/config/slugging.php``` will be used.

```php
use Tobento\App\Crud\Field;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\SlugifiersInterface;

// using slugifier name:
new Field\Slug('slug')->slugifier('crud'); 
// 'crud' is set as default but fallsback to default as not defined in slugging config

// using object:
new Field\Slug('slug')->slugifier(new Slugifier());

// using closure:
new Field\Slug('slug')
    ->slugifier(function (SlugifiersInterface $slugifiers): SlugifierInterface {
        return $slugifiers->get('custom');
    });
```

You can define a custom slugifier in the ```app/config/slugging.php``` file.

You may check out the [App Slugging](https://github.com/tobento-ch/app-slugging) bundle to learn more about it in general.

**Unique Slugs**

By default, generated slugs will be saved in the [Slug Repository](https://github.com/tobento-ch/app-slugging#slug-repository) or deleted from when changed. The slug repository is added to the [Slugs](https://github.com/tobento-ch/app-slugging#adding-slugs) by default in the ```app/config/slugging.php``` file which enables you to use [Slug Matches](https://github.com/tobento-ch/app-slugging#slug-matches) on routes.

In addition, the default slugifier used has the [Prevent Dublicate Modifier](https://github.com/tobento-ch/service-slugifier#prevent-dublicate-modifier) applied so that slugs will be generated uniquely.

You may disable unique slugs by using the ```uniqueSlugs``` method if you want to implement a custom strategy:

```php
use Tobento\App\Crud\Field;

new Field\Slug('slug')->uniqueSlugs(false);
```

**Attributes**

You may set additional HTML input attributes using the ```attributes``` method:

```php
use Tobento\App\Crud\Field;

new Field\Slug(name: 'title')->attributes(['data-foo' => 'value']);
```

**Example With Readonly**

```php
use Tobento\App\Crud\Field;

new Field\Slug('slug')
    ->fromField('title')
    ->translatable()
    ->readonly(true, action: 'edit|update');
```

#### Text Field

The text field will be rendered as an ```input``` element of the type ```text``` as default. Use this field for any other input type as well.

```php
use Tobento\App\Crud\Field;

new Field\Text(
    name: 'title',
    // you may set a label, otherwise name is used:
    label: 'TITLE',
);
```

**Type**

You may set another HTML input type as the default ```text``` type using the ```type``` method:

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'email')->type('email');
```

**Value**

You may set a value using the ```value``` method.

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'title')->value('Lorem');

// you may pass an array of values if your field is translatable:
new Field\Text(name: 'title')
    ->translatable()
    ->value(['en' => 'Lorem', 'de' => 'Lorem ipsum']);
```

**Default Value**

You may set a default value using the ```defaultValue``` method:

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'title')->defaultValue('Lorem');

// you may pass an array of values if your field is translatable:
new Field\Text(name: 'title')
    ->translatable()
    ->defaultValue(['en' => 'Lorem', 'de' => 'Lorem ipsum']);
```

**Attributes**

You may set additional HTML input attributes using the ```attributes``` method:

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'title')->attributes(['data-foo' => 'value']);
```

#### Textarea Field

The textarea field will be rendered as an ```textarea``` element.

```php
use Tobento\App\Crud\Field;

new Field\Textarea(
    name: 'title',
    // you may set a label, otherwise name is used:
    label: 'TITLE',
);
```

**Attributes**

You may set additional HTML textarea attributes using the ```attributes``` method:

```php
use Tobento\App\Crud\Field;

new Field\Textarea(name: 'title')->attributes(['rows' => '5']);
```

#### TextEditor Field

This field creates a JavaScript-based WYSIWYG editor using the [JS Editor](https://github.com/tobento-ch/js-editor).

```php
use Tobento\App\Crud\Field;

new Field\TextEditor(
    name: 'desc',
    // you may set a label, otherwise name is used:
    label: 'Description',
);
```

**editorConfig**

This method allows you to pass a PHP array of the configuration options to set passed to the js editor ```data-editor``` attribute:

```php
use Tobento\App\Crud\Field;

new Field\TextEditor(name: 'desc')
    ->editorConfig([
        'toolbar' => [
            'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'bold', 'italic', 'underline', 'strike',
            'ol', 'ul', 'quote', 'pre', 'code',
            'undo', 'redo', 'sourcecode', 'clear',
            'link', 'tables', 'style.fonts', 'style.text.colors'
        ]
    ]);
```

**Security**

This field does **NOT** sanitize the input in any way. You should sanitize the input or output using the [App HTML Sanitizer](https://github.com/tobento-ch/app-html-sanitizer), so you can safely render the value without escaping.

If you use a [Repository Storage With Columns](https://github.com/tobento-ch/service-repository-storage#repository-with-columns), you may use the ```read``` or ```write``` method on the column to clean the value:

```php
use Tobento\Service\Repository\Storage\Column\Text;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\StorageRepository;
use function Tobento\App\HtmlSanitizer\sanitizeHtml;

class ExampleRepository extends StorageRepository
{
    protected function configureColumns(): iterable|ColumnsInterface
    {
        return [
            // ...
            new Column\Text('desc', type: 'text')
                // as there might be data stored before, we clean the html on reading:
                ->read(fn (string $value): string => sanitizeHtml($value))
                
                // clean the html on writing:
                ->write(fn (string $value): string => sanitizeHtml($value))
            // ...
        ];
    }
}
```

Sure, you may sanitize the html depending on the context such as in your view file:

```php
<?= $view->sanitizeHtml(html: $html) ?>
```

#### Value Field

The value field may be used if you want to set the value directly on the field. The value will never be set by any user input.

```php
use Tobento\App\Crud\Field;

new Field\Value(
    name: 'title',
    // you may set a label, otherwise name is used:
    label: 'TITLE',
);
```

**Value**

Use the ```value``` method to set the value for the field:

```php
use Tobento\App\Crud\Field;

new Field\Value(name: 'title')->value('Lorem');
```

**Defaults**

```php
use Tobento\App\Crud\Field;

new Field\Value(name: 'title')
    ->value('Lorem')
    ->indexable(true) // default false
    ->showable(true); // default false
```

### Different Fields Per Action

**Option 1**

Using the fields ```indexable```, ```creatable``` and ```editable``` methods:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        //...

        new Field\Text(name: 'sku')

            // disabled on index action:
            ->indexable(false)

            // disabled on create and store action:
            ->creatable(false)
            
            // disabled on edit, update, delete and any bulk actions such as bulk-edit and bulk-delete action:
            ->editable(false)
            
            // disabled on show action:
            ->showable(false),

        //...
    ];
}
```

**Option 2**

Using the action name:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\PrimaryId('id');

    if (in_array($action->name(), ['create', 'store'])) {
        yield new Field\Text(name: 'sku');
    }

    yield new Field\Text(name: 'title');
}
```

**Option 3**

Using the action ```setFields``` method:

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Field;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->setFields(new Field\Fields(
                new Field\PrimaryId('id'),
                new Field\Text(name: 'sku'),
            )),
    ];
}
```

### Validate Field

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'sku')
        
            // used for all actions:
            ->validate('required|alnum')
        
            // or using different validation per action:
            ->validate(
                store: 'required|alnum',
                update: 'required|alnum',
            ),
    ];
}
```

The field validation is using the [Validation Service](https://github.com/tobento-ch/service-validation) to validate the field.

**applyValidationAttributes**

By default, HTML validation attributes gets created based on your ```validate``` rules and applied for the fields [text](#text-field), [textarea](#textarea-field), [texteditor](#texteditor-field), [select](#select-field). You may disable it using the ```applyValidationAttributes``` method if you want to add validation attributes by yourself.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'sku')
            ->validate('required|alnum')
            ->applyValidationAttributes(false)
            ->attributes(['required']),
    ];
}
```

### Translatable Field

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'title')
            ->translatable(),
    ];
}
```

If you use a [Repository Storage With Columns](https://github.com/tobento-ch/service-repository-storage#repository-with-columns), make sure you use a translatable column:

```php
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;

protected function configureColumns(): iterable|ColumnsInterface
{
    return [
        //...
        new Column\Translatable(name: 'title'),
    ];
}
```

**Supported Locales**

All locales are supported as defined in the [Language Config](https://github.com/tobento-ch/app-language#language-config).

### Unstorable Field

When you set a field as unstorable using the ```storable``` method, the field gets not saved.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            ->storable(false),
    ];
}
```

### Readonly And Disabled Field

You may set a field as readonly or disabled which will be automatically an [unstorable field](#unstorable-field).

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            ->readonly()
            
            // or you may set only for specific actions:
            ->readonly(action: 'edit|update')
            
            // or you may use a closure (parameters are resolved by autowiring):
            ->readonly(
                readonly: function (ActionInterface $action, FieldInterface $field): bool {
                    return true;
                },
                action: 'edit|update'
            )
            
            ->disabled()
            
            // or you may set only for specific actions:
            ->disabled(action: 'edit|update')
            
            // or you may use a closure (parameters are resolved by autowiring):
            ->disabled(
                disabled: function (ActionInterface $action, FieldInterface $field): bool {
                    return true;
                },
                action: 'edit|update'
            )
    ];
}
```

### Formatting Field Value

Use the ```formatValue``` method to format the field value before rendering it in the ```index``` and ```show``` action.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;
use Tobento\Service\Support\HtmlString;
use Tobento\Service\Support\Str;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            // format value using a closure as formatter:
            ->formatValue(
                formatter: fn (mixed $value, Field\Text $field): string => strtoupper((string)$value),
                // you may set only for the show action:
                action: 'show', // 'index|show' is default
            )
            
            // or format value using a formatter:
            ->formatValue(
                formatter: new Field\Formatter\CssClass('float-right text-700')
                // you may set only for the index action:
                action: 'index', // 'index|show' is default
            )
            
            // you may use the HtmlString class to allow HTML and escape manually:
            ->formatValue(fn (string $value, Field\Text $field): HtmlString => new HtmlString('<p>'.Str::esc($value).'</p>')),
    ];
}
```

#### Supported Fields

* [Checkboxes Field](#checkboxes-field)
* [Radios Field](#radios-field)
* [Select Field](#select-field)
* [Text Field](#text-field)
* [Textarea Field](#textarea-field)
* [Slug Field](#slug-field)
* [Value Field](#value-field)

#### Available Formatters

**Badge Formatter**

```php
use Tobento\App\Crud\Field;

new Field\Select('status')
    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
    
    ->formatValue(
        formatter: new Field\Formatter\Badge(
            classes: ['inactive' => 'text-error', 'active' => 'text-success'],
            
            // you may change the fallback class:
            fallbackClass: 'text-black', // default
            
            // you may limit the badges:
            limit: 10,
        )
    );
```

**CssClass Formatter**

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'foo')
    ->formatValue(
        formatter: new Field\Formatter\CssClass('float-right text-700')
    );
```

**Date Formatter**

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'foo')
    ->formatValue(
        formatter: new Field\Formatter\Date(
            format: 'EE, dd. MMMM yyyy, HH:mm',
        )
    );
```

**Formatters**

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'foo')
    ->formatValue(
        formatter: new Field\Formatter\Formatters(
            new Field\Formatter\Date(),
            new Field\Formatter\CssClass('float-right text-700'),
        )
    );
```

**Str**

```php
use Tobento\App\Crud\Field;

new Field\Text(name: 'foo')
    ->formatValue(
        formatter: new Field\Formatter\Str(
            // you may trim the width:
            trimWidth: 100, // default (null)
            
            // you may change the trim marker:
            trimMarker: '...', // default
            
            // you may change the delimiter for array value:
            delimiter: ', ', // default
            
            // you may convert array value to json:
            arrayToJson: true, // false default
        )
    );
```

### Live Field

Use the ```live``` method if you wish to rerender specified fields after the user has interacted with a field. By default, the field will update on the JavaScript ```change``` event if not set otherwise on the ```live``` method. Only the [create](#create-action), [edit](#edit-action) and [copy](#copy-action) actions supports this live feature.

In the following example, the title field will rerender after the field has changed and will display the error message if validation fails:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\Text(name: 'title')
        ->validate('required|htmlclean')
        ->live();
}
```

**Debounce**

You may consider debouncing for text fields as it will prevent a network request from being sent until a user has finished typing for a certain period of time.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\Text(name: 'title')
        ->live(debounce: 300);
}
```

**On Blur**

In Addition of debouncing, you may consider using the ```onBlur``` parameter with the value ```true``` for fields as it will update the field only after the user has finished interacted with, when it becomes out of focus.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\Text(name: 'title')
        ->live(onBlur: true);
}
```

**Rerender Fields**

Use the ```fields``` parameter to define the fields being rerendered.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\Select(name: 'status')
        ->options(['draft' => 'Draft', 'published' => 'Published'])
        ->live(
            fields: ['title'],
            after: function(ActionInterface $action, Field\Select $field): void {
                $action->fields()->get('title')->value('Lorem');
            },
        );
        
    yield new Field\Text(name: 'title');
}
```

**Rerender Selectors**

In addition, of rerender fields, you may define JavaScript query selectors using the ```selectors``` parameter which content will be rerendered as well.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\Text(name: 'title')
        ->live(selectors: ['.some-class']);
}
```

**After Handler**

You may use the ```after``` parameter to define a callable which will be excuted after the field is updated.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\Select(name: 'status')
        ->options(['draft' => 'Draft', 'published' => 'Published'])
        ->live(
            fields: ['title'],
            after: function(ActionInterface $action, Field\Select $field): void {
                $action->fields()->get('title')->value('Lorem');
            },
        );
        
    yield new Field\Text(name: 'title');
}
```

**Specific Actions**

You may use the ```action``` parameter to define the action(s) to perform the live events.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    yield new Field\Text(name: 'title')
        ->live(
            action: 'create|edit',
            // action: 'create|edit|copy', // default
            debounce: 300,
        )
        ->live(
            action: 'copy',
            debounce: 500,
        );
}
```

**Supported Fields**

The live method is supported by the following fields:

* [Checkboxes Field](#checkboxes-field)
* [Options Field](#options-field)
* [Radios Field](#radios-field)
* [Select Field](#select-field)
* [Single Options Field](#single-options-field)
* [Text Field](#text-field)
* [Textarea Field](#textarea-field)

### Field Grouping

Use the ```group``` method to group fields:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')->group('Name'),
        new Field\Text(name: 'bar')->group('Name'),
        
        new Field\Text(name: 'baz')->group('Another Name'),
    ];
}
```

### Field Texts

**```requiredText```**

By default, the required text will be set automatically if you have any required [validation rules](#validate-field) set. But you may specify a custom text:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            ->requiredText('Required because of ...')
            // same as:
            ->requiredText(text: 'Required because of ...', action: 'create|edit')
            
            // or using different text per action:
            ->requiredText(text: 'Required because of ...', action: 'edit'),
    ];
}
```

**```optionalText```**

By default, the optional text will be set automatically if you do not have any required [validation rules](#validate-field) set. But you may specify a custom text:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            ->optionalText('optional ...')
            // same as:
            ->optionalText(text: 'optional ...', action: 'create|edit')
            
            // or using different text per action:
            ->optionalText(text: 'optional ...', action: 'edit'),
    ];
}
```

**```infoText```**

You may specify an info text:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;
use Tobento\Service\Support\HtmlString;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            ->infoText('Some info ...')
            // same as:
            ->infoText(text: 'Some info ...', action: 'create|edit')
            
            // or using different text per action:
            ->infoText(text: 'Some info ...', action: 'edit')
            
            // you may pass HTML (make sure it is properly escaped):
            ->infoText(new HtmlString('html')),
    ];
}
```

### Field Resolving

You may use the ```resolve``` method to set any field parameters from a resolved value or just for specific actions.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            ->resolve(
                // you may define additional parameters being resolved by autowiring!
                resolve: function (ActionInterface $action, FieldInterface $field): void {
                    $entity = $field->entity(); // EntityInterface
                    $locale = $field->locale();
                    $locales = $field->locales();
                    
                    $field->attributes(['data-foo' => 'value']);
                },
                
                // you may define for which actions to resolve, otherwise it will be resolved for all actions:
                action: 'create|edit',
            ),
    ];
}
```

### Custom Field Action

You may customize exisiting field actions or add [custom actions](#custom-action) using the ```process``` method:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\ActionInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Support\Str;

protected function configureFields(ActionInterface $action): iterable|FieldsInterface
{
    return [
        new Field\Text(name: 'foo')
            ->process(
                action: 'index',
                // you may define additional parameters being resolved by autowiring!
                processor: function (ActionInterface $action, FieldInterface $field): void {
                    $entity = $field->entity(); // EntityInterface
                    $locale = $field->locale();
                    $locales = $field->locales();
                    $html = (string)$entity->get($field->name(), '', $locale);
                    $field->html(Str::esc($html));
                }
            )
            ->process(
                action: 'create|edit',
                // you may define additional parameters being resolved by autowiring!
                processor: function (ActionInterface $action, FieldInterface $field): void {
                    $field->html('html');
                }
            )
            ->process(
                action: 'store|update',
                // you may define additional parameters being resolved by autowiring!
                processor: function (ActionInterface $action, FieldInterface $field, InputInterface $input): void {
                    // you may modify the input data:
                    $input->set($field->name(), 'data');
                }
            )
            ->process(
                action: 'stored|updated',
                // you may define additional parameters being resolved by autowiring!
                processor: function (ActionInterface $action, FieldInterface $field): void {
                    // you may do something on the custom action.
                    $actionName = $action->name(); // store or update
                    $entity = $field->entity(); // stored or updated entity
                    $oldEntity = $field->oldEntity(); // old entity before stored or updated
                }
            )
            ->process(
                action: 'delete',
                // you may define additional parameters being resolved by autowiring!
                processor: function (ActionInterface $action, FieldInterface $field): void {
                    // you may do something on delete.
                }
            )
            ->process(
                action: 'deleted',
                // you may define additional parameters being resolved by autowiring!
                processor: function (ActionInterface $action, FieldInterface $field): void {
                    // you may do something on the custom action.
                    $actionName = $action->name(); // delete
                    $entity = $field->entity(); // deleted entity
                }
            )            
            ->process(
                action: 'custom',
                // you may define additional parameters being resolved by autowiring!
                processor: function (ActionInterface $action, FieldInterface $field, InputInterface $input): void {                   
                    // you may do something on the custom action.
                }
            ),
    ];
}
```

## Actions

### Build in Actions

#### Index Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            
            // you may set a custom view:
            ->view('custom/crud/index')
    ];
}
```

**Configure Buttons**

The default buttons are named ```create```, ```edit```, ```show```, ```show.json```, ```copy``` and ```delete```.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->removeButton('delete', 'show'),
    ];
}
```

Check out the [Buttons](#buttons) section for more information.

#### Create Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Create(title: 'New product')
        
            // you may set a custom view:
            ->view('custom/crud/create')
    ];
}
```

**Configure Buttons**

The default buttons are named ```cancel```, ```save```, ```close```, ```copy``` and ```new```.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Create(title: 'New product')
            ->removeButton('create', 'edit'),
    ];
}
```

Check out the [Buttons](#buttons) section for more information.

#### Store Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Store(),
    ];
}
```

#### Edit Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Edit(title: 'Edit product'),
        
        // or using the entity:
        new Action\Edit(fn (EntityInterface $entity): string => 'Edit Product: '.$entity->get('sku'))
        
            // you may set a custom view:
            ->view('custom/crud/edit')
    ];
}
```

**Configure Buttons**

The default buttons are named ```cancel```, ```save```, ```close```, ```copy``` and ```new```.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Edit(title: 'Edit product')
            ->removeButton('copy', 'new'),
    ];
}
```

#### Update Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Update(),
    ];
}
```

**Unupdatable Entities**

Sometimes, it may be useful to prevent certain entities from being updatabed by using the ```unupdatable``` method:

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Update()
            // by entity ids using an array:
            ->unupdatable(ids: [12, 13], reason: 'Unupdatable because of...')
            
            // or using a closure:
            ->unupdatable(fn (EntityInterface $entity): bool => in_array($entity->get('sku'), ['foo', 'bar']))
            
            // or using a closure for the reason:
            ->unupdatable([3], fn (EntityInterface $entity): string => sprintf('ID %s unupdatable because of...', $entity->id())),
            
        // In addition, you may not display the edit button for those entities:
        new Action\Index('Products')
            ->displayButtonIf('edit', fn (EntityInterface $entity): bool => !in_array($entity->get('sku'), ['foo', 'bar']))
    ];
}
```

#### Copy Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Copy(title: 'Copy product'),
        
        // or using the entity:
        new Action\Copy(fn (EntityInterface $entity): string => 'Copy Product: '.$entity->get('sku'))
        
            // you may set a custom view:
            ->view('custom/crud/copy')
            //->view('crud/create') // is default view
    ];
}
```

**Configure Buttons**

The default buttons are named ```cancel```, ```save```, ```close```, ```copy``` and ```new```.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Copy(title: 'Copy product')
            ->removeButton('copy', 'new'),
    ];
}
```

#### Show Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Show(title: 'Show product'),
        
        // or using the entity:
        new Action\Show(fn (EntityInterface $entity): string => 'Product: '.$entity->get('sku')),
        
        new Action\Show(title: 'Show product')
            // you may set a custom view:
            ->view('custom/crud/show')
    ];
}
```

**Configure Buttons**

The default buttons are named ```back```.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Show(title: 'Show product')
            ->removeButton('back'),
    ];
}
```

#### Show JSON Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\ShowJson(),
    ];
}
```

#### Delete Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Delete(),
    ];
}
```

**Undeletable Entities**

Sometimes, it may be useful to prevent certain entities from being deleted by using the ```undeletable``` method:

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Delete()
            // by entity ids using an array:
            ->undeletable(ids: [12, 13], reason: 'Undeletable because of...')
            
            // or using a closure:
            ->undeletable(fn (EntityInterface $entity): bool => in_array($entity->get('sku'), ['foo', 'bar']))
            
            // or using a closure for the reason:
            ->undeletable([3], fn (EntityInterface $entity): string => sprintf('ID %s undeletable because of...', $entity->id())),
            
        // In addition, you may not display the delete button for those entities:
        new Action\Index('Products')
            ->displayButtonIf('delete', fn (EntityInterface $entity): bool => !in_array($entity->get('sku'), ['foo', 'bar']))
    ];
}
```

#### Bulk Delete Action

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\BulkDelete(),
    ];
}
```

#### Bulk Edit Action

You can make as many bulk edit actions as you want. Make sure the ```name``` parameter is unique and only contains a-z letters and hyphens.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\BulkEdit(name: 'edit-status', title: 'Edit Status')
            ->field('status'),
        
        new Action\BulkEdit(name: 'edit-multiple', title: 'Edit Multiple Fields')
            ->field('fieldname', 'another-fieldname'),
    ];
}
```

**Supported Fields**

The following fields support bulk editing:

* [Checkboxes Field](#checkboxes-field)
* [Radios Field](#radios-field)
* [Select Field](#select-field)
* [Text Field](#text-field)
* [Textarea Field](#textarea-field)

#### Bulk Tree Update Action

If using the ```crud/index-tree``` view on the ```Index``` action, which enables you to reorder entities by drag-and-drop. The ```BulkTreeUpdate``` action updates entities sort order after drag-and-drop.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index()->view('crud/index-tree'),
        
        new Action\BulkTreeUpdate()
            // you may change the field names:
            ->mapping(id: 'id', parentId: 'parent_id', sortorder: 'sortorder'), // defaults
    ];
}
```

Instead of setting the ```crud/index-tree``` view on the ```Index``` action, you may consider using the [Views Filter](#views-filter) to switch between views.

### Buttons

All build-in actions have already specified the buttons for linking to other actions. You may configure the buttons by the following methods.

You may view the action ```buttons``` method to see its configuration such as the button names.

#### Creating Buttons

**Available Buttons**

```php
$link = new Button\Link(label: 'Label', group: 'entity');
// renders an <a> element

$button = new Button\Button(label: 'Label', group: 'entity');
// renders a <button> element

$delete = new Button\Delete(label: 'Label', group: 'entity');
// renders a <form> element to delete an entity

$dropdown = new Button\Dropdown(label: 'Label', group: 'entity');

$form = new Button\Form(label: 'Label', group: 'entity')->method('POST');
// renders a <form> element with the entity id as hidden input.
```

You may use the html button if you need full control. Make sure you escape the html properly!

```php
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\Service\Tag\AttributesInterface;
use Tobento\Service\View\ViewInterface;

$html = new Button\Html(group: 'entity')
    ->html('<button>Label</button>');
    
    // Or:
    ->html(function(Button\Html $button, ViewInterface $view): string {
        $url = $button->getUrl(); // the resolved url
        $entity = $button->getEntity(); // null|EntityInterface
        $attributes = $button->getAttributes(); // AttributesInterface
        return 'html';
    });
```

**Linking Methods**

```php
$link = new Button\Link(label: 'View invoice', group: 'entity')
    // link to an existing action:
    ->linkToAction('viewInvoice')

    // link to a route:
    ->linkToRoute('viewInvoice')

    // link to a route with parameters:
    ->linkToRoute('viewInvoice', [
        'param' => 'value',
    ])

    // link to a route using a closure:
    ->linkToRoute('viewInvoice', function(EntityInterface $entity): null|array {
        return ['id' => $entity->id()];
        
        // you may return null if not to display the button:
        return null;
    })

    // link to an url:
    ->linkToUrl('https://example.com/invoice')

    // link to an url using a closure:
    ->linkToUrl(function(EntityInterface $entity): string {
        return 'https://example.com/invoice/'.$entity->id();
    });
```

**General Methods**

```php
$link = new Button\Link(label: 'View invoice', group: 'entity')
    // You may define a name:
    ->name('viewInvoice')
    // You modify the label:
    ->label('View invoice')
    // You may define an icon:
    ->icon('invoice')
    
    // You may set it as primary button:
    ->primary()
    // You may set it as raw button:
    ->raw()
    
    // You may add an attribute:
    ->attr('data-foo', 'value')
    // You may remove an attribute:
    ->removeAttr('data-foo')
    
    // You may ask to confirm the action using inline buttons:
    ->askConfirmation()
    // If a text is given, a modal is used:
    ->askConfirmation('Are you sure you want to perform this action.')
    // Or disable it if previous set:
    ->askConfirmation(false)
    
    // You may use AJAX to perform the action:
    ->ajaxAction()
    // With a success message:
    ->ajaxAction('Action performed successfully.')
    // Or disable it if previous set:
    ->ajaxAction(false);
```

#### Adding Buttons

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    $button = new Button\Link(label: 'View invoice', group: 'entity')
        ->name('viewInvoice')
        ->linkToAction('viewInvoice');
        
    return [
        new Action\Index(title: 'Products')
            ->addButton($button),
    ];
}
```

#### Removing Buttons

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->removeButton('create', 'edit'),
    ];
}
```

#### Reorder Buttons

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->reorderButtons('edit', 'delete'),
    ];
}
```

#### Modify Buttons

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Button\ButtonInterface;
use Tobento\App\Crud\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->modifyButton('edit', function(ButtonInterface $button, EntityInterface $entity): void {
                $button
                    ->label('')
                    ->icon('pencil')
                    ->primary(false)
                    ->raw(true);
            }),
    ];
}
```

#### Grouping Buttons

You may group buttons using the ```groupButtons``` method. If no group button is defined, buttons will be grouped using a dropdown button.

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Button;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->groupButtons(
                except: ['edit'],
                //only: ['show', 'delete'],
                
                // You may set a label:
                label: 'More',
                // You may set an icon:
                icon: 'dots',
                // You may set a name:
                name: 'more',
            )
            
            // or you may define a custom button:
            ->groupButtons(
                only: ['show', 'delete'],
                button: new Button\Dropdown(label: '', icon: 'dots', group: 'entity')
                    ->name('anotherGroup')
                    ->raw(),
            ),
    ];
}
```

#### Display Buttons Conditionally

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->displayButtonIf('viewInvoice', fn (EntityInterface $entity): bool => $entity->get('isPaid'))
            
            // or with bool:
            ->displayButtonIf('viewInvoice', true),
    ];
}
```

#### Confirming Button Action

You may ask to confirm the button action using the ```confirmButtonAction``` method:

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\EntityInterface;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            // asking to confirm the action using inline buttons:
            ->confirmButtonAction('delete')
            
            // if a text is given, a modal is opened asking to confirm the action:
            ->confirmButtonAction('delete', 'Are you sure you want to delete the product')
            
            // or using a callable:
            ->confirmButtonAction('delete', function(EntityInterface $entity): string {
                return sprintf('Are you sure you want to delete the product %s', $entity->id());
            })
            
            // you may disable it if already set by default:
            ->confirmButtonAction('delete', false),
    ];
}
```

#### AJAX Button Action

You may use the ```ajaxButtonAction``` method to set whether to use AJAX to perform the button action:

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            // using ajax:
            ->ajaxButtonAction('delete')
            
            // using ajax with a success message:
            ->ajaxButtonAction('delete', 'Deleted the product successfully.')
            
            // or using a callable:
            ->ajaxButtonAction('delete', function(EntityInterface $entity): string {
                return sprintf('Deleted the product %s successfully.', $entity->id());
            })
            
            // you may disable it if already set by default:
            ->ajaxButtonAction('delete', false),
    ];
}
```

#### Set Buttons

Using the ```setButtons``` method will overwrite the default buttons. Any configurable buttons methods such as ```reorderButtons``` e.g. will be ignored though!

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Button;

protected function configureActions(): iterable|ActionsInterface
{
    return [
        new Action\Index(title: 'Products')
            ->setButtons(
                new Button\Link(label: 'Create New', group: 'global')
                    ->name('create')
                    ->linkToAction('create'),
            ),
    ];
}
```

### Custom Action

You may create a custom action by the following way:

**1. Create And Add Button**

```php
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Button;

protected function configureActions(): iterable|ActionsInterface
{
    $viewInvoiceBtn = new Button\Link(label: 'View Invoice', group: 'entity')
        ->name('viewInvoice')
        // link to an action:
        ->linkToAction('viewInvoice')
        // or link to a route:
        ->linkToRoute('products.invoice.view', function(EntityInterface $entity): array {
            return ['id' => $entity->id()];
        });
        
    return [
        new Action\Index(title: 'Products')
            ->addButton($viewInvoiceBtn),
        //...
    ];
}
```

**2. Create Action**

If your added button links to a custom action using the ```linkToAction``` method, you will need to create the corresponding action, otherwise skip this step:

```php
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\EntityInterface;
use Closure;

final class ViewInvoice extends AbstractAction
{
    public function __construct(
        null|string|Closure $title = null,
    ) {
        $this->title = $title;
        $this->route('{name}.invoice.view', function(EntityInterface $entity): array {
            return ['id' => $entity->id()];
        });
    }

    public static function new(null|string|Closure $title = null): static
    {
        return new static($title);
    }
    
    public function name(): string
    {
        return 'viewInvoice';
    }
    
    public function buttons(): ButtonsInterface
    {
        if ($this->buttons instanceof ButtonsInterface) {
            return $this->buttons;
        }
        
        $this->buttons = new Buttons(
            new Button\Link(label: $this->trans('Back to index'), group: 'entity')
                ->name('back')
                ->linkToAction('index'),
        );
        
        return $this->applyButtonsConfig($this->buttons);
    }
}
```

**3. Route your action to the CRUD controller**

```php
use Tobento\Service\Routing\RouterInterface;

// After adding boots
$app->booting();

$router = $this->app->get(RouterInterface::class);

$name = App\ProductsController::RESOURCE_NAME;

// needed if you have configured bulk actions:
$router->get($name.'/invoice/{id}', [App\ProductsController::class, 'viewInvoice'])
    ->name($name.'.invoice.view');
```

Sure, you may route it to a different controller too!

**4. Create your method in the routed controller**

```php
use Psr\Http\Message\ResponseInterface;
use Tobento\App\Crud\AbstractCrudController;

class ProductsController extends AbstractCrudController
{
    public function viewInvoice(int|string $id): ResponseInterface
    {
        // ...
        return $response;
    }
}
```

## Filters

### Build in Filters

#### Checkboxes Filter

Adds multiple HTML input elements of the type checkbox filtering the checked values.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Checkboxes(name: 'colors', field: 'color')
            // specify the options using an array:
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            
            // or using a closure (parameters are resolved by autowiring):
            ->options(fn(ProductRepository $repo): array => $repo->findAllColors()),
            
            // you may set the default selected values:
            ->selected(['blue', 'red'])
            
            // you may add attributes applied to all input elements:
            ->attributes(['data-foo' => 'foo'])
            
            // you may change the comparison:
            ->comparison('in') // = (default)
            // 'in', 'not like', 'contains'
            
            // hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // display in table at the field:
            ->group('field')
            
            // you may set a label:
            ->label('Colors')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
        
        // you may use dot notation for the name and
        // use -> for the field (JSON) if your repository supports it:
        new Filter\Checkboxes(name: 'options.color', field: 'options->color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->comparison('contains'),
    ];
}
```

**Example using the after method**

You may use the ```after``` method to define the filters where parameters if you are not define a field or for custom filtering.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Checkboxes(name: 'categories') // no field defined!
            // specify the options using an array:
            ->options(['1' => 'Foo Category', '3' => 'Bar Category'])
            
            // you may use the after method to set the filters where parameters:
            ->after(function(Filter\Checkboxes $filter, FiltersInterface $filters, ProductToCategoryRepository $repo): void {
                if (empty($filter->getSelected())) {
                    return;
                }
                
                $ids = $repo->findProductIdsForCategoryIds(
                    categoryIds: $filter->getSelected(),
                    
                    // you may get the limit from the filters:
                    limit: $filters->getLimitParameter()[0] ?? 100,
                );

                $filter->setWhereParameters(['id' => ['in' => $ids]]);
            }),
    ];
}
```

#### Clear Button Filter

Filter to display a button for clearing filter(s).

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\ClearButton()
        
            // hide on default:
            ->open(false)

            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // you may set a custom label:
            ->label('Clear all filters')
            
            // you may set button attributes:
            ->attributes(['data-foo' => 'value']),
        
        // or clear specific filter by its names:
        new Filter\ClearButton(
            filters: ['foo', 'bar'],
            name: 'unique-filter-name', // only if multiple clear button filters
        )->label('Clear foo and bar filters'),
    ];
}
```

#### Columns Filter

Filter to display only the selected columns.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Columns()
            // you may set the default active columns,
            // otherwise the first 5 fields and actions will be used.
            ->default('title', 'date', 'actions')
            
            // you may reorder the columns:
            ->reorder('title', 'date', 'actions')
            
            // you may disable that columns can be sorted by dragging.
            ->sortable(false)
            
            // hide on default:
            ->open(false)

            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // you may set a label:
            ->label('Columns')
            
            // you may set a description:
            ->description('The columns to display') // is default text
            
            // you may set a custom view:
            ->view('custom/crud/filter')
    ];
}
```

#### Datalist Filter

Filter to display a HTML datalist element.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Datalist(name: 'list-titles')
            // specify the options using an array:
            ->options(['foo', 'bar'])
            
            // or using a closure (parameters are resolved by autowiring):
            ->options(fn(ProductRepository $repo): array => $repo->findAllTitles()),
        
        // set the list attributes on the filter you want the datalist to be displayed:
        new Filter\Input(name: 'data', field: 'title')
            ->attributes(['list' => 'list-titles']),
    ];
}
```

**Using optionsFromField method**

You may use the ```optionsFromField``` method to easily retrive options from the specified field. Your repository must be  of [Storage Repository](https://github.com/tobento-ch/service-repository-storage#storage-repository), otherwise it gets ignored.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;
use Tobento\Service\Repository\Storage\StorageRepository;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Datalist(name: 'list-titles')
            ->optionsFromField(field: 'title', limit: 50),
            
            // or specify fromInput parameters, applying a where like query:
            ->optionsFromField(field: 'title', fromInput: 'data', limit: 50),
        
        // set the list attributes on the filter you want the datalist to be displayed:
        new Filter\Input(name: 'data', field: 'title')
            ->attributes(['list' => 'list-titles']),
    ];
}
```

**Examples using options with a closure**

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\InputInterface;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Datalist(name: 'list-titles')
            
            // $input, $action and $filter parameters will always be available,
            // any other parameters are resolved by autowiring:
            ->options(function(ProductRepository $repo, InputInterface $input, ActionInterface $action, Filter\Datalist $filter): array {
                // You may get the locales from the action:
                $locale = $action->getLocale();
                $locales = $action->getLocales();
                
                // You may get any fields:
                $field = $action->fields()->get(name: 'name');
                
                // Be careful with $input values as they may come from user input!
                $value = $input->get('data');

                if (!empty($value) && is_string($value)) {
                    // Example if storage repository by using the underlying storage query builder:
                    return $repo->query()->where('title->en', 'like', $value.'%')->column('title->en')->all();
                    
                    // Example using a custom repository method:
                    return $repo->findAllTitlesFromValue(value: $value, locale: $locale);
                }

                return [];
            }),
    ];
}
```

#### Editable Columns Filter

Filter to enable inline editing fields in table columns.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\EditableColumns('sku', 'title') // define the editable columns
        
            // hide on default:
            ->open(false)

            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // you may set a label:
            ->label('Editable Columns')
            
            // you may set a description:
            ->description('The columns to edit in table.'), // is default text
    ];
}
```

**Supported Fields**

The following fields support inline table editing:

* [Checkboxes Field](#checkboxes-field)
* [Radios Field](#radios-field)
* [Select Field](#select-field)
* [Text Field](#text-field)
* [Textarea Field](#textarea-field)

#### Fields Filter

The fields filter displays an [input filter](#input-filter) or [select filter](#select-filter) on each field in the table column.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        ...new Filter\Fields()
            
            // set the fields from the action:
            ->fields($action->fields())
            
            // display only specific:
            ->only('sku', 'title')
            
            // or
            ->except('sku', 'title')
            
            ->toFilters(),
    ];
}
```

If you want a custom filter for the field just do not display the filter using the ```except``` method and add your custom filter with the group ```field```:

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        ...new Filter\Fields()
            ->fields($action->fields())
            ->except('sku')
            ->toFilters(),
        // custom sku filter:
        new Filter\Input(name: 'sku', field: 'sku')
            ->group('field'),
    ];
}
```

#### Fields Sort Order Filter

The fields sort order filter provides the option to order fields up and down.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\FieldsSortOrder()
        
            // display only specific:
            ->only('sku', 'title')
            
            // or
            ->except('sku', 'title')

            // you may add a default sort order:
            ->addDefault(name: 'title', value: 'asc') // 'asc' or 'desc'
            
            // you may add an active sort order:
            ->addActive(name: 'title', value: 'asc'), // 'asc' or 'desc'
    ];
}
```

#### Group Filter

Filter to group other filters.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Group(name: 'group-key')
            // display in modal:
            ->group('modal')
            
            // you may hide the grouped filters:
            ->open(false)
            
            // you may set a custom label, otherwise name is used:
            ->label('Group Name')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter/group'),
            
        // Next, assign filters to the group:
        new Filter\Columns()->group('group-key'),
        
        // The modal button to open filters:
        new Filter\ModalButton(),
    ];
}
```

#### Input Filter

Adds an HTML input element to filter the field if specified.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Input(name: 'foo', field: 'email')
            // you may change the type:
            ->type('email') // text (default)
            
            // you may add attributes for the input element:
            ->attributes(['placeholder' => 'value'])
            
            // you may change the comparison:
            ->comparison('like') // = (default)
            // '=', '!=', '>', '<', '>=', '<=', '<>', '<=>', 'like', 'not like', 'contains'
            
            // hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // display in table at the field:
            ->group('field')
            
            // you may set a label:
            ->label('Foo')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
        
        // you may use dot notation for the name and
        // use -> for the field (JSON) if your repository supports it:
        new Filter\Input(name: 'options.color', field: 'options->color')
            ->comparison('contains'),
    ];
}
```

**Example using the after method**

You may use the ```after``` method to define the filters where parameters if you are not define a field or for custom filtering.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Input(name: 'colors') // no field defined!
            // you may use the after method to set the filters where parameters:
            ->after(function(Filter\Input $filter, FiltersInterface $filters): void {
                if (!is_string($filter->getSearchValue())) {
                    return;
                }

                $filter->setWhereParameters(['fieldname' => ['=' => $filter->getSearchValue()]]);
            }),
    ];
}
```

#### Locale Filter

Adds a filter to switch the resource locale. Make sure this filter is added as the first filter because other filters may depend on the locales.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        // should be added as the first filter!
        new Filter\Locale()
            // hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // you may set a label:
            ->label('Resource Locale')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
    ];
}
```

#### Menu Filter

Adds a menu based on the specified items. Records will be filtered by the active menu item. The filter uses the [Menu Service](https://github.com/tobento-ch/service-menu) to render the menu.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Menu(name: 'categories', field: 'category_id')
            // specify the menu items using an array:
            ->items([
                ['id' => 'foo', 'name' => 'Foo', 'parent' => null],
                ['id' => 'bar', 'name' => 'Bar', 'parent' => 'foo'],
            ])
            
            // or using a closure (parameters are resolved by autowiring):
            ->items(fn(CategoryRepository $repo): array => $repo->findAllMenuItems()),
            
            // you may change the comparison:
            ->comparison('like') // = (default)
            // '=', '!=', '>', '<', '>=', '<=', '<>', '<=>', 'like', 'not like', 'contains'
            
            // hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // you may set a label:
            ->label('Label')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
    ];
}
```

**Example using the after method**

You may use the ```after``` method to define the filters where parameters if you are not define a field.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Menu(name: 'categories') // no field defined!
            // specify the menu items using an array:
            ->items([
                ['id' => 'foo', 'name' => 'Foo', 'parent' => null],
                ['id' => 'bar', 'name' => 'Bar', 'parent' => 'foo'],
            ])
            
            // you may use the after method to set the filters where parameters:
            ->after(function(Filter\Menu $filter, FiltersInterface $filters, ProductToCategoryRepository $repo): void {
                if (!is_string($filter->getActive())) {
                    return;
                }
                
                $ids = $repo->findProductIdsForCategoryId(
                    categoryId: $filter->getActive(),
                    
                    // you may get the limit from the filters:
                    limit: $filters->getLimitParameter()[0] ?? 100,
                );

                $filter->setWhereParameters(['id' => ['in' => $ids]]);
            }),
    ];
}
```

#### Modal Button Filter

Filter to display a button for opening the filters in the modal. Will only be displayed if there are filters with the group ```modal``` though.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\ModalButton()
        
            // hide on default:
            ->open(false)

            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in the aside area:
            ->group('aside')
            
            // you may set a custom label:
            ->label('Filters')
            
            // you may set button attributes:
            ->attributes(['data-foo' => 'value']),
        
        // or
        new Filter\ModalButton(
            name: 'unique-filter-name', // only if multiple clear button filters
        ),
    ];
}
```

#### Pagination Filter

Adds pagination for the items.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        // ...
        // must be the last filter added!
        new Filter\Pagination()
        
            // you may set a label:
            ->label('Current Page')
            
            // you may unset the default description:
            ->description('')
            
            // you may hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // display below table:
            ->group('footer')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
        
        // Or:
        new Filter\Pagination(
            // Specify the default items to show per page:
            show: 100,
            
            // Specify the max. items (limit) to show per page:
            maxItemsPerPage: 1000, // default 1000
        ),
    ];
}
```

#### Pagination Items Per Page Filter

Adds the option to specify how many items to display.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\PaginationItemsPerPage()
        
            // you may unset the default label:
            ->label('')
            
            // you may set the default description:
            ->description('Per page')
            
            // you may hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
        
        // Or:
        new Filter\PaginationItemsPerPage(
            // Specify the default items to show per page:
            show: 50, // default 100
        ),
        
        // required filter, otherwise the above filter is not displayed at all.
        new Filter\Pagination(),
    ];
}
```

#### Radios Filter

Adds multiple HTML input elements of the type radio filtering the selected value.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Radios(name: 'status', field: 'status')
            // specify the options using an array:
            ->options(['_none' => 'None', 'pending' => 'Pending', 'paid' => 'Paid'])
            // you may set a value to '_none' which skips filtering if selected!
            
            // or using a closure (parameters are resolved by autowiring):
            ->options(fn(StatusRepository $repo): array => $repo->findAll()),
            
            // you may set the default selected value:
            ->selected('_none')
            
            // you may add attributes applied to all input elements:
            ->attributes(['data-foo' => 'foo'])
            
            // you may change the comparison:
            ->comparison('=') // = (default)
            // '=', '!=', '>', '<', '>=', '<=', '<>', '<=>', 'like', 'not like', 'contains'
            
            // hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // display in table at the field:
            ->group('field')
            
            // you may set a label:
            ->label('Status')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
        
        // you may use dot notation for the name and
        // use -> for the field (JSON) if your repository supports it:
        new Filter\Radios(name: 'options.color', field: 'options->color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->comparison('contains'),
    ];
}
```

**Example using the after method**

You may use the ```after``` method to define the filters where parameters if you are not define a field, allowing multiple selection  or for custom filtering.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Radios(name: 'colors') // no field defined!
            // specify the options using an array:
            ->options(['1' => 'Foo Category', '3' => 'Bar Category'])
            
            // you may use the after method to set the filters where parameters:
            ->after(function(Filter\Radios $filter, FiltersInterface $filters, ProductToCategoryRepository $repo): void {
                if (empty($filter->getSelected())) {
                    return;
                }
                
                $ids = $repo->findProductIdsForCategoryId(
                    categoryId: $filter->getSelected(),
                    
                    // you may get the limit from the filters:
                    limit: $filters->getLimitParameter()[0] ?? 100,
                );

                $filter->setWhereParameters(['id' => ['in' => $ids]]);
            }),
    ];
}
```

#### Select Filter

Adds an HTML select element with options to filter the field if specified.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Select(name: 'colors', field: 'color')
            // specify the options using an array:
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            
            // or using a closure (parameters are resolved by autowiring):
            ->options(fn(ProductRepository $repo): array => $repo->findAllColors()),
            
            // you may set the default selected value(s):
            ->selected('blue')
            ->selected(['blue', 'red']) // if multiple
            
            // you may add attributes for the select element:
            ->attributes(['size' => '3', 'multiple'])
            
            // you may change the comparison:
            ->comparison('like') // = (default)
            // '=', '!=', '>', '<', '>=', '<=', '<>', '<=>', 'like', 'not like', 'contains'
            
            // you may change the empty option:
            ->emptyOption(value: 'none', label: '---')
            // or you may disable empty option setting it null:
            ->emptyOption(value: null)
            
            // hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // display in table at the field:
            ->group('field')
            
            // you may set a label:
            ->label('Colors')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
        
        // you may use dot notation for the name and
        // use -> for the field (JSON) if your repository supports it:
        new Filter\Select(name: 'options.color', field: 'options->color')
            ->options(['blue' => 'Blue', 'red' => 'Red'])
            ->comparison('contains'),
    ];
}
```

**Example using the after method**

You may use the ```after``` method to define the filters where parameters if you are not define a field, allowing multiple selection  or for custom filtering.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Select(name: 'colors') // no field defined!
            // specify the options using an array:
            ->options(['1' => 'Foo Category', '3' => 'Bar Category'])
            
            // you may add attributes for the select element:
            //->attributes(['size' => '3', 'multiple'])
            
            // you may use the after method to set the filters where parameters:
            ->after(function(Filter\Select $filter, FiltersInterface $filters, ProductToCategoryRepository $repo): void {
                if (!is_string($filter->getSelected())) {
                    return;
                }
                
                // $filter->getSelected()
                // will return an array if multiple or null if none selected
                
                $ids = $repo->findProductIdsForCategoryId(
                    categoryId: $filter->getSelected(),
                    
                    // you may get the limit from the filters:
                    limit: $filters->getLimitParameter()[0] ?? 100,
                );

                $filter->setWhereParameters(['id' => ['in' => $ids]]);
            }),
    ];
}
```

#### Views Filter

Adds multiple HTML input elements of the type radio to switch views.

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Input\Input;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Views()
            // specify the views to switch:
            ->addView(id: 'default', view: 'crud/index', label: 'Table')
            ->addView(id: 'tree', view: 'crud/index-tree', label: 'Tree')
            
            // you may set the default view:
            ->defaultView(id: 'tree')
            
            // you may use the after method to modify filters:
            ->after(function(Filter\Views $filter, FiltersInterface $filters, ActionInterface $action): void {           
                if ($filter->viewChanged()) {
                    foreach($filters as $f) {
                        if (in_array($f->name(), ['columns'])) {
                            continue;
                        }
                        
                        $f->apply(new Input([]), $filters, $action);
                    }
                }

                if ($filter->viewId() === 'tree') {
                    $filters->get('sort')?->addActive(name: 'sortorder', value: 'asc');
                }
            }),
                
            // hide on default:
            ->open(false)
            
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in modal:
            ->group('modal')
            
            // display in the aside area:
            ->group('aside')
            
            // display in table at the field:
            ->group('field')
            
            // you may set a label:
            ->label('View')
            
            // you may set a description:
            ->description('Lorem ipsum')
            
            // you may set a custom view:
            ->view('custom/crud/filter'),
    ];
}
```

Check out the [Bulk Tree Update Action](#bulk-tree-update-action) section if you use ```crud/index-tree``` view which is required to update entities sort order after drag-and-drop.

### Filter Groups

**Available filter groups (if the filter supports it)**

* ```header``` display above table
* ```footer``` display below table
* ```modal``` display in modal
* ```aside``` display in the aside area
* ```field``` display in table at the field if exists
            
**Example**

```php
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Action\ActionInterface;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Input(name: 'foo', field: 'title')
        
            // display above table (default):
            ->group('header')
            
            // display below table:
            ->group('footer')
            
            // display in table at the field:
            ->group('field'),
    ];
}
```

### Display Filters Conditionally

```php
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Action\ActionInterface;

protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
{
    return [
        new Filter\Input(name: 'foo', field: 'title')
            ->displayIf(function(FiltersInterface $filters, FilterInterface $filter, ActionInterface $action): bool {
                // your condition
                return true;
            }),
            
            // or with bool:
            ->displayIf(true),
    ];
}
```

### Filter Processor

By default, filtered data is stored in cookies, you may store the data in session instead by the following code:

```php
use Tobento\App\Crud\FilterProcessor;
use Tobento\App\Crud\FilterProcessorInterface;

$app->set(FilterProcessorInterface::class, FilterProcessor::class)->with(['storage' => 'session']);
```

### Filter Limitations

As the [CRUD controller](#crud-controller) uses the [Repository Interface](https://github.com/tobento-ch/service-repository#repository-interface) and filtering is done using the ```findAll``` method you are not able to make complex queries by filters.

```php
$entities = $repository->findAll(
    where: $filters->getWhereParameters(),
    orderBy: $filters->getOrderByParameters(),
    limit: $filters->getLimitParameter(),
);
```

## Resource Types

You may create different resource types such as a ```BlogArticleType``` and ```DefaultArticleType``` for instance. Each type can have its own fields configured.

### Create Resource Types

```php
use Tobento\App\Crud\ResourceTypes;
use Tobento\App\Crud\ResourceTypesInterface;

interface ArticleTypesInterface extends ResourceTypesInterface
{
    //
}

class ArticleTypes extends ResourceTypes implements ArticleTypesInterface
{
    //
}
```

### Create Resource Type

```php
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\ResourceTypeInterface;

class BlogArticleType implements ResourceTypeInterface
{
    /**
     * Returns the type name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'blog';
    }
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Blog Article';
    }
    
    /**
     * Configure fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\PrimaryId('id');
        
        yield new Field\Text('title');
        
        if (in_array($action->name(), ['create', 'store'])) {
            yield new Field\Text(name: 'type')
                ->type('hidden')
                ->value($this->name())
                ->validate(store: sprintf('required|in:%s', $this->name()));
        }
    }
    
    /**
     * Configure actions.
     *
     * @param ActionsInterface $actions
     * @return void
     */
    public function configureActions(ActionsInterface $actions): void
    {
        // add create button on index action:
        if ($indexAction = $actions->get('index')) {
            $indexAction->addButton(
                new Button\Link(label: 'Blog Article', group: 'global')
                    ->name('create.blog')
                    ->linkToRoute('articles.create', function(EntityInterface $entity): array {
                        return ['type' => $this->name()];
                    })
            );
        }
    }
}
```

### Create Controller Supporting Resource Types

```php
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\Service\Repository\RepositoryInterface;

class ArticleController extends AbstractCrudController
{
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'articles';
    
    /**
     * Create a new ArticleController.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(
        ArticleRepository $repository,
        protected ArticleTypesInterface $types,
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        // return the fields for the index action:
        if  ($action->name() === 'index') {
            return [
                new Field\PrimaryId('id'),
                new Field\Select(name: 'type', label: 'Type')->options($this->types->titles()),
                new Field\Text('sku'),
                //...
            ];
        }
        
        // return the specific fields from the type:
        $typeName = $action->getInput()->get('type', 'default');
        
        if  (in_array($action->name(), ['edit', 'update', 'delete'])) {
            $typeName = $action->entity()->get('type', 'default');
        }
        
        if (!$this->types->has($typeName)) {
            throw new NotFoundException();
        }
        
        return $this->types->get($typeName)->configureFields($action);        
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        $actions = new Action\Actions(
            new Action\Index(title: 'Articles')
                ->removeButton('create')
                ->groupButtons(
                    button: new Button\Dropdown(label: 'Create New', icon: '', group: 'global')->name('create.list'),
                ),
            //...
        );
        
        // configure actions for each type:
        foreach($this->types as $type) {
            $type->configureActions($actions);
        }
        
        return $actions;
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [
            new Filter\Columns()->open(false),
            //...
        ];
    }
}
```

### Configure Resource Types

Implement your interface within the app:

```php
$app->set(
    ArticleTypesInterface::class,
    static function (): ArticleTypesInterface {
        return new ArticleTypes(
            new BlogArticleType(),
        );
    }
);
```

Sometimes, it may be useful to add additional types from another location using the [App on](https://github.com/tobento-ch/app#on) method to add types only on demand:

```php
$app->on(
    ArticleTypesInterface::class,
    static function (ArticleTypesInterface $types): void {
        $types->add(new AnotherArticleType());
    }
);
```

## Security

Keep in mind that the repository is responsible to protect against any SQL injections for instance! If your are using the [Repository Storage](https://github.com/tobento-ch/service-repository-storage) you will be save.

## Testing

You may test your crud controllers by using the provided test classes. Just make sure you have installed the [App Testing](https://github.com/tobento-ch/app-testing) bundle.

### Crud Controller Testing

To test your crud controller extend the ```AbstractCrudTestCase``` class. Next, use ```createApp``` method to [create the test app](https://github.com/tobento-ch/app-testing#getting-started) as usual and define your crud controller using the ```getCrudController``` method. Finally, write your tests:

```php
use Tobento\App\Crud\Testing\AbstractCrudTestCase;
use Tobento\App\AppInterface;

final class ProductCrudControllerTest extends AbstractCrudTestCase
{
    public function createApp(): AppInterface
    {
        return require __DIR__.'/../app/app.php';
        
        // or creating a tmp app:
        $app = $this->createTmpApp(rootDir: __DIR__.'/..');
        $app->boot(\Tobento\App\Crud\Boot\Crud::class);
        // boot your product crud boot which routes your crud controller:
        $app->boot(ProductCrudBoot::class);
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return ProductCrudController::class;
    }
    
    public function testIndexAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'products');
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5);
    }
}
```

#### Seeding

If you are using [Storage Repositories](https://github.com/tobento-ch/service-repository-storage) with defined [columns](https://github.com/tobento-ch/service-repository-storage#repository-with-columns), you may use ```getSeedFactory``` method to seed entities automatically. Make sure you use a [Reset Database Strategy](https://github.com/tobento-ch/app-testing#reset-databases).

```php
use Tobento\App\Crud\Testing\AbstractCrudTestCase;

final class ProductCrudControllerTest extends AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    // ...
    
    public function testIndexPageMultipleEntitiesAreDisplayed()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'products');
        
        // seed 5 products:
        $this->getSeedFactory()->times(5)->create();
        
        // seed 3 inactive products:
        $this->getSeedFactory(['active' => false])->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(8);
    }
}
```

For any other repositories checkout the [App Seeding - Repository](https://github.com/tobento-ch/app-seeding#repository) documentation as to create a seed factory or use the ```getSeedDefinition``` method to define a definition for your repository seed factory:

```php
use Tobento\App\Crud\Testing\AbstractCrudTestCase;
use Tobento\Service\Seeder\SeedInterface;
use Tobento\Service\Seeder\Lorem;

final class ProductCrudControllerTest extends AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    // ...

    protected function getSeedDefinition(): null|\Closure
    {
        return function (SeedInterface $seed): array {
            return [
                'sku' => Lorem::word(number: 1),
                'desc' => Lorem::sentence(number: 2),
            ];
        };
    }
    
    public function testIndexPageMultipleEntitiesAreDisplayed()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'products');
        
        // seed 5 products:
        $this->getSeedFactory()->times(5)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5);
    }
}
```

#### Uri Generation

You may use the following methods to generate request uris for the [actions](#actions):

```php
public function testAnyPage()
{
    $http = $this->fakeHttp();
    
    // index action uri:
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    // equal to uri: 'products'
    
    // index action uri with locale:
    $http->request(method: 'GET', uri: $this->generateIndexUri(locale: 'de'));
    // equal to uri: 'de/products'
    
    // bulk action uri:
    $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-edit'));
    // equal to uri: 'products/bulk/bulk-edit'
    
    // bulk action uri with locale:
    $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-edit', locale: 'de'));
    // equal to uri: 'de/products/bulk/bulk-edit'    
    
    // create action uri:
    $http->request(method: 'GET', uri: $this->generateCreateUri());
    // equal to uri: 'products/create'
    
    // create action uri with locale:
    $http->request(method: 'GET', uri: $this->generateCreateUri(locale: 'de'));
    // equal to uri: 'de/products/create'
    
    // store action uri:
    $http->request(method: 'POST', uri: $this->generateStoreUri());
    // equal to uri: 'products'
    
    // store action uri with locale:
    $http->request(method: 'POST', uri: $this->generateStoreUri(locale: 'de'));
    // equal to uri: 'de/products'
    
    // edit action uri:
    $http->request(method: 'GET', uri: $this->generateEditUri(id: 2));
    // equal to uri: 'products/2/edit'
    
    // edit action uri with locale:
    $http->request(method: 'GET', uri: $this->generateEditUri(id: 2, locale: 'de'));
    // equal to uri: 'de/products/2/edit'
    
    // update action uri:
    $http->request(method: 'PUT|PATCH', uri: $this->generateUpdateUri(id: 2));
    // equal to uri: 'products/2'
    
    // update action uri with locale:
    $http->request(method: 'PUT|PATCH', uri: $this->generateUpdateUri(id: 2, locale: 'de'));
    // equal to uri: 'de/products/2'
    
    // copy action uri:
    $http->request(method: 'GET', uri: $this->generateCopyUri(id: 2));
    // equal to uri: 'products/2/copy'
    
    // copy action uri with locale:
    $http->request(method: 'GET', uri: $this->generateCopyUri(id: 2, locale: 'de'));
    // equal to uri: 'de/products/2/copy'
    
    // delete action uri:
    $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 2));
    // equal to uri: 'products/2'
    
    // delete action uri with locale:
    $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 2, locale: 'de'));
    // equal to uri: 'de/products/2'
    
    // show action uri:
    $http->request(method: 'GET', uri: $this->generateShowUri(id: 2));
    // equal to uri: 'products/2'
    
    // show action uri with locale:
    $http->request(method: 'GET', uri: $this->generateShowUri(id: 2, locale: 'de'));
    // equal to uri: 'de/products/2'
}
```

#### Asserts

The following asserts are avaliable using the default views.

##### Index Action Asserts

**assertCrudIndexEntityCount**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(3)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(3)
        
        // you may specify a custom error message:
        ->assertCrudIndexEntityCount(3, 'Custom message');
}
```

**assertCrudIndexEntityExists**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(3)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexEntityExists(entityId: 2)
        
        // you may specify buttons the entity should have or not:
        ->assertCrudIndexEntityExists(entityId: 2, withButtons: ['edit', 'delete'], withoutButtons: ['show'])
        
        // you may specify a custom error message when entity does not exists:
        ->assertCrudIndexEntityExists(entityId: 2, message: 'Custom message');
}
```

**assertCrudIndexEntityMissing**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexEntityMissing(entityId: 2)
        
        // you may specify a custom error message:
        ->assertCrudIndexEntityMissing(entityId: 2, message: 'Custom message');
}
```

**assertCrudIndexHeaderColumnsExists**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexHeaderColumnsExists(columns: ['username', 'actions'])
        
        // you may specify a custom error message:
        ->assertCrudIndexHeaderColumnsExists(columns: ['username'], message: 'Custom message');
}
```

**assertCrudIndexHeaderColumnsMissing**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexHeaderColumnsMissing(columns: ['username', 'actions'])
        
        // you may specify a custom error message:
        ->assertCrudIndexHeaderColumnsMissing(columns: ['username'], message: 'Custom message');
}
```

**assertCrudIndexFiltersExists**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexFiltersExists(filters: ['columns'], group: 'header')
        
        // you may specify a custom error message:
        ->assertCrudIndexFiltersExists(filters: ['pagination_items'], group: 'footer', message: 'Custom message')
        
        // use the "field" group and field name to check entity field filters:
        ->assertCrudIndexFiltersExists(filters: ['username'], group: 'field');
}
```

**assertCrudIndexFiltersMissing**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexFiltersMissing(filters: ['columns'], group: 'header')
        
        // you may specify a custom error message:
        ->assertCrudIndexFiltersMissing(filters: ['pagination_items'], group: 'footer', message: 'Custom message')
        
        // use the "field" group and field name to check entity field filters:
        ->assertCrudIndexFiltersMissing(filters: ['username'], group: 'field');
}
```

**assertCrudIndexButtonsExists**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexButtonsExists(buttons: ['create'], group: 'global')
        
        // you may specify a custom error message:
        ->assertCrudIndexButtonsExists(buttons: ['create'], group: 'global', message: 'Custom message');
}
```

**assertCrudIndexButtonsMissing**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexButtonsMissing(buttons: ['create'], group: 'global')
        
        // you may specify a custom error message:
        ->assertCrudIndexButtonsMissing(buttons: ['create'], group: 'global', message: 'Custom message');
}
```

**assertCrudIndexBulkActionsExists**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexBulkActionsExists(actions: ['edit-status'])
        
        // you may specify a custom error message:
        ->assertCrudIndexBulkActionsExists(actions: ['edit-status'], message: 'Custom message');
}
```

**assertCrudIndexBulkActionsMissing**

```php
public function testIndexAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateIndexUri());
    
    $this->getSeedFactory()->times(1)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexBulkActionsMissing(actions: ['edit-status'])
        
        // you may specify a custom error message:
        ->assertCrudIndexBulkActionsMissing(actions: ['edit-status'], message: 'Custom message');
}
```

##### Form Asserts

You may use the form asserts to test your crud controller [create](#create-action), [edit](#edit-action) and [copy](#copy-action) actions.

**assertCrudFormFieldExists**

```php
public function testEditAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
    // or create uri:
    //$http->request(method: 'GET', uri: $this->generateCreateUri());
    
    $this->getSeedFactory()->times(2)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudFormFieldExists(field: 'username')
        
        // Or with more options:
        ->assertCrudFormFieldExists(
            field: 'username',
            
            // you may specify the label:
            label: 'Username',
            
            // you may specify the required text:
            requiredText: 'Required because ...',
            
            // you may specify the optional text:
            optionalText: 'optional',
            
            // you may specify the info text:
            infoText: 'Some info text',
            
            // you may specify the error text (validation):
            errorText: 'The title.en is required.',
            // first locale if translatable!
            
            // you may specify if it is translatable field or not:
            translatable: false,
            
            // you may specify a custom error message:
            message: 'Custom message',
        );
}
```

**assertCrudFormFieldMissing**

```php
public function testEditAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
    // or create uri:
    //$http->request(method: 'GET', uri: $this->generateCreateUri());
    
    $this->getSeedFactory()->times(2)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudFormFieldMissing(field: 'username')
        
        // you may specify a custom error message:
        ->assertCrudFormFieldMissing(field: 'username', message: 'Custom message');
}
```

##### Asserts Selectors

If you [customize the view](https://github.com/tobento-ch/app-view#themes) files, make sure you have the following HTML attributes defined, otherwise you may write [custom asserts](https://github.com/tobento-ch/app-testing#response-macros) to fit your views!

**Index Action Asserts**

* ```[data-entity-id="ID"]``` on each entity table rows.
* ```[data-button="name"]``` will be rendered by button automatically.
* ```[data-header-col="name"]``` on each table header columns.
* ```[data-filters="group_name"]``` on each filters group.
* ```[data-filter="name"]``` on each filters within a group.
* ```[data-bulk-action="name"]``` on each bulk actions.

**Form Asserts**

* ```[data-field="name"]``` on each fields.
* ```[data-field="name"] label``` on each fields for the label, required and optional text.
* ```[data-field="name"] p``` on each fields for the info text.
* ```[data-field="name"] .error``` on each fields for the error text (validation).
* ```[data-translatable]``` on each translatable fields.

##### Example Tests

**Index Filter Action**

```php
public function testFilterAction()
{
    $http = $this->fakeHttp();
    $http->request(
        method: 'GET',
        uri: $this->generateIndexUri(),
        query: ['filter' => ['field' => ['type' => 'business']]],
    );

    $this->getSeedFactory(['type' => 'private'])->times(1)->create();
    $this->getSeedFactory(['type' => 'business'])->times(2)->create();
    
    $http->response()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(2);
}
```

**Store Action**

```php
public function testStoreAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
        'email' => 'tom@example.com',
        'smartphone' => '555', // is required
    ]);

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateIndexUri());

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(1);

    $this->assertSame(1, $this->getCrudRepository()->count());
}
```

**Store Action With Validation Errors**

```php
public function testStoreActionWithValidationErrors()
{
    $http = $this->fakeHttp();
    $http->previousUri($this->generateCreateUri());
    $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
        'email' => 'tom@example.com',
        //'smartphone' => '555', // is required
    ]);

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateCreateUri());

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'The smartphone is required.');

    $this->assertSame(0, $this->getCrudRepository()->count());
}
```

**Update Action**

```php
public function testUpdateAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
        'smartphone' => '555',
    ]);

    $this->getSeedFactory()->times(2)->create();

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateIndexUri());

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(2);

    $this->assertSame('555', $this->getCrudRepository()->findById(1)->smartphone());
}
```

**Update Action With Validation Errors**

```php
public function testUpdateActionWithValidationErrors()
{
    $http = $this->fakeHttp();
    $http->previousUri($this->generateEditUri(id: 1));
    $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
        'smartphone' => ['invalid'],
    ]);

    $this->getSeedFactory()->times(2)->create();

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateEditUri(id: 1));

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'The smartphone must be a string.');
}
```

**Delete Action**

```php
public function testDeleteAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));

    $this->getSeedFactory()->times(2)->create();

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateIndexUri());

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(1);

    $this->assertSame(1, $this->getCrudRepository()->count());
}
```

**Bulk Edit Action**

```php
public function testBulkEditAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-edit'))->body([
        'ids' => [2, 3],
        'smartphone' => '555',
    ]);

    $this->getSeedFactory()->times(5)->create();

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateIndexUri());

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(5);

    $this->assertNotSame('555', $this->getCrudRepository()->findById(1)->smartphone());
    $this->assertSame('555', $this->getCrudRepository()->findById(2)->smartphone());
    $this->assertSame('555', $this->getCrudRepository()->findById(3)->smartphone());
}
```

**Bulk Edit Action With Validation Errors**

```php
public function testBulkEditAction()
{
    $http = $this->fakeHttp();
    $http->previousUri($this->generateIndexUri());
    $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-edit'))->body([
        'ids' => [2, 3],
        'smartphone' => ['555'],
    ]);

    $this->getSeedFactory()->times(5)->create();

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateIndexUri());

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(5)
        ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'The smartphone must be a string.');
}
```

**Bulk Delete Action**

```php
public function testBulkDeleteAction()
{
    $http = $this->fakeHttp();
    $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-delete'))->body([
        'ids' => [2, 3],
    ]);

    $this->getSeedFactory()->times(5)->create();

    $http->response()
        ->assertStatus(302)
        ->assertLocation($this->generateIndexUri());

    $http->followRedirects()
        ->assertStatus(200)
        ->assertCrudIndexEntityCount(3);

    $this->assertNotNull($this->getCrudRepository()->findById(1));
    $this->assertNull($this->getCrudRepository()->findById(2));
    $this->assertNull($this->getCrudRepository()->findById(3));
}
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)