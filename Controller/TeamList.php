<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of TeamList
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class TeamList extends SectionController
{

    protected function createLogSection($name = 'ListWebTeamLog')
    {
        $this->addListSection($name, 'WebTeamLog', 'logs', 'fas fa-file-medical-alt');
        $this->sections[$name]->template = 'Section/TeamLogs.html.twig';
        $this->addSearchOptions($name, ['description']);
        $this->addOrderOption($name, ['time'], 'date', 2);
    }

    protected function createPublicationSection($name = 'ListPublication')
    {
        $this->addListSection($name, 'Publication', 'publications', 'fas fa-newspaper');
        $this->addSearchOptions($name, ['title', 'body']);
        $this->addOrderOption($name, ['creationdate'], 'date', 2);
        $this->addOrderOption($name, ['visitcount'], 'visit-counter');

        /// buttons
        if ($this->user) {
            $button = [
                'action' => 'AddPublication',
                'color' => 'success',
                'icon' => 'fas fa-plus',
                'label' => 'new',
                'type' => 'link'
            ];
            $this->addButton($name, $button);
        }
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->createTeamSection();
        $this->createPublicationSection();
        $this->createLogSection();

        if ($this->contact) {
            $this->createTeamSection('ListWebTeam-you', 'your');
        }
    }

    protected function createTeamSection($name = 'ListWebTeam', $group = '')
    {
        $this->addListSection($name, 'WebTeam', 'teams', 'fas fa-users', $group);
        $this->addOrderOption($name, ['name'], 'name');
        $this->addOrderOption($name, ['nummembers'], 'members');
        $this->addOrderOption($name, ['visitcount'], 'visit-counter');

        /// buttons
        if ($this->contact) {
            $button = [
                'action' => 'AddTeam',
                'color' => 'success',
                'icon' => 'fas fa-plus',
                'label' => 'new',
                'type' => 'link'
            ];
            $this->addButton($name, $button);
        }
    }

    /**
     * Return the list of team member relations of this contact.
     *
     * @return WebTeamMember[]
     */
    protected function getTeamsMemberData(): array
    {
        $teamMember = new WebTeamMember();
        $where = [new DataBaseWhere('idcontacto', $this->contact->idcontacto)];
        return $teamMember->all($where, [], 0, 0);
    }

    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'ListPublication':
            case 'ListWebTeamLog':
                parent::loadData($sectionName);
                break;

            case 'ListWebTeam':
                if ($this->user) {
                    parent::loadData($sectionName);
                } else {
                    $where[] = new DataBaseWhere('private', false);
                    $this->sections[$sectionName]->loadData('', $where);
                }
                break;

            case 'ListWebTeam-you':
                $idTeams = [];
                foreach ($this->getTeamsMemberData() as $member) {
                    if ($member->accepted) {
                        $idTeams[] = $member->idteam;
                    }
                }
                $where[] = new DataBaseWhere('idteam', implode(',', $idTeams), 'IN');
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }
}
