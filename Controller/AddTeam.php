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
use FacturaScripts\Plugins\Community\Lib;
use FacturaScripts\Plugins\Community\Lib\WebPortal\PortalControllerWizard;
use FacturaScripts\Plugins\Community\Model\WebTeam;

/**
 * Description of AddTeam
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AddTeam extends PortalControllerWizard
{

    use Lib\PointsMethodsTrait;

    /**
     * 
     * @return int
     */
    public function pointCost()
    {
        return 5;
    }

    protected function commonCore()
    {
        $this->setTemplate('AddTeam');

        $name = $this->request->request->get('name', '');
        $description = $this->request->request->get('description', '');
        $private = (bool) $this->request->request->get('private', false);
        if (!empty($name) && !empty($description)) {
            $this->newWebTeam($name, $description, $private);
        }
    }

    /**
     * 
     * @param string $name
     * @param string $description
     * @param bool   $private
     *
     * @return bool
     */
    protected function newWebTeam($name, $description, $private)
    {
        /// team already exists?
        $team = new WebTeam();
        $where = [new DataBaseWhere('name', $name)];
        if ($team->loadFromCode('', $where)) {
            $this->miniLog->error($this->i18n->trans('duplicate-record'));
            return false;
        }

        if (!$this->contactHasPoints($this->pointCost())) {
            return $this->redirToYouNeedMorePointsPage();
        }

        /// save new team
        $team->name = $name;
        $team->description = $description;
        $team->idcontacto = $this->contact->idcontacto;
        $team->private = $private;
        if ($team->save()) {
            $this->subtractPoints();

            /// redir to new plugin
            $this->response->headers->set('Refresh', '0; ' . $team->url('public'));
            return true;
        }

        $this->miniLog->alert($this->i18n->trans('record-save-error'));
        return false;
    }
}
