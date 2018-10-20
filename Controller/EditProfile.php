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
use FacturaScripts\Plugins\webportal\Controller\EditProfile as parentController;

/**
 * Description of PortalHome
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditProfile extends parentController
{

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        parent::createSections();

        $this->addListSection('ListWebTeamLog', 'WebTeamLog', 'logs', 'fas fa-file-medical-alt');
        $this->addSearchOptions('ListWebTeamLog', ['description']);
        $this->addOrderOption('ListWebTeamLog', ['time'], 'date', 2);

        $this->addListSection('ListWebTeamMember', 'WebTeamMember', 'teams', 'fas fa-users');
        $this->addOrderOption('ListWebTeamMember', ['creationdate'], 'date', 2);
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'ListWebTeamLog':
            case 'ListWebTeamMember':
                $where[] = new DataBaseWhere('idcontacto', $this->contact->idcontacto);
                $this->sections[$sectionName]->loadData('', $where);
                break;

            default:
                parent::loadData($sectionName);
        }
    }
}
