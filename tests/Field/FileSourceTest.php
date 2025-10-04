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
        $field = new Field\FileSource(name: 'name');
        $this->assertInstanceof(Field\FileSource::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(new Field\FileSource(name: 'name'));
        $this->renderTests(new Field\FileSource(name: 'name'));
        $this->nameTests(Field\FileSource::class);
        $this->groupTests(Field\FileSource::class);
        $this->localeTests(new Field\FileSource(name: 'name'));
        $this->storableTests(new Field\FileSource(name: 'name'));
        $this->indexableTests(new Field\FileSource(name: 'name'));
        $this->creatableTests(new Field\FileSource(name: 'name'));
        $this->editableTests(new Field\FileSource(name: 'name'));
        $this->entityTests(new Field\FileSource(name: 'name'));
        $this->requiredTextTests(Field\FileSource::class, withTranslatable: false);
        $this->optionalTextTests(Field\FileSource::class, withTranslatable: false);
        $this->infoTextTests(Field\FileSource::class);
    }
    
    public function labelTests(string $field)
    {
        $this->assertSame('Name', $field(name: '')->label());
        $this->assertSame('NAME', $field(name: 'name', label: 'NAME')->label());
    }
    
    public function testThrowsSettingReadonlyAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Field\FileSource(name: 'name')->readonly();
    }
    
    public function testThrowsSettingDisabledAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Field\FileSource(name: 'name')->disabled();
    }
}