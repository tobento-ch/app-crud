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

namespace Tobento\App\Crud\Action\Traits;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Requester\RequesterInterface;

trait InteractsWithRequest
{
    /**
     * Fetch input for actions that need it.
     *
     * @param RequesterInterface $requester
     * @param ActionInterface $action
     * @param bool $fresh
     * @return InputInterface
     */
    protected function fetchInput(
        RequesterInterface $requester,
        ActionInterface $action,
        bool $fresh = false,
    ): InputInterface {
        // Always fetch if fresh input is explicitly requested
        if ($fresh) {
            return new Input(
                array_replace_recursive(
                    $requester->input()->all(),
                    $requester->request()->getUploadedFiles()
                )
            );
        }

        // If action already has input do NOT override
        if (!empty($action->getInput()->all())) {
            return $action->getInput();
        }

        // Otherwise fetch fresh input
        return new Input(
            array_replace_recursive(
                $requester->input()->all(),
                $requester->request()->getUploadedFiles()
            )
        );
    }
}