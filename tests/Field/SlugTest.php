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
use Tobento\App\Crud\new Field\FieldInterface;
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
        $field = new Field\Slug(name: 'name');
        $this->assertInstanceof(new Field\Slug::class, $field);
        $this->assertInstanceof(new Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Slug(name: 'name'));
        $this->renderTests(new Field\Slug(name: 'name'));
        $this->nameTests(new Field\Slug::class);
        $this->labelTests(new Field\Slug::class);
        $this->groupTests(new Field\Slug::class);
        $this->translatableTests(new Field\Slug(name: 'name'));
        $this->localeTests(new Field\Slug(name: 'name'));
        $this->storableTests(new Field\Slug(name: 'name'));
        $this->indexableTests(new Field\Slug(name: 'name'));
        $this->creatableTests(new Field\Slug(name: 'name'));
        $this->editableTests(new Field\Slug(name: 'name'));
        $this->readonlyTests(new Field\Slug(name: 'name'));
        $this->disabledTests(new Field\Slug(name: 'name'));
        $this->entityTests(new Field\Slug(name: 'name'));
        $this->validateTests(new Field\Slug::class, defaultValidate: 'string');
        $this->requiredTextTests(new Field\Slug::class);
        $this->optionalTextTests(new Field\Slug::class);
        $this->infoTextTests(new Field\Slug::class);
    }
    
    public function testActionProcesses()
    {
        $this->processIndexTests(new Field\Slug::class);
        $this->processStoreTests(new Field\Slug::class);
        $this->processUpdateTests(new Field\Slug::class);
        $this->processShowTests(new Field\Slug::class);
    }
    
    public function testProcessCreateEdit()
    {
        $field = new Field\Slug(name: 'name')->setEntity(new Entity(['name' => 'Foo']));
        $field->processCreateEdit(action: Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name" id="name" type="text" value="Foo">', $field->render());
        
        $field = new Field\Slug(name: 'name')->setEntity(new Entity(['name' => ['en' => 'Foo']]))->translatable();
        $field->processCreateEdit(action: Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString('<input name="name[en]" id="name_en" type="text" value="Foo">', $field->render());
    }
    
    public function testProcessBeforeSaveWithoutSlugInputDoesNothing()
    {
        $action = Action\Store();
        $field = new Field\Slug(name: 'slug');
        $input = new Input([]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame([], $input->all());
    }

    public function testProcessBeforeSaveUsesSlugInput()
    {
        $action = Action\Store();
        $field = new Field\Slug(name: 'slug');
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum'], $input->all());
    }

    public function testProcessBeforeSaveUsesSlugInputTranslatable()
    {
        $action = Action\Store();
        $field = new Field\Slug(name: 'slug')->translatable();
        $input = new Input(['slug' => ['en' => 'Lorem Ipsum']]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => ['en' => 'lorem-ipsum']], $input->all());
    }
    
    public function testProcessBeforeSaveTranslatableAssignesPreviousStoredSlugs()
    {
        $action = Action\Update();
        $field = new Field\Slug(name: 'slug')
            ->setEntity(new Entity(['slug' => ['de' => 'de-slug']]))
            ->setLocales(['en' => 'EN', 'de' => 'DE'])
            ->translatable();
        $input = new Input(['slug' => ['en' => 'Lorem Ipsum']]);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => ['de' => 'de-slug', 'en' => 'lorem-ipsum']], $input->all());
    }
    
    public function testProcessBeforeSaveUsesSlugInputTranslatableWithNoneTranslatedInput()
    {
        $action = Action\Store();
        $field = new Field\Slug(name: 'slug')->translatable();
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => ['en' => 'lorem-ipsum']], $input->all());
    }
    
    public function testProcessBeforeSaveFromFieldIsIgnoredWhenHasSlugInput()
    {
        $action = Action\Store()
            ->setFields(new new Field\Fields(
                new new Field\Text('title')
            ));
        
        $field = new Field\Slug(name: 'slug')->fromField('title');
        $input = new Input(['slug' => 'Lorem Ipsum', 'title' => 'A Title']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum', 'title' => 'A Title'], $input->all());
    }
    
    public function testProcessBeforeSaveFromFieldIsUsedIfEmptySlugInput()
    {
        $action = Action\Store()
            ->setFields(new new Field\Fields(
                new new Field\Text('title')
            ));
        
        $field = new Field\Slug(name: 'slug')->fromField('title');
        $input = new Input(['slug' => '', 'title' => 'A Title']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        
        $this->assertSame(['slug' => 'a-title', 'title' => 'A Title'], $input->all());
    }
    
    public function testProcessBeforeSaveFromFieldTranslatableAndNoneTranslatedFromField()
    {
        $action = Action\Store()
            ->setFields(new new Field\Fields(
                new new Field\Text('title')
            ));
        
        $field = new Field\Slug(name: 'slug')
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
        $action = Action\Store()
            ->setFields(new new Field\Fields(
                new Field\Text('title')->translatable()
            ));
        
        $field = new Field\Slug(name: 'slug')
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
        $action = Action\Store()
            ->setFields(new new Field\Fields(
                new Field\Text('title')->translatable()
            ));
        
        $field = new Field\Slug(name: 'slug')
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
        $action = Action\Update()
            ->setFields(new new Field\Fields(
                new Field\Text('title')
                    ->setEntity(new Entity(['title' => ['de' => 'De Title Stored']]))
                    ->translatable()
            ));
        
        $field = new Field\Slug(name: 'slug')
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
        $action = Action\Store();
        $field = new Field\Slug(name: 'slug');
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        $field->processStore(field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum'], $input->all());
    }
    
    public function testProcessUpdate()
    {
        $action = Action\Update();
        $field = new Field\Slug(name: 'slug');
        $input = new Input(['slug' => 'Lorem Ipsum']);
        $field->processBeforeSave(action: $action, field: $field, input: $input);
        $field->processStore(field: $field, input: $input);
        
        $this->assertSame(['slug' => 'lorem-ipsum'], $input->all());
    }
    
    public function testCustomAttributesAreRendered()
    {
        $field = new Field\Slug(name: 'name')
            ->setEntity(new Entity(['name' => 'Foo']))
            ->attributes(['data-foo' => ['foo'], 'required']);
        
        $field->processCreateEdit(action: Action\Edit(), field: $field, view: Factory::createView());
        $this->assertStringContainsString(
            '<input data-foo=\'[&quot;foo&quot;]\' required name="name" id="name" type="text" value="Foo">',
            $field->render()
        );
    }
    
    public function testCustomSlugifierUsingString()
    {
        $field = new Field\Slug(name: 'name')->slugifier('foo');
        
        $slugifier = (new SlugifierFactory())->createSlugifier();
        $slugifiers = new Slugifiers([
            'foo' => $slugifier,
        ]);
        $container = new Container();
        $container->set(SlugifiersInterface::class, $slugifiers);
        $action = Action\Update()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor(container: $container);
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertSame($slugifier, $field->getSlugifier());
    }
    
    public function testCustomSlugifierUsingObject()
    {
        $slugifier = (new SlugifierFactory())->createSlugifier();
        
        $field = new Field\Slug(name: 'name')->slugifier($slugifier);
        
        $slugifiers = new Slugifiers([]);
        $container = new Container();
        $container->set(SlugifiersInterface::class, $slugifiers);
        $action = Action\Update()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor(container: $container);
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertSame($slugifier, $field->getSlugifier());
    }
    
    public function testCustomSlugifierUsingClosure()
    {
        $field = new Field\Slug(name: 'name')
            ->slugifier(function (SlugifiersInterface $slugifiers): SlugifierInterface {
                return $slugifiers->get('foo');
            });
        
        $slugifier = (new SlugifierFactory())->createSlugifier();
        $slugifiers = new Slugifiers([
            'foo' => $slugifier,
        ]);
        $container = new Container();
        $container->set(SlugifiersInterface::class, $slugifiers);
        $action = Action\Update()->setFields(new new Field\Fields($field));
        $actionProcessor = Factory::createActionProcessor(container: $container);
        $actionProcessor->processFields(action: $action);
        $field = $action->fields()->get(name: $field->name());
        
        $this->assertSame($slugifier, $field->getSlugifier());
    }
}