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
 * More is tested on Test\Feature\Field\FileSource::class
 */
class FileSourceTest extends AbstractField
{
    public function getContainer(): ContainerInterface
    {
        $container = new Container();
        $container->set(ViewInterface::class, Factory::createView());
        return $container;
    }
    
    public function testDefaultInterfaceMethods()
    {
        $field = Field\FileSource::new(name: 'name');
        $this->assertInstanceof(Field\FileSource::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\FileSource::new(name: 'name'));
        $this->renderTests(Field\FileSource::new(name: 'name'));
        $this->nameTests(Field\FileSource::class);
        $this->groupTests(Field\FileSource::class);
        $this->localeTests(Field\FileSource::new(name: 'name'));
        $this->storableTests(Field\FileSource::new(name: 'name'));
        $this->indexableTests(Field\FileSource::new(name: 'name'));
        $this->creatableTests(Field\FileSource::new(name: 'name'));
        $this->editableTests(Field\FileSource::new(name: 'name'));
        $this->entityTests(Field\FileSource::new(name: 'name'));
        $this->requiredTextTests(Field\FileSource::class, withTranslatable: false);
        $this->optionalTextTests(Field\FileSource::class, withTranslatable: false);
        $this->infoTextTests(Field\FileSource::class);
    }
    
    public function labelTests(string $field)
    {
        $this->assertSame('Name', $field::new(name: '')->label());
        $this->assertSame('NAME', $field::new(name: 'name', label: 'NAME')->label());
    }
    
    public function testThrowsSettingReadonlyAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Field\FileSource::new(name: 'name')->readonly();
    }
    
    public function testThrowsSettingDisabledAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Field\FileSource::new(name: 'name')->disabled();
    }
}