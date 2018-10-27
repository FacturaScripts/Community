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
use FacturaScripts\Plugins\Community\Lib\WebPortal\PortalControllerWizard;

/**
 * This class allow us to manage new plugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AddPlugin extends PortalControllerWizard
{

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
            /// redir to existing plugin
            $this->response->headers->set('Refresh', '0; ' . $project->url('public'));
            return false;
        }

        /// save new plugin
        $project->name = $name;
        $project->description = $name;
        $project->idcontacto = $this->contact->idcontacto;
        if ($project->save()) {
            $description = $this->i18n->trans('new-plugin', ['%pluginName%' => $project->name]);
            $link = $project->url('public');
            $this->saveTeamLog($idteamdev, $description, $link);

            /// redir to new plugin
            $this->response->headers->set('Refresh', '0; ' . $link);
            return true;
        }

        return false;
    }
}
