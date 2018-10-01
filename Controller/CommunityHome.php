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
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of PortalHome
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CommunityHome extends SectionController
{

    /**
     * A list of teams.
     * TODO: Unused.
     *
     * @var array
     */
    private $teams;

    /**
     * Execute common code between private and public core.
     */
    protected function commonCore()
    {
        parent::commonCore();

        /// hide sectionController template if all sections are empty
        if ($this->getTemplate() == 'Master/SectionController.html.twig') {
            $this->hideSections();
        }
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        if (null === $this->contact) {
            $this->setTemplate('Master/PortalTemplate');
            return;
        }

        $this->addSection('home', ['icon' => 'fa-home', 'label' => $this->i18n->trans('home')]);

        $this->addListSection('myissues', 'Issue', 'Section/Issues', 'issues', 'fa-question-circle', 'your');
        $this->addSearchOptions('myissues', ['body', 'creationroute']);
        $this->addOrderOption('myissues', 'lastmod', 'last-update', 2);
        $this->addOrderOption('myissues', 'creationdate', 'date');
        $this->addButton('myissues', 'ContactForm', 'new', 'fa-plus');

        $this->addListSection('issues', 'Issue', 'Section/Issues', 'issues', 'fa-question-circle', 'teams');
        $this->addSearchOptions('issues', ['body', 'creationroute']);
        $this->addOrderOption('issues', 'lastmod', 'last-update', 2);
        $this->addOrderOption('issues', 'creationdate', 'date');

        $this->addListSection('logs', 'WebTeamLog', 'Section/TeamLogs', 'logs', 'fa-file-alt', 'teams');
        $this->addSearchOptions('logs', ['description']);
        $this->addOrderOption('logs', 'time', 'date', 2);
    }

    /**
     * Return a list of team memebers.
     *
     * @return WebTeamMember[]
     */
    protected function getTeams(): array
    {
        $teamMember = new WebTeamMember();
        $where = [new DataBaseWhere('idcontacto', $this->contact->idcontacto)];
        return $teamMember->all($where, [], 0, 0);
    }

    /**
     * Return when was do it the last modification.
     *
     * @param array $section
     *
     * @return int
     */
    protected function getSectionLastmod(array &$section): int
    {
        $lastMod = 0;

        if (isset($section['cursor'][0]->creationdate) && strtotime($section['cursor'][0]->creationdate) > $lastMod) {
            $lastMod = strtotime($section['cursor'][0]->creationdate);
        }

        if (isset($section['cursor'][0]->lastmod) && strtotime($section['cursor'][0]->lastmod) > $lastMod) {
            $lastMod = strtotime($section['cursor'][0]->lastmod);
        }

        return $lastMod;
    }

    /**
     * Hide unneeded sections.
     */
    protected function hideSections()
    {
        if (!empty($this->request->request->all())) {
            return;
        }

        $empty = true;
        $lastMod = 0;
        foreach ($this->sections as $name => $section) {
            if ($section['count'] > 0) {
                $empty = false;
            } elseif ($name !== 'home') {
                unset($this->sections[$name]);
            }

            if ($this->getSectionLastmod($section) > $lastMod) {
                $lastMod = $this->getSectionLastmod($section);
            }
        }

        if ($empty) {
            $this->setTemplate('Master/PortalTemplate');
            return;
        }

        foreach ($this->sections as $name => $section) {
            $this->active = $name;
            $this->current = $name;
            if ($this->getSectionLastmod($section) >= $lastMod) {
                break;
            }
        }
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $where = [];
        switch ($sectionName) {
            case 'issues':
                $where[] = new DataBaseWhere('idcontacto', $this->contact->idcontacto, '!=');
                /// no break
            case 'logs':
                $idTeams = [];
                foreach ($this->getTeams() as $member) {
                    if ($member->accepted) {
                        $idTeams[] = $member->idteam;
                    }
                }
                $where[] = new DataBaseWhere('idteam', implode(',', $idTeams), 'IN');
                $this->loadListSection($sectionName, $where);
                break;

            case 'myissues':
                $where[] = new DataBaseWhere('idcontacto', $this->contact->idcontacto);
                $this->loadListSection($sectionName, $where);
                break;
        }
    }
}
