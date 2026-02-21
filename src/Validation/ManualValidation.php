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
 
namespace Tobento\App\Crud\Validation;

use Tobento\Service\Collection\Collection;
use Tobento\Service\Message\MessagesInterface;
use Tobento\Service\Validation\RuleInterface;
use Tobento\Service\Validation\ValidationInterface;

/**
 * Simple validation result container used for manually constructed
 * validation states such as remapped bulk‑edit errors.
 */
final class ManualValidation implements ValidationInterface
{
    /**
     * @var Collection
     */
    private Collection $data;
    
    /**
     * @var Collection The valid data.
     */
    private Collection $valid;
    
    /**
     * @var Collection The invalid data.
     */
    private Collection $invalid;    
    
    /**
     * @var bool
     */
    private bool $isValid = false;
    
    /**
     * Create a new instance.
     *
     * @param MessagesInterface $errors
     * @param array|Collection $data
     * @param array|Collection $valid
     * @param array|Collection $invalid
     * @param bool $isValid
     */
    public function __construct(
        private MessagesInterface $errors,
        array|Collection $data = [],
        array|Collection $valid = [],
        array|Collection $invalid = [],
        bool $isValid = false,
    ) {
        $this->errors = $errors;
        $this->data = is_array($data) ? new Collection($data) : $data;
        $this->valid = is_array($valid) ? new Collection($valid) : $valid;
        $this->invalid = is_array($invalid) ? new Collection($invalid) : $invalid;
        $this->isValid = $isValid;
    }
    
    /**
     * Returns true if the validation is valid, otherwise false.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }
    
    /**
     * Returns true if the validation is skipped, otherwise false.
     *
     * @return bool
     */
    public function skipped(): bool
    {
        return false;
    }
    
    /**
     * Returns the errors.
     *
     * @return MessagesInterface
     */
    public function errors(): MessagesInterface
    {
        return $this->errors;
    }
    
    /**
     * Returns the data.
     *
     * @return Collection
     */
    public function data(): Collection
    {
        return $this->data;
    }
    
    /**
     * Returns the valid data.
     *
     * @return Collection
     */
    public function valid(): Collection
    {
        return $this->valid;
    }
    
    /**
     * Returns the invalid data.
     *
     * @return Collection
     */
    public function invalid(): Collection
    {
        return $this->invalid;
    }
    
    /**
     * Returns the rule.
     *
     * @return null|RuleInterface
     */
    public function rule(): null|RuleInterface
    {
        return null;
    }
    
    /**
     * Returns the key if any.
     *
     * @return null|string
     */
    public function key(): null|string
    {
        return null;
    }
}