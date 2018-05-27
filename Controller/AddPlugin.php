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
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;

/**
 * Description of AddPlugin
 *
 * @author carlos
 */
class AddPlugin extends PortalController
{

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->commonCore();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->commonCore();
    }

    protected function commonCore()
    {
        if (empty($this->contact) && !$this->user) {
            $this->setTemplate('Master/LoginToContinue');
            return;
        }

        $name = $this->request->get('name', '');
        if ('' !== $name && $this->newPlugin($name)) {
            return;
        }

        $this->setTemplate('AddPlugin');
    }

    protected function contactCanAdd(): bool
    {
        if ($this->user) {
            return true;
        }

        $idteamdev = AppSettings::get('community', 'idteamdev', '');
        if (empty($idteamdev)) {
            return false;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $idteamdev),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    protected function newPlugin(string $name): bool
    {
        if (!$this->contactCanAdd()) {
            $idteamdev = AppSettings::get('community', 'idteamdev', '');
            $team = new WebTeam();
            $team->loadFromCode($idteamdev);
            $this->miniLog->error($this->i18n->trans('join-team', ['%team%' => $team->name]));
            return false;
        }

        $project = new WebProject();
        if ($project->loadFromCode($name)) {
            return false;
        }

        $project->name = $name;
        $project->description = $name;
        $project->idcontacto = empty($this->contact) ? null : $this->contact->idcontacto;
        if ($project->save()) {
            $this->saveTeamLog($project);
            $this->response->headers->set('Refresh', '0; ' . $project->url('public'));
            return true;
        }

        return false;
    }

    protected function saveTeamLog(WebProject $plugin)
    {
        $idteamdev = AppSettings::get('community', 'idteamdev', '');
        if (empty($idteamdev)) {
            return;
        }

        $teamLog = new WebTeamLog();
        $teamLog->description = 'New plugin: ' . $plugin->name;
        $teamLog->idcontacto = $plugin->idcontacto;
        $teamLog->idteam = $idteamdev;
        $teamLog->link = $plugin->url('public');
        $teamLog->save();
    }
}
