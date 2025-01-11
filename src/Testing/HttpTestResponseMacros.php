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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tobento\App\Testing\Http\TestResponse;

/**
 * @psalm-suppress UnusedClosureParam
 */
class HttpTestResponseMacros
{
    /**
     * Applies the crud macros to the test response.
     */
    public static function applyAllAsserts(): void
    {
        static::applyIndexAsserts();
        static::applyFormAsserts();
    }
    
    /**
     * Applies the crud index macros to the test response.
     *
     * @return void
     * @psalm-suppress UnusedClosureParam
     * @psalm-suppress InvalidScope
     */
    public static function applyIndexAsserts(): void
    {
        TestResponse::macro(
            'assertCrudIndexEntityCount',
            function(int $count, null|string $message = null): static {
                
                if (0 > $count) {
                    throw new InvalidArgumentException('Entity count must be >= 0');
                }

                if (0 === $count) {
                    return $this->assertNodeMissing(
                        selector: '[data-entity-id]',
                        message: $message ??= 'There should be no records found in the index table.',
                    );
                }
                
                $this->assertNodeExists(
                    selector: '[data-entity-id]',
                    callback: fn ($n): bool => $n->count() === $count,
                    message: $message ??= sprintf('There should be a total of %d records found in the index table.', $count),
                );
                
                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexEntityExists',
            function(string|int $entityId, array $withButtons = [], array $withoutButtons = [], null|string $message = null): static {
                // entity:
                $entityId = (string)$entityId;
                
                $this->assertNodeExists(
                    selector: sprintf('[data-entity-id="%s"]', $entityId),
                    message: $message ?: sprintf('Entity id %s not found in the index table.', $entityId),
                );
                
                // with buttons:
                $entityRow = $this->crawl()->filter(sprintf('[data-entity-id="%s"]', $entityId));
                
                $foundButtons = $entityRow->filter('[data-button]')->each(function(Crawler $node): string {
                    return (string)$node->attr('data-button');
                });
                
                $missingButtons = array_diff($withButtons, $foundButtons);
                
                TestCase::assertTrue(
                    empty($missingButtons),
                    sprintf(
                        'Entity id %s misses the button(s) "%s" in the index table.',
                        $entityId,
                        implode(', ', $missingButtons)
                    ),
                );
                
                // without buttons:
                $unexpectedButtons = array_intersect($withoutButtons, $foundButtons);
                
                TestCase::assertTrue(
                    empty($unexpectedButtons),
                    sprintf(
                        'Entity id %s has the unexpected button(s) "%s" in the index table.',
                        $entityId,
                        implode(', ', $unexpectedButtons)
                    ),
                );

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexEntityMissing',
            function(string|int $entityId, null|string $message = null): static {
                $entityId = (string) $entityId;
                
                $this->assertNodeMissing(
                    selector: sprintf('[data-entity-id="%s"]', $entityId),
                    message: $message ?: sprintf('The index table has unexpected entity id %s.', $entityId),
                );

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexHeaderColumnsExists',
            function(array $columns, null|string $message = null): static {            
                foreach($columns as $col) {
                    $this->assertNodeExists(
                        selector: sprintf('[data-header-col="%s"]', $col),
                        message: $message ?: sprintf('Header column %s not found in the index table.', $col),
                    );
                }

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexHeaderColumnsMissing',
            function(array $columns, null|string $message = null): static {
                foreach($columns as $col) {
                    $this->assertNodeMissing(
                        selector: sprintf('[data-header-col="%s"]', $col),
                        message: $message ?: sprintf('Header column %s has been found in the index table.', $col),
                    );
                }

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexFiltersExists',
            function(array $filters, string $group = 'header', null|string $message = null): static {
                $filtersGroup = $this->crawl()->filter(sprintf('[data-filters="%s"]', $group));
                
                $foundFilters = $filtersGroup->filter('[data-filter]')->each(function(Crawler $node): string {
                    return (string)$node->attr('data-filter');
                });
                
                $missingFilters = array_diff($filters, $foundFilters);
                
                TestCase::assertTrue(
                    empty($missingFilters),
                    $message ?: sprintf(
                        'The index table misses the %s filter(s) "%s".',
                        $group,
                        implode(', ', $missingFilters)
                    ),
                );

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexFiltersMissing',
            function(array $filters, string $group = 'header', null|string $message = null): static {
                $filtersGroup = $this->crawl()->filter(sprintf('[data-filters="%s"]', $group));
                
                $foundFilters = $filtersGroup->filter('[data-filter]')->each(function(Crawler $node): string {
                    return (string)$node->attr('data-filter');
                });
                
                $unexpectedFilters = array_intersect($filters, $foundFilters);
                
                TestCase::assertTrue(
                    empty($unexpectedFilters),
                    $message ?: sprintf(
                        'The index table has the unexpected %s filter(s) "%s".',
                        $group,
                        implode(', ', $unexpectedFilters)
                    ),
                );

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexButtonsExists',
            function(array $buttons, string $group = 'global', null|string $message = null): static {
                $buttonsGroup = $this->crawl()->filter(sprintf('[data-buttons="%s"]', $group));
                
                $foundButtons = $buttonsGroup->filter('[data-button]')->each(function(Crawler $node): string {
                    return (string)$node->attr('data-button');
                });
                
                $missingButtons = array_diff($buttons, $foundButtons);
                
                TestCase::assertTrue(
                    empty($missingButtons),
                    $message ?: sprintf(
                        'The index table misses the %s button(s) "%s".',
                        $group,
                        implode(', ', $missingButtons)
                    ),
                );

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexButtonsMissing',
            function(array $buttons, string $group = 'global', null|string $message = null): static {
                $buttonsGroup = $this->crawl()->filter(sprintf('[data-buttons="%s"]', $group));
                
                $foundButtons = $buttonsGroup->filter('[data-button]')->each(function(Crawler $node): string {
                    return (string)$node->attr('data-button');
                });
                
                $unexpectedButtons = array_intersect($buttons, $foundButtons);
                
                TestCase::assertTrue(
                    empty($unexpectedButtons),
                    $message ?: sprintf(
                        'The index table has the unexpected %s button(s) "%s".',
                        $group,
                        implode(', ', $unexpectedButtons)
                    ),
                );

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexBulkActionsExists',
            function(array $actions, null|string $message = null): static {
                $foundActions = $this->crawl()->filter('[data-bulk-action]')->each(function(Crawler $node): string {
                    return (string)$node->attr('data-bulk-action');
                });
                
                $missingActions = array_diff($actions, $foundActions);
                
                TestCase::assertTrue(
                    empty($missingActions),
                    $message ?: sprintf(
                        'The index table misses the bulk action(s) "%s".',
                        implode(', ', $missingActions)
                    ),
                );

                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudIndexBulkActionsMissing',
            function(array $actions, null|string $message = null): static {
                $foundActions = $this->crawl()->filter('[data-bulk-action]')->each(function(Crawler $node): string {
                    return (string)$node->attr('data-bulk-action');
                });
                
                $unexpectedActions = array_intersect($actions, $foundActions);
                
                TestCase::assertTrue(
                    empty($unexpectedActions),
                    $message ?: sprintf(
                        'The index table has the unexpected bulk action(s) "%s".',
                        implode(', ', $unexpectedActions)
                    ),
                );
                
                return $this;
            }
        );
    }
    
    /**
     * Applies the crud form macros to the test response.
     *
     * @return void
     * @psalm-suppress UnusedClosureParam
     * @psalm-suppress InvalidScope
     */
    public static function applyFormAsserts(): void
    {
        TestResponse::macro(
            'assertCrudFormFieldExists',
            function(
                string $field,
                null|string $label = null,
                null|string $requiredText = null,
                null|string $optionalText = null,
                null|string $infoText = null,
                null|string $errorText = null,
                null|bool $translatable = null,
                null|string $message = null
            ): static {
                $this->assertNodeExists(
                    selector: sprintf('[data-field="%s"]', $field),
                    message: $message ?: sprintf('The field %s is missing in the form.', $field),
                );
                
                if ($translatable === true) {
                    $this->assertNodeExists(
                        selector: sprintf('[data-field="%s"][data-translatable]', $field),
                        message: sprintf('The field %s is not translatable in the form.', $field),
                    );
                } elseif ($translatable === false) {
                    $this->assertNodeMissing(
                        selector: sprintf('[data-field="%s"][data-translatable]', $field),
                        message: sprintf('The field %s is translatable in the form.', $field),
                    );
                }
                
                if ($label) {
                    $this->assertNodeExists(
                        selector: sprintf('[data-field="%s"] label', $field),
                        callback: fn (Crawler $n): bool => str_starts_with($n->text(), $label),
                        message: sprintf('The field %s has not the correct label "%s" in the form.', $field, $label),
                    );
                }
                
                if ($requiredText) {
                    $this->assertNodeExists(
                        selector: sprintf('[data-field="%s"] label', $field),
                        callback: fn (Crawler $n): bool => str_ends_with($n->text(), $requiredText),
                        message: sprintf('The field %s has not the correct required text "%s" in the form.', $field, $requiredText),
                    );
                }
                
                if ($optionalText) {
                    $this->assertNodeExists(
                        selector: sprintf('[data-field="%s"] label', $field),
                        callback: fn (Crawler $n): bool => str_ends_with($n->text(), $optionalText),
                        message: sprintf('The field %s has not the correct optional text "%s" in the form.', $field, $optionalText),
                    );
                }
                
                if ($infoText) {
                    $this->assertNodeExists(
                        selector: sprintf('[data-field="%s"] p', $field),
                        callback: fn (Crawler $n): bool => str_ends_with($n->first()->text(), $infoText),
                        message: sprintf('The field %s has not the correct info text "%s" in the form.', $field, $infoText),
                    );
                }
                
                if ($errorText) {
                    $this->assertNodeExists(
                        selector: sprintf('[data-field="%s"] .error', $field),
                        callback: fn (Crawler $n): bool => str_ends_with($n->first()->text(), $errorText),
                        message: sprintf('The field %s has not the correct error text "%s" in the form.', $field, $errorText),
                    );
                }
                
                return $this;
            }
        );
        
        TestResponse::macro(
            'assertCrudFormFieldMissing',
            function(string $field, null|string $message = null): static {
                $this->assertNodeMissing(
                    selector: sprintf('[data-field="%s"]', $field),
                    message: $message ?: sprintf('The field %s is found in the form.', $field),
                );
                
                return $this;
            }
        );
    }
}