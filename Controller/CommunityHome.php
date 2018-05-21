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
 * Description of PortalHome
 *
 * @author carlos
 */
class CommunityHome extends SectionController
{

    protected function createSections()
    {
        if (null === $this->contact) {
            $this->setTemplate('Master/PortalTemplate');
            return;
        }

        $this->addListSection('teams', 'WebTeamMember', 'Section/MyTeamRequests', 'teams', 'fa-users');
    }

    protected function loadData($sectionName)
    {
        switch ($sectionName) {
            case 'teams':
                $where = [
                    new DataBaseWhere('idcontacto', $this->contact->idcontacto),
                ];
                $this->loadListSection($sectionName, $where);
                break;
        }
    }
}
