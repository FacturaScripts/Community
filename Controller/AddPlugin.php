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

use FacturaScripts\Core\App\AppSettings;
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
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['title'] = 'new-plugin';

        return $data;
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
        return ['public', 'private'];
    }

    /**
     * Execute common code between private and public core.
     */
    protected function commonCore()
    {
        $this->setTemplate('AddPlugin');

        $name = $this->request->get('name', '');
        if (!empty($name)) {
            $this->newPlugin($name);
        }
    }

    /**
     * 
     * @return string
     */
    protected function getPluginType()
    {
        switch ($this->request->request->get('type')) {
            case 'private':
                return 'private';

            default:
                return 'public';
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
        $idteamdev = AppSettings::get('community', 'idteamdev', '');
        if (!$this->contactInTeam($idteamdev)) {
            $this->contactNotInTeamError($idteamdev);
            return false;
        }

        /// plugin exist?
        $project = new WebProject();
        $where = [new DataBaseWhere('name', $name)];
        if ($project->loadFromCode('', $where)) {
            $this->miniLog->error($this->i18n->trans('duplicate-record'));
            return false;
        }

        /// save new plugin
        $project->name = $name;
        $project->description = $this->request->request->get('description', $name);
        $project->idcontacto = $this->contact->idcontacto;
        $project->idteam = $this->getPrivateTeam()->idteam;
        $project->license = $this->request->request->get('license');
        $project->publicrepo = $this->request->request->get('git');
        $project->type = $this->getPluginType();
        if ($project->save()) {
            $description = $this->i18n->trans('new-plugin', ['%pluginName%' => $name]);
            $link = $project->url('public');
            $this->saveTeamLog($idteamdev, $description, $link);

            /// redir to new plugin
            $this->response->headers->set('Refresh', '0; ' . $link);
            return true;
        }

        $this->miniLog->alert($this->i18n->trans('record-save-error'));
        return false;
    }
}
