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
use FacturaScripts\Plugins\Community\Model\PluginProject;
use FacturaScripts\Plugins\Community\Model\WebProject;
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
        if (empty($this->contact)) {
            $this->setTemplate('Master/LoginToContinue');
            return;
        }

        $name = $this->request->get('name', '');
        if ('' !== $name && $this->newPlugin($name)) {
            $this->response->headers->set('Refresh', '1; ' . AppSettings::get('webportal', 'url'));
            return;
        }

        $this->setTemplate('AddPlugin');
    }

    protected function contactCanAdd(): bool
    {
        $idteamdev = AppSettings::get('community', 'idteamdev', '');
        if (empty($idteamdev)) {
            return false;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idteam', $idteamdev),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    protected function newPlugin(string $name): bool
    {
        if (!$this->contactCanAdd()) {
            $this->miniLog->error('join team development');
        }

        $project = new WebProject();
        if ($project->loadFromCode($name)) {
            return false;
        }

        $project->name = $name;
        if (!$project->save()) {
            return false;
        }

        $pluginProject = new PluginProject();
        $pluginProject->description = $name;
        $pluginProject->idcontacto = $this->contact->idcontacto;
        $pluginProject->idproject = $project->idproject;
        $pluginProject->name = $name;
        return $pluginProject->save();
    }
}