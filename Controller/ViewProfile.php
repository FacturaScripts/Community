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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ViewProfile
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ViewProfile extends EditSectionController
{

    /**
     *
     * @var Contacto
     */
    protected $mainModel;

    /**
     * 
     * @return bool
     */
    public function contactCanEdit()
    {
        return false;
    }

    /**
     * 
     * @return bool
     */
    public function contactCanSee()
    {
        return true;
    }

    /**
     * 
     * @param bool $reload
     *
     * @return Contacto
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->mainModel) && !$reload) {
            return $this->mainModel;
        }

        $this->mainModel = new Contacto();
        $uri = explode('/', $this->uri);
        $this->mainModel->loadFromCode('', [new DataBaseWhere('idcontacto', end($uri))]);
        return $this->mainModel;
    }

    /**
     * 
     * @param string $date
     * @param string $max
     *
     * @return bool
     */
    public function isDateOld($date, $max)
    {
        return strtotime($date) < strtotime($max);
    }

    protected function createLogSection($name = 'ListWebTeamLog')
    {
        $this->addListSection($name, 'WebTeamLog', 'logs', 'fas fa-file-medical-alt');
        $this->sections[$name]->template = 'Section/TeamLogs.html.twig';
        $this->addSearchOptions($name, ['description']);
        $this->addOrderOption($name, ['time'], 'date', 2);
    }

    protected function createPluginSection($name = 'ListWebProject')
    {
        $this->addListSection($name, 'WebProject', 'plugins', 'fas fa-plug');
        $this->sections[$name]->template = 'Section/Plugins.html.twig';
        $this->addOrderOption($name, ['LOWER(name)'], 'name');
        $this->addOrderOption($name, ['lastmod'], 'last-update', 2);
        $this->addOrderOption($name, ['version'], 'version');
        $this->addOrderOption($name, ['downloads'], 'downloads');
        $this->addSearchOptions($name, ['name', 'description']);

        /// filters
        $licenses = $this->codeModel->all('licenses', 'name', 'title');
        $this->addFilterSelect($name, 'license', 'license', 'license', $licenses);
    }

    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('profile', 'profile', 'Section/Profile');

        $this->createLogSection();
        $this->createTeamSection();
        $this->createPluginSection();
    }

    protected function createTeamSection($name = 'ListWebTeam')
    {
        $this->addListSection($name, 'WebTeam', 'teams', 'fas fa-users');
    }

    protected function loadData(string $sectionName)
    {
        $contacto = $this->getMainModel();
        switch ($sectionName) {
            case 'ListWebProject':
                $where = [
                    new DataBaseWhere('plugin', true),
                    new DataBaseWhere('idcontacto', $contacto->idcontacto)
                ];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListWebTeam':
                $this->loadTeams($sectionName);
                break;

            case 'ListWebTeamLog':
                $where = [new DataBaseWhere('idcontacto', $contacto->idcontacto)];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'profile':
                $this->loadProfile();
                break;
        }
    }

    protected function loadProfile()
    {
        if ($this->getMainModel()->exists()) {
            $this->title = $this->getMainModel()->alias();
            $this->description = $this->getMainModel()->fullName();
            return;
        }

        $this->miniLog->warning($this->i18n->trans('no-data'));
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->webPage->noindex = true;
        $this->setTemplate('Master/Portal404');

        if ('/' == substr($this->uri, -1)) {
            /// redit to homepage
            $this->response->headers->set('Refresh', '0; ' . AppSettings::get('webportal', 'url'));
        }
    }

    protected function loadTeams($sectionName)
    {
        $teamMember = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->getMainModel()->idcontacto),
            new DataBaseWhere('accepted', true),
        ];

        $ids = [];
        foreach ($teamMember->all($where) as $member) {
            $ids[] = $member->idteam;
        }

        $finalWhere = [new DataBaseWhere('idteam', implode(',', $ids), 'IN')];
        $this->sections[$sectionName]->loadData('', $finalWhere);
    }
}
