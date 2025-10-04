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
 * More is tested on Test\Feature\Field\File::class
 */
class FileTest extends AbstractField
{
    public function getContainer(): ContainerInterface
    {
        $container = new Container();
        $container->set(ViewInterface::class, Factory::createView());
        return $container;
    }
    
    public function testDefaultInterfaceMethods()
    {
        $field = new Field\File(name: 'name');
        $this->assertInstanceof(Field\File::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\File(name: 'name'));
        $this->renderTests(new Field\File(name: 'name'));
        $this->nameTests(Field\File::class);
        $this->groupTests(Field\File::class);
        $this->localeTests(new Field\File(name: 'name'));
        $this->storableTests(new Field\File(name: 'name'));
        $this->indexableTests(new Field\File(name: 'name'));
        $this->creatableTests(new Field\File(name: 'name'));
        $this->editableTests(new Field\File(name: 'name'));
        $this->entityTests(new Field\File(name: 'name'));
        $this->requiredTextTests(Field\File::class, withTranslatable: false);
        $this->optionalTextTests(Field\File::class, withTranslatable: false);
        $this->infoTextTests(Field\File::class);
    }
    
    public function storableTests(FieldInterface $field)
    {
        $this->assertFalse($field->isStorable());
        $this->assertFalse($field->storable(false)->isStorable());
        $this->assertTrue($field->storable()->isStorable());
        $this->assertTrue($field->storable(true)->isStorable());
    }
    
    public function labelTests(string $field)
    {
        $this->assertSame('Name', $field(name: '')->label());
        $this->assertSame('NAME', $field(name: 'name', label: 'NAME')->label());
    }
    
    public function testThrowsSettingReadonlyAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Field\File(name: 'name')->readonly();
    }
    
    public function testThrowsSettingDisabledAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Field\File(name: 'name')->disabled();
    }
}