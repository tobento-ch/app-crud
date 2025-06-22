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

namespace Tobento\App\Crud\Boot;

use Tobento\App\Boot;
use Tobento\App\Http\Boot\ErrorHandler;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\App\Crud\Exception\EntityUndeletableException;
use Tobento\App\Crud\Exception\EntityUnupdatableException;
use Tobento\App\Crud\Exception\ValidationException;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Uri\PreviousUriInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * HttpErrorHandler boot.
 */
class HttpErrorHandler extends ErrorHandler
{
    public const INFO = [
        'boot' => [
            'Crud Error Handler',
        ],
    ];
    
    protected const HANDLER_PRIORITY = 3000;
    
   /**
     * Handle a throwable.
     *
     * @param Throwable $t
     * @return Throwable|ResponseInterface Return throwable if cannot handle, otherwise anything.
     */
    public function handleThrowable(Throwable $t): Throwable|ResponseInterface
    {
        $requester = $this->app->get(RequesterInterface::class);
        $responser = $this->app->get(ResponserInterface::class);
        
        if ($t instanceof EntityNotFoundException) {
            if ($requester->isReading()) {
                return $this->renderView(code: 404);
            }
            
            $responser->messages()->add(
                level: 'error',
                message: 'Record with the ID :id not found.',
                parameters: [':id' => $t->id()],
            );
            
            if ($requester->wantsJson()) {
                return $responser->json([
                    'status' => 404,
                    'messages' => $responser->messages()->toArray(),
                ]);
            }

            return $responser
                ->redirect(uri: $this->app->get(PreviousUriInterface::class));
        }
        
        if ($t instanceof EntityUndeletableException) {
            if ($requester->isReading()) {
                return $this->renderView(code: 403);
            }
            
            $responser->messages()->add(
                level: 'error',
                message: $t->getMessage() ? $t->getMessage() : 'Record with the ID :id is undeletable.',
                parameters: [':id' => $t->id()],
            );
            
            if ($requester->wantsJson()) {
                return $responser->json([
                    'status' => 403,
                    'messages' => $responser->messages()->toArray(),
                ]);
            }

            return $responser
                ->redirect(uri: $this->app->get(PreviousUriInterface::class));
        }
        
        if ($t instanceof EntityUnupdatableException) {
            if ($requester->isReading()) {
                return $this->renderView(code: 403);
            }            
            
            $responser->messages()->add(
                level: 'error',
                message: $t->getMessage() ? $t->getMessage() : 'Record with the ID :id is unupdatable.',
                parameters: [':id' => $t->id()],
            );
            
            if ($requester->wantsJson()) {
                return $responser->json([
                    'status' => 403,
                    'messages' => $responser->messages()->toArray(),
                ]);
            }

            return $responser
                ->redirect(uri: $this->app->get(PreviousUriInterface::class));
        }
        
        if ($t instanceof ActionNotFoundException) {
            if ($requester->isReading()) {
                return $this->renderView(code: 404);
            }
            
            $responser->messages()->add(
                level: 'error',
                message: 'Action :name not found.',
                parameters: [':name' => $t->actionName()],
            );
            
            if ($requester->wantsJson()) {
                return $responser->json([
                    'status' => 404,
                    'messages' => $responser->messages()->toArray(),
                ]);
            }

            return $responser
                ->redirect(uri: $this->app->get(PreviousUriInterface::class));
        }
        
        if ($t instanceof ValidationException) {
            if ($requester->wantsJson()) {
                return $responser->json([
                    'status' => 422,
                    'messages' => $t->validation()->errors()->toArray(),
                ]);
            }
            
            $uri = $this->app->get(PreviousUriInterface::class);

            if ($t->redirectActionName()) {
                $redirectAction = $t->action()->actions()->get(name: $t->redirectActionName());
                
                if ($redirectAction) {
                    $redirectAction->setController($t->action()->controller());
                    $redirectAction->setActions($t->action()->actions());
                    $this->app->get(ActionProcessorInterface::class)->resolveActionUrls(action: $redirectAction);
                    $uri = $redirectAction->getUrl();                    
                }
            }
            
            $responser->messages()->add('error', 'You have some errors check out the fields for its error message.');
            $responser->messages()->push($t->validation()->errors());

            $response = $responser
                ->withInput($t->action()->getInput()->all())
                ->redirect(uri: $uri);
            
            return $response;
        }

        return $t;
    }
}