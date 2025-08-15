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
 
namespace Tobento\App\Crud;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;

interface ResourceTypeInterface
{
    /**
     * Returns the type name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string;
    
    /**
     * Configure fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface;
    
    /**
     * Configure actions.
     *
     * @param ActionsInterface $actions
     * @return void
     */
    public function configureActions(ActionsInterface $actions): void;
}