<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of PluginList
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PluginList extends SectionController
{

    protected function createPluginSection($name, $title)
    {
        $this->addListSection($name, 'WebProject', $title, 'fas fa-plug', '2018');
        $this->addOrderOption($name, ['LOWER(name)'], 'name', 1);
        $this->addSearchOptions($name, ['name', 'description']);

        /// buttons
        $button = [
            'action' => 'AddPlugin',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link'
        ];
        if ($this->contact) {
            $this->addButton($name, $button);
        }
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        /// all plugins
        $this->createPluginSection('ListWebProject', 'plugins');

        /// your plugins
        if ($this->contact) {
            $this->createPluginSection('ListWebProject-you', 'your');
        }
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $where = [new DataBaseWhere('plugin', true)];
        switch ($sectionName) {
            case 'ListWebProject':
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListWebProject-you':
                $where[] = new DataBaseWhere('idcontacto', $this->contact->idcontacto);
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }
}
