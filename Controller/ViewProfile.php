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

    public function contactCanEdit()
    {
        return false;
    }

    public function contactCanSee()
    {
        return true;
    }

    public function getMainModel($reload = false)
    {
        if (isset($this->mainModel) && !$reload) {
            return $this->mainModel;
        }

        $contact = new Contacto();
        $uri = explode('/', $this->uri);
        $contact->loadFromCode('', [new DataBaseWhere('idcontacto', end($uri))]);
        return $contact;
    }

    public function getProfileAlias()
    {
        $contact = $this->getMainModel();
        $aux = explode('@', $contact->email);
        return (count($aux) == 2) ? $aux[0] . '_' . $contact->idcontacto : '-';
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

            default:
                parent::loadData($sectionName);
        }
    }

    protected function loadProfile()
    {
        $this->mainModel = $this->getMainModel();
        if ($this->mainModel->exists()) {
            $this->title = $this->getProfileAlias();
            $this->description = $this->mainModel->fullName();
            return;
        }

        $this->miniLog->alert($this->i18n->trans('no-data'));
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->webPage->noindex = true;
        $this->setTemplate('Master/Portal404');
    }

    protected function loadTeams($sectionName)
    {
        $teamMember = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->mainModel->idcontacto),
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
