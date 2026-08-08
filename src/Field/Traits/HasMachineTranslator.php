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

namespace Tobento\App\Crud\Field\Traits;

use Tobento\App\Crud\Field\FieldInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\View\ViewInterface;

trait HasMachineTranslator
{
    protected bool $machineTranslatorEnabled = false;
    
    protected null|string $machineTranslatorName = null;
    
    protected null|string $machineTranslatorLabel = null;
    
    /*
     * @var array|callable
     */
    protected $machineTranslatorAttributes = [];

    /**
     * Enable machine translation for this field.
     *
     * @param string|null $translator Optional translator name (e.g. "deepl")
     * @param string|null $label Optional button label
     * @param array|callable $attributes Additional HTML attributes for the button
     * @param bool $allowNonTranslatable
     */
    public function machineTranslator(
        null|string $translator = null,
        null|string $label = null,
        array|callable $attributes = [],
        bool $allowNonTranslatable = false,
    ): static {
        if (! $allowNonTranslatable && ! $this->isTranslatable()) {
            throw new \LogicException(
                sprintf('Field "%s" must be translatable() before using machineTranslator()', $this->name())
            );
        }
        
        if (!is_array($attributes) && !is_callable($attributes)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'machineTranslator() expects $attributes to be array or callable, %s given.',
                    gettype($attributes)
                )
            );
        }
        
        $this->machineTranslatorEnabled = true;
        $this->machineTranslatorName = $translator;
        $this->machineTranslatorLabel = $label;
        $this->machineTranslatorAttributes = $attributes;
        return $this;
    }

    /**
     * Determine whether the machine translator is enabled for this field.
     *
     * Fields that support translation may enable or disable the machine
     * translator feature. This method returns the current state so the
     * renderer can decide whether to display the translator UI.
     *
     * @return bool True if the machine translator is enabled, otherwise false.
     */
    public function hasMachineTranslator(): bool
    {
        return $this->machineTranslatorEnabled;
    }
    
    /**
     * Renders the machine-translator button for the field.
     *
     * Generates the HTML element containing all required
     * data-machine-translator attributes, including the
     * target field ("to") that should receive the translated text.
     *
     * @param string $toField The fully qualified field name (e.g. "title.en") to populate with the translation.
     * @param FieldInterface $field
     * @param string $actionName
     * @param ViewInterface $view The view instance used for asset loading and translations.
     * @return string The rendered HTML for the machine-translator button.
     * @psalm-suppress UndefinedInterfaceMethod
     *
     * @see https://github.com/tobento-ch/app-machine-translator#javascript-translator
     */
    public function renderMachineTranslator(
        string $toField,
        FieldInterface $field,
        string $actionName,
        ViewInterface $view,
    ): string {
        // Ensure translator.js is loaded
        $view->asset('assets/machine-translator/translator.js')->attr('type', 'module');
        
        $url = (string)$view->routeUrl(name: 'machine-translate', throw: false);
        
        if ($url === '') {
            return '';
        }

        // Base attributes
        $attrs = [
            'class' => 'button text-xxs ml-s',
            'data-machine-translator' => [
                'url' => $url,
                'from_first' => '[data-field]',
                'to' => $toField,
                'empty_message' => $view->trans('No text found to translate from.'),
                'target_not_empty_message' => $view->trans('Target already contains text.'),
            ],
        ];
        
        $attributes = [];
        
        if (is_callable($this->machineTranslatorAttributes)) {
            $attributes = ($this->machineTranslatorAttributes)($field, $actionName, $view);
        } elseif (is_array($this->machineTranslatorAttributes)) {
            $attributes = $this->machineTranslatorAttributes;
        }
        
        // Merge custom attributes (user overrides)
        $attrs = array_replace_recursive($attrs, $attributes);

        // Add translator name if provided
        if (is_string($this->machineTranslatorName)) {
            $attrs['data-machine-translator']['translator'] = $this->machineTranslatorName;
        }
        
        // Determine button label
        $label = $this->machineTranslatorLabel ?? $view->etrans('Auto-Translate');
        
        // Render final HTML
        return sprintf(
            '<span%s>%s</span>',
            (string) new Attributes($attrs),
            $label
        );
    }
}