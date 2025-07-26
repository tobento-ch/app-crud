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

use Psr\Container\ContainerInterface;
use Tobento\Service\Autowire\Autowire;
use Tobento\App\Crud\Url\UrlResolverInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\Actions;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\Fields;
use Tobento\App\Crud\Field\FieldsAwareInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Button\Buttons;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Language\LanguageInterface;

/**
 * ActionProcessor
 */
class ActionProcessor implements ActionProcessorInterface
{
    /**
     * @var Autowire
     */
    protected Autowire $autowire;
    
    /**
     * Create a new ActionProcessor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container,
        protected UrlResolverInterface $urlResolver,
        protected null|TranslatorInterface $translator,
        protected null|LanguagesInterface $languages,
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Preprocess action.
     *
     * @param ActionInterface $action
     * @return void
     * @throws ActionProcessException
     */
    public function preprocessAction(ActionInterface $action): void
    {
        if ($this->translator) {
            $action->setTranslator($this->translator);
        }
        
        if ($this->languages) {
            $languages = $this->languages->sort(
                fn(LanguageInterface $a, LanguageInterface $b): int => $b->default() <=> $a->default()
            );
            
            $action->locales($languages->column('name', 'key'));
        }
    }
    
    /**
     * Process action.
     *
     * @param ActionInterface $action
     * @return void
     * @throws ActionProcessException
     */
    public function processAction(ActionInterface $action): void
    {
        // Sets actions:
        if ($action->actions()->empty()) {
            $action->setActions(new Actions($action));
        }
        
        // Sets controller:
        foreach($action->actions() as $a) {
            $a->setController($action->controller());
            $a->locales($action->getLocales());
        }
        
        // Resolving actions url after setting controller:
        foreach($action->actions() as $a) {
            $this->resolveActionUrls($a);
        }
        
        // Resolve buttons url:
        $buttons = $this->urlResolver->resolveButtonsUrl(
            buttons: $action->buttons(),
            action: $action
        );
        
        $action->setButtons($buttons);
        
        // Process fields:
        $this->processFields(action: $action);

        // Process entities:
        foreach($action->entities() as $entity) {
            $action->setEntity($entity);
            
            $buttons = $action->applyButtonsConfig($action->buttons()->group('entity'));
            
            // Resolving buttons url:
            $buttons = $this->urlResolver->resolveButtonsUrl(
                buttons: $buttons,
                action: $action,
                entity: $entity
            );
            
            $entity->setButtons($buttons);
            
            // Process fields:
            $this->processFields(action: $action, entity: $entity);
        }
    }

    /**
     * Process fields.
     *
     * @param ActionInterface $action
     * @param null|EntityInterface $entity
     * @return void
     */
    public function processFields(
        ActionInterface $action,
        null|EntityInterface $entity = null,
    ): void {
        // Handle subfields:
        /*$fields = [];
        foreach($action->fields() as $field) {
            if ($field instanceof FieldsAwareInterface) {
                foreach($field->getFields($action) as $f) {
                    $fields[] = $f;
                }
            }
            $fields[] = $field;
        }*/
        
        $fields = $this->collectFields($action->fields(), $action);
        
        //echo '<pre>'; print_r((new Fields(...$fields))->getNames()); exit;

        $action->setFields(new Fields(...$fields));
        
        // Fields:
        $entity = $entity ?: $action->entity();
        $fields = clone $action->fields();
                
        foreach($fields as $field) {
            // Set locales:
            $field->setLocale($action->getLocale());
            $field->setLocales($action->getLocales());
            $field->setEntity($entity);
        }
        
        $action->setFields($fields);
        $entity->setFields($fields);
        
        // Process resolve first:
        foreach($fields as $field) {
            foreach($field->getResolve() as $resolve) {
                if ($resolve->supportsAction($action->name())) {
                    $resolve->resolved(
                        field: $field,
                        value: $this->call($resolve->callable(), ['action' => $action, 'field' => $field])
                    );
                }
            }
        }
        
        // Call fields actions:
        foreach($action->getFieldsActions() as $fieldsAction) {
            $this->call($fieldsAction, ['action' => $action]);
        }
        
        // Call each field action:
        $this->processFieldsAction(action: $action);
    }
    
    /**
     * Process fields action.
     *
     * @param ActionInterface $action
     * @param null|string $actionName
     * @param null|EntityInterface $entity
     * @return void
     * @throws ActionProcessException
     */
    public function processFieldsAction(
        ActionInterface $action,
        null|string $actionName = null,
        null|EntityInterface $entity = null,
    ): void {
        $actionName = $actionName ?: $action->name();

        foreach($action->fields() as $field) {
            
            if ($entity) {
                $field->setOldEntity($field->entity());
                $field->setEntity($entity);
            }
            
            $callable = $field->getProcessor(action: $actionName);
                        
            if (is_callable($callable)) {
                $this->call($callable, [
                    'action' => $action,
                    'field' => $field,
                    'input' => $action->getInput(),
                ]);
            }
        }
    }
    
    /**
     * Resolves the action urls.
     *
     * @param ActionInterface $action
     * @return void
     */
    public function resolveActionUrls(ActionInterface $action): void
    {
        $url = $this->urlResolver->resolveActionUrl($action);
        $action->setUrl($url);
        
        $linkUrl = $this->urlResolver->resolveActionLinkToUrl($action);
        $action->setLinkUrl($linkUrl);
    }
    
    /**
     * Resolve callable and calls it.
     *
     * @param mixed $callable
     * @param array<int|string, mixed> $parameters
     * @return mixed The result of the callable.
     */
    public function call(mixed $callable, array $parameters = []): mixed
    {
        return $this->autowire->call($callable, $parameters);
    }
    
    /**
     * Collects fields with sub fields.
     *
     * @param FieldsInterface $fields
     * @param ActionInterface $action
     * @param array<array-key, FieldInterface> $items The previous collected fields
     * @return array<array-key, FieldInterface>
     */
    protected function collectFields(FieldsInterface $fields, ActionInterface $action, $items = []): array
    {
        foreach($fields as $field) {
            if ($field instanceof FieldsAwareInterface) {
                $items = $this->collectFields($field->getFields($action), $action, $items);
            }
            $items[] = $field;
        }
        
        return $items;
    }
}