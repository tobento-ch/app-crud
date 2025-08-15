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

namespace Tobento\App\Crud\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Dir\DirsInterface;

/**
 * Crud
 */
class Crud implements MigrationInterface
{
    protected array $transFiles;
    
    protected array $iconFiles;
    
    /**
     * Create a new Migration.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->transFiles = [
            $this->dirs->get('trans').'en/' => [
                $resources.'trans/en/en-crud.json',
                $resources.'trans/en/validator.crud.json',
            ],
            $this->dirs->get('trans').'de/' => [
                $resources.'trans/de/de-crud.json',
                $resources.'trans/de/validator.crud.json',
            ],
        ];
        
        $this->iconFiles = [
            $this->dirs->get('views').'icons/' => [
                $resources.'views/icons/dots.svg',
                $resources.'views/icons/grip-vertical.svg',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Crud views, translation and other files.';
    }
    
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        return new Actions(
            new FilesCopy(
                files: $this->transFiles,
                type: 'trans',
                description: 'Crud translation files.',
            ),
            new DirCopy(
                dir: $resources.'views/crud/',
                destDir: $this->dirs->get('views').'crud/',
                name: 'Crud views',
                type: 'views',
                description: 'Crud views.',
            ),
            new FilesCopy(
                files: $this->iconFiles,
                type: 'icons',
                description: 'Crud icon files.',
            ),
            new DirCopy(
                dir: $resources.'assets/crud/',
                destDir: $this->dirs->get('public').'assets/crud/',
                name: 'Crud assets',
                type: 'assets',
                description: 'Crud assets.',
            ),
            new DirCopy(
                dir: $this->dirs->get('vendor').'tobento/css-modal/src/',
                destDir: $this->dirs->get('public').'assets/modal/',
                name: 'Css modal assets',
                type: 'assets',
                description: 'Css Modal assets.',
            ),
            new DirCopy(
                dir: $this->dirs->get('vendor').'tobento/js-editor/src/',
                destDir: $this->dirs->get('public').'assets/js-editor/',
                name: 'JS Editor assets',
                type: 'assets',
                description: 'JS Editor assets.',
            ),
            new DirCopy(
                dir: $this->dirs->get('vendor').'tobento/js-notifier/src/',
                destDir: $this->dirs->get('public').'assets/js-notifier/',
                name: 'JS Notifier assets',
                type: 'assets',
                description: 'JS Notifier assets.',
            ),
            new DirCopy(
                dir: $this->dirs->get('vendor').'tobento/js-sortable/src/',
                destDir: $this->dirs->get('public').'assets/js-sortable/',
                name: 'JS Sortable assets',
                type: 'assets',
                description: 'JS Sortable assets.',
            ),
            new DirCopy(
                dir: $this->dirs->get('vendor').'tobento/service-form/resources/',
                destDir: $this->dirs->get('public').'assets/form/',
                name: 'Form validation assets',
                type: 'assets',
                description: 'Form validation assets.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new FilesDelete(
                files: $this->transFiles,
                type: 'trans',
                description: 'Crud translation files.',
            ),
            new DirDelete(
                dir: $this->dirs->get('views').'crud/',
                name: 'Crud views',
                type: 'views',
                description: 'Crud views.',
            ),
            new FilesDelete(
                files: $this->iconFiles,
                type: 'icons',
                description: 'Crud icon files.',
            ),
            new DirDelete(
                dir: $this->dirs->get('public').'assets/crud/',
                name: 'Crud assets',
                type: 'assets',
                description: 'Crud assets.',
            ),
        );
    }
}