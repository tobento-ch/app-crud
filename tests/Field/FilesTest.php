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

use Psr\Container\ContainerInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Test\Factory;
use Tobento\Service\Container\Container;
use Tobento\Service\View\ViewInterface;

/**
 * More is tested on Test\Feature\Field\Files::class
 */
class FilesTest extends AbstractField
{
    public function getContainer(): ContainerInterface
    {
        $container = new Container();
        $container->set(ViewInterface::class, Factory::createView());
        return $container;
    }
    
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\Files(name: 'name');
        $this->assertInstanceof(Field\Files::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\Files(name: 'name'));
        $this->renderTests(new Field\Files(name: 'name'));
        $this->nameTests(Field\Files::class);
        $this->groupTests(Field\Files::class);
        $this->localeTests(new Field\Files(name: 'name'));
        $this->storableTests(new Field\Files(name: 'name'));
        $this->indexableTests(new Field\Files(name: 'name'));
        $this->creatableTests(new Field\Files(name: 'name'));
        $this->editableTests(new Field\Files(name: 'name'));
        $this->entityTests(new Field\Files(name: 'name'));
        $this->requiredTextTests(Field\Files::class, withTranslatable: false);
        $this->optionalTextTests(Field\Files::class, withTranslatable: false);
        $this->infoTextTests(Field\Files::class);
    }
    
    public function storableTests(FieldInterface $field)
    {
        $this->assertFalse($field->isStorable());
        $this->assertFalse($field->storable(false)->isStorable());
        $this->assertTrue($field->storable()->isStorable());
        $this->assertTrue($field->storable(true)->isStorable());
    }
    
    public function testThrowsSettingReadonlyAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Field\Files(name: 'name')->readonly();
    }
    
    public function testThrowsSettingDisabledAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Field\Files(name: 'name')->disabled();
    }
    
    public function testGetRawFieldsMethodReturnsDeclaredFields()
    {
        $field = new Field\Files(name: 'gallery');

        // Declare custom fields
        $field->fields(
            new Field\Text('src'),
            new Field\Text('title'),
        );

        $raw = $field->getRawFields();

        // Should contain the two declared fields + the internal "order" field
        $this->assertSame(3, count($raw->getNames()));

        // Check names
        $names = $raw->getNames();
        $this->assertContains('src', $names);
        $this->assertContains('title', $names);
        $this->assertContains('order', $names);

        // Ensure "order" is the hidden internal field
        $orderField = $raw->get('order');
        $this->assertInstanceOf(Field\Text::class, $orderField);
        $this->assertSame('order', $orderField->name());
    }
}