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

use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\ParentFieldsAwareInterface;
use Tobento\Service\Requester\RequesterInterface;

trait InteractsWithRequestTrait
{
    /**
     * Returns only the fields existing from the request.
     *
     * @param RequesterInterface $requester
     * @param FieldsInterface $fields
     * @return FieldsInterface
     */
    public function filterRequestedFieldsOnly(RequesterInterface $requester, FieldsInterface $fields): FieldsInterface
    {
        $inputKeys = $requester->input()->keys()->all();
        $inputKeys = array_merge($inputKeys, array_keys($requester->request()->getUploadedFiles()));
        
        return $fields->filter(
            fn (FieldInterface $f): bool
            => $f instanceof ParentFieldsAwareInterface || in_array(explode('.', $f->name())[0], $inputKeys)
        );
    }
    
    /**
     * Returns the whether it is a live request or not.
     *
     * @param RequesterInterface $requester
     * @return bool
     */
    public function isLiveRequest(RequesterInterface $requester): bool
    {
        if (!$requester->isAjax()) {
            return false;
        }
        
        return $requester->request()->hasHeader('X-Crud-Live');
    }
}