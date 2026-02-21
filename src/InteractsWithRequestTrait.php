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
use Tobento\App\Crud\Field\FieldsAwareInterface;
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
        $isLive = $this->isLiveRequest($requester);

        // LIVE REQUEST
        if ($isLive) {
            $changed = $requester->input()->get('_changed');
            
            // If _changed is missing, do NOT update anything
            if (!$changed) {
                return $fields->filter(fn() => false);
            }

            // Only update the changed field
            return $fields->filter(function(FieldInterface $f) use ($changed) {
                return $f->name() === $changed;
            });
        }

        // Fallback: original behavior for normal AJAX
        $inputKeys = $requester->input()->keys()->all();
        $inputKeys = array_merge($inputKeys, array_keys($requester->request()->getUploadedFiles()));

        return $fields->filter(function(FieldInterface $f) use ($inputKeys) {

            $name = $f->name();
            $root = explode('.', $name)[0];

            // 1. Keep parent fields only if they exist in the request
            if ($f instanceof FieldsAwareInterface) {
                return in_array($root, $inputKeys, true);
            }

            // 2. Keep children only if their parent exists in the request
            if (in_array($root, $inputKeys, true)) {
                return true;
            }

            // 3. Keep direct fields that exist in the request
            return in_array($name, $inputKeys, true);
        });
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