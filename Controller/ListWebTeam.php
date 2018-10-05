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

use FacturaScripts\Dinamic\Lib\ExtendedController;

/**
 * Description of ListWebProject controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListWebTeam extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'teams';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fas fa-users';

        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        /// Teams
        $this->addView('ListWebTeam', 'WebTeam', 'teams', 'fas fa-users');
        $this->addSearchFields('ListWebTeam', ['name']);
        $this->addOrderBy('ListWebTeam', ['name']);
        $this->addOrderBy('ListWebTeam', ['creationdate'], 'date', 2);

        /// Members
        $this->addView('ListWebTeamMember', 'WebTeamMember', 'members', 'fas fa-users');
        $this->addSearchFields('ListWebTeamMember', ['idcontacto']);
        $this->addOrderBy('ListWebTeamMember', ['creationdate'], 'date', 2);

        /// Members
        $this->addView('ListWebTeamLog', 'WebTeamLog', 'logs', 'fas fa-file');
        $this->addSearchFields('ListWebTeamLog', ['description']);
        $this->addOrderBy('ListWebTeamLog', ['time'], 'date', 2);
    }
}
