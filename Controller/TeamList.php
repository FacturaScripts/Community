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

use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of TeamList
 *
 * @author carlos
 */
class TeamList extends SectionController
{

    protected function createSections()
    {
        $this->addListSection('teams', 'WebTeam', 'Section/Teams', 'teams', 'fa-users');
        $this->addOrderOption('teams', 'name', 'name');
        $this->addOrderOption('teams', 'nummembers', 'members');
        $this->addOrderOption('teams', 'nummembers', 'requests');

        $this->addListSection('logs', 'WebTeamLog', 'Section/TeamLogs', 'logs', 'fa-file-text-o');
        $this->addSearchOptions('logs', ['description']);
        $this->addOrderOption('logs', 'time', 'date', 2);
    }

    protected function loadData($sectionName)
    {
        switch ($sectionName) {
            case 'logs':
                $this->loadListSection($sectionName);
                break;

            case 'teams':
                $this->loadListSection($sectionName);
                break;
        }
    }
}
