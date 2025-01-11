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

namespace Tobento\App\Crud\Test\Field;

use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Container\Container;
use Tobento\Service\Slugifier\SlugifierFactory;
use Tobento\Service\Slugifier\SlugifierInterface;
use Tobento\Service\Slugifier\Slugifiers;
use Tobento\Service\Slugifier\SlugifiersInterface;

class SlugTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = Field\Slug::new(name: 'name');
        $this->assertInstanceof(Field\Slug::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\Slug::new(name: 'name'));
        $this->renderTests(Field\Slug::new(name: 'name'));
        $this->nameTests(Field\Slug::class);
        $this->labelTests(Field\Slug::class);
        $this->groupTests(Field\Slug::class);
        $this->translatableTests(Field\Slug::new(name: 'name'));
        $this->localeTests(Field\Slug::new(name: 'name'));
        $this->storableTests(Field\Slug::new(name: 'name'));
        $this->indexableTests(Field\Slug::new(name: 'name'));
        $this->creatableTests(Field\Slug::new(name: 'name'));
        $this->editableTests(Field\Slug::new(name: 'name'));
        $this->readonlyTests(Field\Slug::new(name: 'name'));
        $this->disabledTests(Field\Slug::new(name: 'name'));
        $this->entityTests(Field\Slug::new(name: 'name'));
        $this->validateTests(Field\Slug::class, defaultValidate: 'string');
        $this->requiredTextTests(Field\Slug::class);
        $this->optionalTextTests(Field\Slug::class);
        $this->infoTextTests(Field\Slug::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(Field\Slug::class);
        $this->processStoreTests(Field\Slug::class);
        $this->processUpdateTests(Field\Slug::class);
        $this->processShowTests(Field\Slug::class);
    }
    
    public function testProcessCreateEdit()
    {
        $field = Field\Slug::new(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="text" value="Foo">', $field->render());
        
        $field = Field\Slug::new(name: 'name')->setEntity(new Entity(['name' => ['en' => 'Foo']]))->translatable();
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name[en]" id="name_en" type="text" value="Foo">', $field->render());
    }
    
    public function testProcessBeforeSaveWithoutSlugInputDoesNothing()
    {
        $action = Action\Store::new();
        $field = Field\Slug::new(name: 'slug');
        $input = new Input([]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame([], $input->all());
    }

    public function testProcessBeforeSaveUsesSlugInput()
    {
        $action = Action\Store::new();
        $field = Field\Slug::new(name: 'slug');
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum'], $input->all());
    }

    public function testProcessBeforeSaveUsesSlugInputTranslatable()
    {
        $action = Action\Store::new();
        $field = Field\Slug::new(name: 'slug')->translatable();
        $input = new Input(['slug' => ['en' => 'Lorem Ipsum']]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => ['en' => 'lorem-ipsum']], $input->all());
    }
    
    public function testProcessBeforeSaveTranslatableAssignesPreviousStoredSlugs()
    {
        $action = Action\Update::new();
        $field = Field\Slug::new(name: 'slug')
            ->setEntity(new Entity(['slug' => ['de' => 'de-slug']]))
            ->setLocales(['en' => 'EN', 'de' => 'DE'])
            ->translatable();
        $input = new Input(['slug' => ['en' => 'Lorem Ipsum']]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => ['de' => 'de-slug', 'en' => 'lorem-ipsum']], $input->all());
    }
    
    public function testProcessBeforeSaveUsesSlugInputTranslatableWithNoneTranslatedInput()
    {
        $action = Action\Store::new();
        $field = Field\Slug::new(name: 'slug')->translatable();
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => ['en' => 'lorem-ipsum']], $input->all());
    }
    
    public function testProcessBeforeSaveFromFieldIsIgnoredWhenHasSlugInput()
    {
        $action = Action\Store::new()
            ->setFields(new Field\Fields(
                new Field\Text('title')
            ));
        
        $field = Field\Slug::new(name: 'slug')->fromField('title');
        $input = new Input(['slug' => 'Lorem Ipsum', 'title' => 'A Title']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum', 'title' => 'A Title'], $input->all());
    }
    
    public function testProcessBeforeSaveFromFieldIsUsedIfEmptySlugInput()
    {
        $action = Action\Store::new()
            ->setFields(new Field\Fields(
                new Field\Text('title')
            ));
        
        $field = Field\Slug::new(name: 'slug')->fromField('title');
        $input = new Input(['slug' => '', 'title' => 'A Title']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => 'a-title', 'title' => 'A Title'], $input->all());
    }
    
    public function testProcessBeforeSaveFromFieldTranslatableAndNoneTranslatedFromField()
    {
        $action = Action\Store::new()
            ->setFields(new Field\Fields(
                new Field\Text('title')
            ));
        
        $field = Field\Slug::new(name: 'slug')
            ->setLocales(['en' => 'EN', 'de' => 'DE', 'fr' => 'FR'])
            ->fromField('title')
            ->translatable();
        
        $input = new Input(['slug' => ['en' => '', 'de' => '', 'fr' => 'slug-set'], 'title' => 'A Title']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(
            ['slug' => ['en' => 'a-title', 'de' => 'a-title', 'fr' => 'slug-set'], 'title' => 'A Title'],
            $input->all()
        );
    }
    
    public function testProcessBeforeSaveFromFieldTranslatable()
    {
        $action = Action\Store::new()
            ->setFields(new Field\Fields(
                Field\Text::new('title')->translatable()
            ));
        
        $field = Field\Slug::new(name: 'slug')
            ->setLocales(['en' => 'EN', 'de' => 'DE', 'fr' => 'FR'])
            ->fromField('title')
            ->translatable();
        
        $input = new Input([
            'slug' => ['en' => '', 'de' => '', 'fr' => 'slug-set'],
            'title' => ['en' => 'En Title', 'de' => 'De Title', 'fr' => 'Fr Title'],
        ]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(
            [
                'slug' => ['en' => 'en-title', 'de' => 'de-title', 'fr' => 'slug-set'],
                'title' => ['en' => 'En Title', 'de' => 'De Title', 'fr' => 'Fr Title'],
            ],
            $input->all()
        );
    }
    
    public function testProcessBeforeSaveFromFieldTranslatableButNotTranslatedInputFromField()
    {
        $action = Action\Store::new()
            ->setFields(new Field\Fields(
                Field\Text::new('title')->translatable()
            ));
        
        $field = Field\Slug::new(name: 'slug')
            ->setLocales(['en' => 'EN', 'de' => 'DE', 'fr' => 'FR'])
            ->fromField('title')
            ->translatable();
        
        $input = new Input(['slug' => ['en' => '', 'de' => '', 'fr' => 'slug-set'], 'title' => 'A Title']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(
            ['slug' => ['en' => '', 'de' => '', 'fr' => 'slug-set'], 'title' => 'A Title'],
            $input->all()
        );
    }
    
    public function testProcessBeforeSaveFromFieldTranslatableUsesFromFieldStoredIfNoInput()
    {
        $action = Action\Update::new()
            ->setFields(new Field\Fields(
                Field\Text::new('title')
                    ->setEntity(new Entity(['title' => ['de' => 'De Title Stored']]))
                    ->translatable()
            ));
        
        $field = Field\Slug::new(name: 'slug')
            ->setLocales(['en' => 'EN', 'de' => 'DE', 'fr' => 'FR'])
            ->fromField('title')
            ->translatable();
        
        $input = new Input([
            'slug' => ['en' => '', 'de' => '', 'fr' => 'slug-set'],
            'title' => ['en' => 'En Title', 'fr' => 'Fr Title'],
        ]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(
            [
                'slug' => ['en' => 'en-title', 'de' => 'de-title-stored', 'fr' => 'slug-set'],
                'title' => ['en' => 'En Title', 'fr' => 'Fr Title'],
            ],
            $input->all()
        );
    }
    
    public function testProcessStore()
    {
        $action = Action\Store::new();
        $field = Field\Slug::new(name: 'slug');
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        $field->processStore(field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum'], $input->all());
    }
    
    public function testProcessUpdate()
    {
        $action = Action\Update::new();
        $field = Field\Slug::new(name: 'slug');
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        $field->processStore(field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum'], $input->all());
    }
    
    public function testCustomAttributesAreRendered()
    {
        $field = Field\Slug::new(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->attributes(['data-foo' => ['foo'], 'required']);
        
        $field->processCreateEdit(action: Action\Edit::new(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<input data-foo=\'[&quot;foo&quot;]\' required name="name" id="name" type="text" value="Foo">',
            $field->render()
        );
    }
    
    public function testCustomSlugifierUsingString()
    {
        $field = Field\Slug::new(name: 'name')->slugifier('foo');
        
        $slugifier = (new SlugifierFactory())->createSlugifier();
        $slugifiers = new Slugifiers([
            'foo' => $slugifier,
        ]);
        $container = new Container();
        $container->set(SlugifiersInterface::class, $slugifiers);
        $action = Action\Update::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor(container: $container);
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertSame($slugifier, $field->getSlugifier());
    }
    
    public function testCustomSlugifierUsingObject()
    {
        $slugifier = (new SlugifierFactory())->createSlugifier();
        
        $field = Field\Slug::new(name: 'name')->slugifier($slugifier);
        
        $slugifiers = new Slugifiers([]);
        $container = new Container();
        $container->set(SlugifiersInterface::class, $slugifiers);
        $action = Action\Update::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor(container: $container);
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertSame($slugifier, $field->getSlugifier());
    }
    
    public function testCustomSlugifierUsingClosure()
    {
        $field = Field\Slug::new(name: 'name')
            ->slugifier(function (SlugifiersInterface $slugifiers): SlugifierInterface {
                return $slugifiers->get('foo');
            });
        
        $slugifier = (new SlugifierFactory())->createSlugifier();
        $slugifiers = new Slugifiers([
            'foo' => $slugifier,
        ]);
        $container = new Container();
        $container->set(SlugifiersInterface::class, $slugifiers);
        $action = Action\Update::new()->setFields(new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor(container: $container);
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertSame($slugifier, $field->getSlugifier());
    }    
}