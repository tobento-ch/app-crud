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
        $field = Field\Files::new(name: 'name');
        $this->assertInstanceof(Field\Files::class, $field);
        $this->assertInstanceof(Field\FieldInterface::class, $field);
        
        $this->processTests(Field\Files::new(name: 'name'));
        $this->renderTests(Field\Files::new(name: 'name'));
        $this->nameTests(Field\Files::class);
        $this->groupTests(Field\Files::class);
        $this->localeTests(Field\Files::new(name: 'name'));
        $this->storableTests(Field\Files::new(name: 'name'));
        $this->indexableTests(Field\Files::new(name: 'name'));
        $this->creatableTests(Field\Files::new(name: 'name'));
        $this->editableTests(Field\Files::new(name: 'name'));
        $this->entityTests(Field\Files::new(name: 'name'));
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
        
        Field\Files::new(name: 'name')->readonly();
    }
    
    public function testThrowsSettingDisabledAsUnsupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Field\Files::new(name: 'name')->disabled();
    }
}