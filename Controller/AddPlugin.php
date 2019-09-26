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
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\Community\Lib\WebPortal\PortalControllerWizard;

/**
 * This class allow us to manage new plugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AddPlugin extends PortalControllerWizard
{

    /**
     *
     * @var string
     */
    public $version = '2018';

    /**
     * 
     * @return array
     */
    public function coreVersions()
    {
        return ['2018'];
    }

    /**
     * 
     * @return CodeModel[]
     */
    public function licenses()
    {
        $codeModel = new CodeModel();
        return $codeModel->all('licenses', 'name', 'title', false);
    }

    /**
     * 
     * @return array
     */
    public function types()
    {
        return WebProject::avaliableTypes();
    }

    /**
     * Execute common code between private and public core.
     */
    protected function commonCore()
    {
        $this->setTemplate('AddPlugin');
        $this->title = $this->description = $this->toolBox()->i18n()->trans('new-plugin', ['%pluginName%' => '']);

        $getVersion = $this->request->query->get('version', $this->version);
        $this->version = $this->request->request->get('version', $getVersion);

        $name = $this->request->get('name', '');
        if (!empty($name)) {
            $this->newPlugin($name);
        }
    }

    /**
     * 
     * @return WebTeam
     */
    protected function getPrivateTeam()
    {
        /// contact owns a private team?
        $teamModel = new WebTeam();
        $where = [
            new DataBaseWhere('private', true),
            new DataBaseWhere('idcontacto', $this->contact->idcontacto)
        ];
        foreach ($teamModel->all($where, [], 0, 0) as $team) {
            return $team;
        }

        /// contact is in private team?
        $teamMemberModel = new WebTeamMember();
        $where2 = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('accepted', true)
        ];
        foreach ($teamMemberModel->all($where2, [], 0, 0) as $member) {
            if ($teamModel->loadFromCode($member->idteam) && $teamModel->private) {
                return $teamModel;
            }
        }

        /// create a new team
        $newTeam = new WebTeam();
        $newTeam->description = $this->contact->alias();
        $newTeam->idcontacto = $this->contact->idcontacto;
        $newTeam->name = $this->contact->alias();
        $newTeam->private = true;
        $newTeam->save();
        return $newTeam;
    }

    /**
     * Adds new plugin to the community.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function newPlugin(string $name): bool
    {
        /// contact is in dev team?
        $idteamdev = $this->toolBox()->appSettings()->get('community', 'idteamdev', '');
        if (!$this->contactInTeam($idteamdev)) {
            $this->contactNotInTeamError($idteamdev);
            return false;
        }

        /// plugin exist?
        $project = new WebProject();
        $where = [new DataBaseWhere('name', $name)];
        if ($project->loadFromCode('', $where)) {
            $this->toolBox()->i18nLog()->warning('duplicate-record');
            return false;
        }

        /// save new plugin
        $project->name = $name;
        $project->description = $this->request->request->get('description', $name);
        $project->idcontacto = $this->contact->idcontacto;
        $project->idteam = $this->getPrivateTeam()->idteam;
        $project->license = $this->request->request->get('license');
        $project->publicrepo = $this->request->request->get('git');
        $project->type = $this->request->request->get('type');
        if ($project->save()) {
            /// redir to new plugin
            $this->redirect($project->url('public'));
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }
}
