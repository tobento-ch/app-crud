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

namespace Tobento\App\Crud\Button;

use Tobento\App\Crud\Entity\Entity;
use Tobento\Service\Collection\Arr;

/**
 * ConfigurableButtons
 */
trait ConfigurableButtons
{
    /**
     * @var array
     */
    private array $buttonsConfig = [];
    
    /**
     * @var array<string, string>
     */
    private array $buttonGroupedTo = [];

    /**
     * Returns the button configuration.
     *
     * @return array
     */
    public function getButtonsConfig(): array
    {
        return $this->buttonsConfig;
    }
    
    /**
     * Adds a button.
     *
     * @param ButtonInterface $button
     * @return static $this
     */
    public function addButton(ButtonInterface $button): static
    {
        $this->buttonsConfig['add'][] = $button;
        return $this;
    }
    
    /**
     * Removes a button or multiple by its name.
     *
     * @param string ...$name
     * @return static $this
     */
    public function removeButton(string ...$name): static
    {
        $this->buttonsConfig['remove'] = $name;
        return $this;
    }
    
    /**
     * Reorders the buttons by names set.
     *
     * @param string ...$name
     * @return static $this
     */
    public function reorderButtons(string ...$name): static
    {
        $this->buttonsConfig['reorder'] = $name;
        return $this;
    }
    
    /**
     * Modify button.
     *
     * @param string $name
     * @param callable $modifier
     * @return static $this
     */
    public function modifyButton(string $name, callable $modifier): static
    {
        $this->buttonsConfig['modify'][$name] = $modifier;
        return $this;
    }
    
    /**
     * Display the button if the rule set validates to true.
     *
     * @param string $name
     * @param bool|callable $rule
     * @return static $this
     */
    public function displayButtonIf(string $name, bool|callable $rule): static
    {
        $this->buttonsConfig['displayIf'][$name] = $rule;
        return $this;
    }
    
    /**
     * Group buttons.
     *
     * @param array<array-key, string> $except
     * @param array<array-key, string> $only
     * @param null|string $label
     * @param null|string $icon
     * @param null|string $name
     * @param null|ButtonInterface $button
     * @return static $this
     */
    public function groupButtons(
        array $except = [],
        array $only = [],
        null|string $label = null,
        null|string $icon = null,
        null|string $name = null,
        null|ButtonInterface $button = null,
    ): static {
        $this->buttonsConfig['group'][] = [
            'except' => $except,
            'only' => $only,
            'label' => $label,
            'icon' => $icon,
            'name' => $name,
            'button' => $button,
        ];
        
        return $this;
    }
    
    /**
     * Sets whether to ask confirmation for the button action.
     *
     * @param string $name
     * @param bool|string|callable $text
     * @return static $this
     */
    public function confirmButtonAction(string $name, bool|string|callable $text = true): static
    {
        $this->buttonsConfig['confirm'][$name] = $text;
        return $this;
    }
    
    /**
     * Sets whether to use AJAX to perform the button action.
     *
     * @param string $name
     * @param bool|string|callable $text
     * @return static $this
     */
    public function ajaxButtonAction(string $name, bool|string|callable $text = true): static
    {
        $this->buttonsConfig['ajax'][$name] = $text;
        return $this;
    }
    
    /**
     * Returns the buttons applied with the configurations.
     *
     * @param ButtonsInterface $buttons
     * @return ButtonsInterface
     */
    public function applyButtonsConfig(ButtonsInterface $buttons): ButtonsInterface
    {
        if (isset($this->buttonsConfig['add'])) {
            $buttons->add(...$this->buttonsConfig['add']);
            unset($this->buttonsConfig['add']);
        }
        
        if (isset($this->buttonsConfig['remove'])) {
            $buttons->remove(...$this->buttonsConfig['remove']);
            unset($this->buttonsConfig['remove']);
        }
        
        if (isset($this->buttonsConfig['reorder'])) {
            $buttons = $buttons->reorder(...$this->buttonsConfig['reorder']);
            unset($this->buttonsConfig['reorder']);
        }
        
        if (!empty($this->buttonsConfig['modify'])) {
            foreach($this->buttonsConfig['modify'] as $name => $modifier) {
                $btns = $this->getButtonsFor($name, $buttons);
                
                if ($button = $btns->get($name)) {
                    $button = clone $button;
                    $modifier($button, $this->entity ?? new Entity());
                    $btns->add($button);
                }
            }
        }
        
        if (!empty($this->buttonsConfig['displayIf'])) {
            foreach($this->buttonsConfig['displayIf'] as $name => $rule) {
                $btns = $this->getButtonsFor($name, $buttons);
                if ($button = $btns->get($name)) {
                    if ($button->getGroup() === 'entity' && is_null($this->entity)) {
                        continue;
                    }
                    
                    if (is_callable($rule)) {
                        $rule = $rule($this->entity ?? new Entity());
                    }
                    
                    if ($rule !== true) {
                        $btns->remove($name);
                    }
                }
            }
        }
        
        if (!empty($this->buttonsConfig['confirm'])) {
            foreach($this->buttonsConfig['confirm'] as $name => $text) {
                $btns = $this->getButtonsFor($name, $buttons);
                
                if ($button = $btns->get($name)) {
                    if (is_string($text) || is_bool($text)) {
                        $button->askConfirmation($text);
                        unset($this->buttonsConfig['confirm'][$name]);
                        continue;
                    }
                    
                    if (is_callable($text)) {
                        $text = $text($this->entity ?? new Entity());
                        $button->askConfirmation($text);
                    }
                }
            }
        }
        
        if (!empty($this->buttonsConfig['ajax'])) {
            foreach($this->buttonsConfig['ajax'] as $name => $text) {
                $btns = $this->getButtonsFor($name, $buttons);
                
                if ($button = $btns->get($name)) {
                    if (is_string($text) || is_bool($text)) {
                        $button->ajaxAction($text);
                        unset($this->buttonsConfig['ajax'][$name]);
                        continue;
                    }
                    
                    if (is_callable($text)) {
                        $text = $text($this->entity ?? new Entity());
                        $button->ajaxAction($text);
                    }
                }
            }
        }
        
        if (!empty($this->buttonsConfig['group'])) {
            $removeButtons = [];
            
            foreach($this->buttonsConfig['group'] as $group) {
                $groupedButtons = [];
                
                if (isset($group['button']) && $group['button'] instanceof ButtonInterface) {
                    $groupButtonName = $group['button']->getName();
                } else {
                    $groupButtonName = $group['name'] ?? '';
                }
                
                if (!empty($group['except'])) {
                    $groupName = $buttons->get($group['except'][0])?->getGroup();
                    
                    foreach ($buttons->group($groupName ?: '') as $button) {
                        if (!in_array($button->getName(), $group['except'])) {
                            $groupedButtons[] = $button->primary(false)->raw();
                            $removeButtons[$button->getName()] = $button->getName();
                            $this->buttonGroupedTo[$button->getName()] = $groupButtonName;
                        }
                    }
                }
                if (!empty($group['only'])) {
                    $groupName = $buttons->get($group['only'][0])?->getGroup();
                    
                    foreach ($buttons->group($groupName ?: '') as $button) {
                        if (in_array($button->getName(), $group['only'])) {
                            $groupedButtons[] = $button->primary(false)->raw();
                            $removeButtons[$button->getName()] = $button->getName();
                            $this->buttonGroupedTo[$button->getName()] = $groupButtonName;
                        }
                    }
                }
                
                if (empty($group['except']) && empty($group['only'])) {
                    if (isset($group['button']) && $group['button'] instanceof ButtonInterface) {
                        $groupName = $group['button']->getGroup();
                    } else {
                        $groupName = $buttons->get($group['name'] ?? '')?->getGroup();
                    }
                    
                    foreach ($buttons->group($groupName ?: '') as $button) {
                        $groupedButtons[] = $button->primary(false)->raw();
                        if ($button->getName() !== ($group['name'] ?? '')) {
                            $removeButtons[$button->getName()] = $button->getName();
                        }
                        $this->buttonGroupedTo[$button->getName()] = $groupButtonName;
                    }
                }
                
                if (empty($groupedButtons)) {
                    continue;
                }
                
                if (isset($group['button']) && $group['button'] instanceof ButtonInterface) {
                    if ($group['button'] instanceof ButtonsAwareInterface) {
                        $group['button']->buttons(...$groupedButtons);
                    }
                    
                    $buttons->add($group['button']);
                    continue;
                }
                
                $button = new Dropdown(
                    label: $group['label'] ?? '',
                    icon: $group['icon'] ?? null,
                    group: $groupName ?? '',
                )->buttons(...$groupedButtons);
                
                if (isset($group['name'])) {
                    $button->name($group['name']);
                }
                
                $buttons->add($button);
            }
            
            foreach ($removeButtons as $button) {
                $buttons->remove($button);
            }
            
            unset($this->buttonsConfig['group']);
        }
        
        return $buttons;
    }
    
    /**
     * Returns the buttons for the button name.
     *
     * @param ButtonsInterface $buttons
     * @return ButtonsInterface
     */
    protected function getButtonsFor(string $buttonName, ButtonsInterface $buttons): ButtonsInterface
    {
        if (isset($this->buttonGroupedTo[$buttonName])) {
            $button = $buttons->get($this->buttonGroupedTo[$buttonName]);
            
            if (! $button instanceof ButtonInterface && ! $button instanceof ButtonsAwareInterface) {
                return $buttons;
            }
            
            $button = $button->withButtons(clone $button->getButtons());
            $buttons->add($button);
            return $button->getButtons();
        }
        
        return $buttons;
    }
}