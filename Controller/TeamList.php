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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class TeamList extends SectionController
{

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->addListSection('ListWebTeam', 'WebTeam', 'teams', 'fas fa-users');
        $this->addOrderOption('ListWebTeam', ['name'], 'name');
        $this->addOrderOption('ListWebTeam', ['nummembers'], 'members');

        $this->addListSection('ListWebTeamLog', 'WebTeamLog', 'logs', 'fas fa-file-medical-alt');
        $this->addSearchOptions('ListWebTeamLog', ['description']);
        $this->addOrderOption('ListWebTeamLog', ['time'], 'date', 2);
    }
}
