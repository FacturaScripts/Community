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
namespace FacturaScripts\Plugins\Community\Lib\WebPortal;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PortalControllerWizard
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PortalControllerWizard extends PortalController
{

    abstract protected function commonCore();

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        if (empty($this->contact)) {
            $this->miniLog->alert('Contact not found');
            return;
        }

        $this->commonCore();
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        if (empty($this->contact)) {
            $this->setTemplate('Master/LoginToContinue');
            return;
        }

        $this->commonCore();
    }

    /**
     * Return true if contact is member of team $idteam.
     * 
     * @param WebTeam $idteam
     *
     * @return bool
     */
    protected function contactInTeam($idteam): bool
    {
        if (empty($this->contact) && empty($idteam)) {
            return false;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $idteam),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    /**
     * Shows an error message to the contact. Contact must join team $idteam.
     *
     * @param string $idteam
     */
    protected function contactNotInTeamError($idteam)
    {
        $team = new WebTeam();
        $team->loadFromCode($idteam);
        $this->miniLog->alert('<a href="' . $team->url('public') . '">' . $this->i18n->trans('join-team', ['%team%' => $team->name]) . '</a>');
        $this->setTemplate('Master/AccessDenied');
    }

    /**
     * Puts a new line in team's log.
     *
     * @param string $idteam
     * @param string $description
     * @param string $link
     *
     * @return bool
     */
    protected function saveTeamLog($idteam, $description, $link)
    {
        $teamLog = new WebTeamLog();
        $teamLog->description = $description;
        $teamLog->idcontacto = $this->contact->idcontacto;
        $teamLog->idteam = $idteam;
        $teamLog->link = $link;
        return $teamLog->save();
    }
}
