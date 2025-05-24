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

namespace Tobento\App\Crud\Table;

use Tobento\Service\Table\Renderer;
use Tobento\Service\Table\TableInterface;
use Tobento\Service\Table\Table;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Str;
use Stringable;

/**
 * CrudTableRenderer
 */
class CrudTableRenderer extends Renderer
{
    /**
     * Return the create groups.
     *
     * @param TableInterface $table
     * @return array
     */
    protected function createGroups(TableInterface $table): array
    {
        $groups = [];
        
        if ($headingRow = $table->getRow(id: 'heading')) {
            $groups['heading'] = [$headingRow];
        }
        
        if ($filterRow = $table->getRow(id: 'filters')) {
            $groups['filters'] = [$filterRow];
        }
        
        $groups['items'] = array_filter($table->getRows(), function($row) {
            if ($row->getId() === 'filters' || $row->getId() === 'heading') {
                return false;
            }
            
            return true;
        });
        
        return $groups;
    }
    
    /**
     * Render the table.
     *
     * @param TableInterface $table
     * @return string
     */
    public function render(TableInterface $table): string
    {
        if (empty($table->getRows())) {
            return '';
        }
        
        $groups = $this->createGroups($table);

        $attributes = new Attributes($table->getAttributes());
        $attributes->add('class', 'table');
        $attributes->add('role', 'table');
        $html = '<div'.$attributes.'>';
        
        foreach($groups as $groupName => $rows) {
            if (empty($rows)) {
                continue;
            }
            $html .= '<div data-table-group="'.$groupName.'"><div>';
            
            foreach($rows as $row) {
                if (empty($row->getColumns())) {
                    continue;
                }

                $attributes = new Attributes($row->getAttributes());
                $attributes->add('class', 'table-row');
                $attributes->set('role', 'row');

                if ($row->isHeading()) {
                    $attributes->add('class', 'th');
                }

                $html .= '<div'.$attributes.'>';

                if ($row->prependedHtml()) {
                    $html .= $row->prependedHtml();
                }

                foreach($row->getColumns() as $column) {
                    $text = $row->isHtml($column->key())
                        ? $column->text()
                        : Str::esc($column->text());

                    $size = $this->getColumnSize($table->getRows(), $column->key());
                    
                    if ($column->key() === 'bulk') {
                        $size = '1';
                    }
                    
                    $role = 'cell';

                    if ($row->isHeading()) {
                        $role = 'columnheader';
                    }

                    if (empty($column->attributes())) {
                        $html .= '<div class="table-col grow-'.Str::esc((string)$size).'" role="'.$role.'">'.$text.'</div>';
                    } else {
                        $attributes = new Attributes($column->attributes());
                        $attributes->add('class', 'table-col grow-'.Str::esc((string)$size));
                        $attributes->set('role', $role);
                        $html .= '<div'.$attributes.'>'.$text.'</div>';
                    }
                }

                if ($row->appendedHtml()) {
                    $html .= $row->appendedHtml();
                }

                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $this->sizes = null;
        
        return $html;
    }
}