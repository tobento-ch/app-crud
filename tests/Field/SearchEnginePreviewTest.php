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

use Nyholm\Psr7\Factory\Psr17Factory;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Container\Container;
use Tobento\Service\Form\Form;
use Tobento\Service\Form\Input as FormInput;
use Tobento\Service\Uri\BaseUri;
use Tobento\Service\Uri\BaseUriInterface;
use Tobento\Service\View\ViewInterface;

class SearchEnginePreviewTest extends AbstractField
{
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\SearchEnginePreview(name: 'preview');
        $this->assertInstanceof(Field\SearchEnginePreview::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\SearchEnginePreview(name: 'preview'));
        $this->renderTests(new Field\SearchEnginePreview(name: 'preview'));
        $this->nameTests(Field\SearchEnginePreview::class);
        $this->labelTests(Field\SearchEnginePreview::class);
        $this->groupTests(Field\SearchEnginePreview::class);
        $this->localeTests(new Field\SearchEnginePreview(name: 'preview'));
        $this->assertFalse(new Field\SearchEnginePreview(name: 'preview')->isStorable());
        $this->assertFalse(new Field\SearchEnginePreview(name: 'preview')->isIndexable());
        $this->assertFalse(new Field\SearchEnginePreview(name: 'preview')->isShowable());
        $this->creatableTests(new Field\SearchEnginePreview(name: 'preview'));
        $this->editableTests(new Field\SearchEnginePreview(name: 'preview'));
        $this->entityTests(new Field\SearchEnginePreview(name: 'preview'));
        $this->infoTextTests(Field\SearchEnginePreview::class);
    }
    
    public function testDefaults()
    {
        $field = new Field\SearchEnginePreview(name: 'preview');
        
        $this->assertSame([], $field->getTitleFrom());
        $this->assertSame([], $field->getSlugFrom());
        $this->assertSame([], $field->getDescriptionFrom());
        $this->assertSame('{slug}', $field->getUri());
    }
    
    public function testFromMethods()
    {
        $field = new Field\SearchEnginePreview(name: 'preview')
            ->titleFrom('meta_title', 'title')
            ->slugFrom('slug')
            ->descriptionFrom('meta_description', 'intro')
            ->uri('blog/{slug}');
        
        $this->assertSame(['meta_title', 'title'], $field->getTitleFrom());
        $this->assertSame(['slug'], $field->getSlugFrom());
        $this->assertSame(['meta_description', 'intro'], $field->getDescriptionFrom());
        $this->assertSame('blog/{slug}', $field->getUri());
    }
    
    public function testProcessCreateEdit()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->titleFrom('meta_title', 'title')
                ->slugFrom('slug')
                ->descriptionFrom('meta_description')
                ->uri('blog/{slug}'),
            entity: [
                'title' => 'Title',
                'meta_title' => 'Meta Title',
                'slug' => 'lorem',
                'meta_description' => 'Description',
            ],
        );
        
        $this->assertStringContainsString('data-field="preview"', $rendered);
        $this->assertStringContainsString('<div class="text-700" data-search-engine-preview-title>Meta Title</div>', $rendered);
        $this->assertStringContainsString('<div class="text-success mt-xxs" data-search-engine-preview-url>https://example.com/blog/lorem</div>', $rendered);
        $this->assertStringContainsString('<div class="mt-xxs" data-search-engine-preview-description>Description</div>', $rendered);
        $this->assertStringNotContainsString('data-search-engine-preview-locale', $rendered);
    }
    
    public function testProcessCreateEditUsesFallbackIfEmpty()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->titleFrom('meta_title', 'title')
                ->slugFrom('slug'),
            entity: ['title' => 'Title', 'meta_title' => '', 'slug' => 'lorem'],
        );
        
        $this->assertStringContainsString('<div class="text-700" data-search-engine-preview-title>Title</div>', $rendered);
        $this->assertStringContainsString('<div class="text-success mt-xxs" data-search-engine-preview-url>https://example.com/lorem</div>', $rendered);
    }
    
    public function testProcessCreateEditWithAbsoluteUri()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->slugFrom('slug')
                ->uri('https://shop.example.com/{slug}'),
            entity: ['slug' => 'lorem'],
        );
        
        $this->assertStringContainsString('data-search-engine-preview-url>https://shop.example.com/lorem</div>', $rendered);
    }
    
    public function testProcessCreateEditWithBaseUriPath()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->slugFrom('slug')
                ->uri('/blog/{slug}'),
            entity: ['slug' => 'lorem'],
            baseUri: 'https://example.com/app/',
        );
        
        $this->assertStringContainsString('data-search-engine-preview-url>https://example.com/app/blog/lorem</div>', $rendered);
    }
    
    public function testProcessCreateEditStripsAndEscapesHtml()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->titleFrom('title')
                ->descriptionFrom('desc'),
            entity: ['title' => '<b>Tom & Jerry</b>', 'desc' => '<p>Tom &amp; Jerry</p>'],
        );
        
        $this->assertStringContainsString('data-search-engine-preview-title>Tom &amp; Jerry</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-description>Tom &amp; Jerry</div>', $rendered);
    }
    
    public function testProcessCreateEditWithTranslatableFields()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->titleFrom('meta_title', 'title')
                ->slugFrom('slug')
                ->uri('{locale}/blog/{slug}'),
            entity: [
                'title' => ['en' => 'Title', 'de' => 'Titel'],
                'meta_title' => ['en' => '', 'de' => ''],
                'slug' => ['en' => 'lorem', 'de' => 'lorem-de'],
            ],
            translatable: true,
        );
        
        // the first locale is shown:
        $this->assertStringContainsString('<div class="text-xxs mb-xxs" data-search-engine-preview-locale>English</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-title>Title</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-url>https://example.com/en/blog/lorem</div>', $rendered);
        $this->assertStringNotContainsString('data-search-engine-preview-locale>Deutsch</div>', $rendered);
        
        // the config for the javascript:
        $this->assertStringContainsString('&quot;title&quot;:[&quot;meta_title.{locale}&quot;,&quot;title.{locale}&quot;]', $rendered);
        $this->assertStringContainsString('&quot;locales&quot;:{&quot;en&quot;:&quot;English&quot;,&quot;de&quot;:&quot;Deutsch&quot;}', $rendered);
    }
    
    public function testProcessCreateEditMergesAttributes()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')->slugFrom('slug')->attributes(['class' => 'mb-m']),
            entity: ['slug' => 'lorem'],
        );
        
        $this->assertStringContainsString('<div class="mb-m" data-search-engine-preview=\'{', $rendered);
    }
    
    public function testProcessCreateEditUsesInputFirst()
    {
        // e.g. on a live update:
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')->titleFrom('title')->slugFrom('slug'),
            entity: ['title' => 'Title', 'slug' => 'lorem'],
            input: ['title' => 'Input Title'],
        );
        
        $this->assertStringContainsString('data-search-engine-preview-title>Input Title</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-url>https://example.com/lorem</div>', $rendered);
    }
    
    public function testProcessCreateEditUsesOldInputFirst()
    {
        // e.g. after a validation error:
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')->titleFrom('title')->slugFrom('slug'),
            entity: [],
            oldInput: ['title' => 'Old Title', 'slug' => 'old'],
        );
        
        $this->assertStringContainsString('data-search-engine-preview-title>Old Title</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-url>https://example.com/old</div>', $rendered);
    }
    
    public function testProcessShow()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview', label: 'Preview')
                ->titleFrom('title')
                ->slugFrom('slug')
                ->showable(),
            entity: ['title' => 'Title', 'slug' => 'lorem'],
            action: new Action\Show(),
        );
        
        $this->assertStringContainsString('<div class="field-label text-700">Preview</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-title>Title</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-url>https://example.com/lorem</div>', $rendered);
        
        // no javascript config, as there are no inputs to update from:
        $this->assertStringNotContainsString('data-search-engine-preview=', $rendered);
    }
    
    public function testProcessShowWithTranslatableFieldsRendersEachLocale()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->titleFrom('title')
                ->slugFrom('slug')
                ->uri('{locale}/{slug}')
                ->showable(),
            entity: [
                'title' => ['en' => 'Title', 'de' => 'Titel'],
                'slug' => ['en' => 'lorem', 'de' => 'lorem-de'],
            ],
            translatable: true,
            action: new Action\Show(),
        );
        
        $this->assertStringContainsString('data-search-engine-preview-locale>English</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-title>Title</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-url>https://example.com/en/lorem</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-locale>Deutsch</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-title>Titel</div>', $rendered);
        $this->assertStringContainsString('data-search-engine-preview-url>https://example.com/de/lorem-de</div>', $rendered);
    }
    
    public function testProcessIndex()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->titleFrom('meta_title', 'title')
                ->slugFrom('slug')
                ->descriptionFrom('desc')
                ->uri('blog/{slug}')
                ->indexable(),
            entity: [
                'title' => '<b>Tom & Jerry</b>',
                'meta_title' => '',
                'slug' => 'lorem',
                'desc' => str_repeat('a', 120),
            ],
            action: new Action\Index(),
        );
        
        $this->assertSame(
            '<div class="text-700">Tom &amp; Jerry</div>'
            .'<div class="text-success mt-xxs">https://example.com/blog/lorem</div>'
            .'<div class="mt-xxs">'.str_repeat('a', 97).'...</div>',
            $rendered
        );
    }
    
    public function testProcessIndexWithTranslatableFields()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')
                ->titleFrom('title')
                ->slugFrom('slug')
                ->uri('{locale}/{slug}')
                ->indexable(),
            entity: [
                'title' => ['en' => 'Title', 'de' => 'Titel'],
                'slug' => ['en' => 'lorem', 'de' => 'lorem-de'],
            ],
            translatable: true,
            action: new Action\Index(),
        );
        
        $this->assertStringContainsString('<div class="text-700">Title</div>', $rendered);
        $this->assertStringContainsString('https://example.com/en/lorem', $rendered);
    }
    
    public function testProcessCreateEditWithHidden()
    {
        $rendered = $this->renderPreview(
            preview: new Field\SearchEnginePreview(name: 'preview')->slugFrom('slug')->hidden(),
            entity: ['slug' => 'lorem'],
        );
        
        $this->assertSame('', $rendered);
    }
    
    private function renderPreview(
        Field\SearchEnginePreview $preview,
        array $entity,
        string $baseUri = 'https://example.com',
        bool $translatable = false,
        array $input = [],
        array $oldInput = [],
        null|Action\ActionInterface $action = null,
    ): string {
        $fields = [$preview];
        
        foreach (['title', 'meta_title', 'slug', 'desc', 'meta_description'] as $name) {
            $fields[] = new Field\Text($name)->translatable($translatable);
        }
        
        $action = ($action ?? new Action\Edit())
            ->setFields(new Field\Fields(...$fields))
            ->setEntity(new Entity($entity));
        
        if ($translatable) {
            $action->locales(['en' => 'English', 'de' => 'Deutsch']);
        }
        
        if (!empty($input)) {
            $action->setInput(new Input($input));
        }
        
        $container = new Container();
        $container->set(BaseUriInterface::class, new BaseUri(new Psr17Factory()->createUri($baseUri)));
        
        $actionProcessor = Factory::createActionProcessor(container: $container);
        
        if (!empty($oldInput)) {
            $container->get(ViewInterface::class)->addMacro('form', function() use ($oldInput) {
                return new Form(input: new FormInput($oldInput));
            });
        }
        
        $actionProcessor->processFields(action: $action);
        
        return $action->fields()->get(name: 'preview')->render();
    }
}