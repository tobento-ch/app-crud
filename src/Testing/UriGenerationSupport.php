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

namespace Tobento\App\Crud\Testing;

trait UriGenerationSupport
{
    /**
     * Returns the crud controller resource name.
     *
     * @return string
     */
    protected function getCrudControllerResourceName(): string
    {
        return $this->getCrudController()::RESOURCE_NAME;
    }
    
    /**
     * Returns the generated index uri for the crud index action.
     *
     * @param null|string $locale
     * @return string
     */
    protected function generateIndexUri(null|string $locale = null): string
    {
        $name = $this->getCrudControllerResourceName();
        
        if (!empty($locale)) {
            return sprintf('%s/%s', $locale, $name);
        }

        return $name;
    }
    
    /**
     * Returns the generated bulk uri for the crud bulk action.
     *
     * @param string $action The bulk action name.
     * @param null|string $locale
     * @return string
     */
    protected function generateBulkUri(string $action, null|string $locale = null): string
    {
        $name = $this->getCrudControllerResourceName();
        
        if (!empty($locale)) {
            return sprintf('%s/%s/%s/%s', $locale, $name, 'bulk', $action);
        }

        return sprintf('%s/%s/%s', $name, 'bulk', $action);
    }

    /**
     * Returns the generated create uri for the crud create action.
     *
     * @param null|string $locale
     * @return string
     */
    protected function generateCreateUri(null|string $locale = null): string
    {
        $name = $this->getCrudControllerResourceName();
        
        if (!empty($locale)) {
            return sprintf('%s/%s/create', $locale, $name);
        }

        return sprintf('%s/create', $name);
    }
    
    /**
     * Returns the generated store uri for the crud store action.
     *
     * @param null|string $locale
     * @return string
     */
    protected function generateStoreUri(null|string $locale = null): string
    {
        return $this->generateIndexUri($locale);
    }
    
    /**
     * Returns the generated edit uri for the crud edit action.
     *
     * @param string|int $id
     * @param null|string $locale
     * @return string
     */
    protected function generateEditUri(string|int $id, null|string $locale = null): string
    {
        $name = $this->getCrudControllerResourceName();
        
        if (!empty($locale)) {
            return sprintf('%s/%s/%s/edit', $locale, $name, (string)$id);
        }

        return sprintf('%s/%s/edit', $name, (string)$id);
    }
    
    /**
     * Returns the generated update uri for the crud update action.
     *
     * @param string|int $id
     * @param null|string $locale
     * @return string
     */
    protected function generateUpdateUri(string|int $id, null|string $locale = null): string
    {
        $name = $this->getCrudControllerResourceName();
        
        if (!empty($locale)) {
            return sprintf('%s/%s/%s', $locale, $name, (string)$id);
        }

        return sprintf('%s/%s', $name, (string)$id);
    }
    
    /**
     * Returns the generated copy uri for the crud copy action.
     *
     * @param string|int $id
     * @param null|string $locale
     * @return string
     */
    protected function generateCopyUri(string|int $id, null|string $locale = null): string
    {
        $name = $this->getCrudControllerResourceName();
        
        if (!empty($locale)) {
            return sprintf('%s/%s/%s/copy', $locale, $name, (string)$id);
        }

        return sprintf('%s/%s/copy', $name, (string)$id);
    }
    
    /**
     * Returns the generated delete uri for the crud delete action.
     *
     * @param string|int $id
     * @param null|string $locale
     * @return string
     */
    protected function generateDeleteUri(string|int $id, null|string $locale = null): string
    {
        return $this->generateUpdateUri($id, $locale);
    }
    
    /**
     * Returns the generated show uri for the crud show action.
     *
     * @param string|int $id
     * @param null|string $locale
     * @return string
     */
    protected function generateShowUri(string|int $id, null|string $locale = null): string
    {
        return $this->generateUpdateUri($id, $locale);
    }
}