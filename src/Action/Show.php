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

namespace Tobento\App\Crud\Action;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Button\ButtonsInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;

final class Show extends AbstractAction
{
    /**
     * Create a new Show.
     *
     * @param null|string|Closure $title
     */
    public function __construct(
        null|string|Closure $title = null,
    ) {
        $this->title = $title;
        $this->route('{name}.show', function(EntityInterface $entity): array {
            return ['id' => $entity->id()];
        });
        
        $this->view('crud/show');
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'show';
    }
    
    /**
     * Returns the handler processing the action.
     *
     * @return callable(mixed...): \Psr\Http\Message\ResponseInterface
     */
    public function getHandler(): callable
    {
        return [$this, 'handle'];
    }    
    
    /**
     * Handle action.
     *
     * @param int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function handle(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        $controller = $this->controller();

        $actionProcessor->preprocessAction(action: $this);
        
        // Handle entity:
        $entity = $controller->repository()->findById($id);
        
        if ($entity === null) {
            throw new EntityNotFoundException($id, $this);
        }
        
        $this->setEntity($controller->createEntityFromObject($entity));

        // Show json:
        if ($requester->input()->get('type') === 'json') {
            $controller->isActionProcessable($this);
            return $responser->json(data: $this->entity()->toArray());
        }
        
        // Set the configured fields if none specified:
        if ($this->fields()->empty()) {
            $this->setFields($controller->getConfiguredFields(action: $this));
        }
        
        $this->setFields($this->fields()->showable());
        
        // Process action:
        $controller->isActionProcessable($this);
        $actionProcessor->processAction(action: $this);
        
        return $responser->render(
            view: $this->getView(),
            data: [
                'action' => $this->setFields($this->fields()->parent(null)),
            ],
        );
    }
    
    /**
     * Returns the buttons.
     *
     * @return ButtonsInterface
     */
    public function buttons(): ButtonsInterface
    {
        if ($this->buttons instanceof ButtonsInterface) {
            return $this->buttons;
        }
        
        $this->buttons = new Buttons(
            new Button\Link(label: $this->trans('Back to index'), group: 'entity')
                ->name('back')
                ->linkToAction('index'),
        );
        
        return $this->applyButtonsConfig($this->buttons);
    }
}