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
namespace FacturaScripts\Plugins\Community\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use Symfony\Component\HttpFoundation\Response;

trait WebTeamMethodsTrait
{

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
        $this->miniLog->warning('<a href="' . $team->url('public') . '">' . $this->i18n->trans('join-team', ['%team%' => $team->name]) . '</a>');
        $this->setTemplate('Master/AccessDenied');
        $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
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
    protected function saveTeamLog($idteam, $description, $link = '')
    {
        $teamLog = new WebTeamLog();
        $teamLog->description = $description;
        $teamLog->idcontacto = $this->contact->idcontacto;
        $teamLog->idteam = $idteam;
        $teamLog->link = $link;
        return $teamLog->save();
    }

    /**
     * 
     * @param string $idteam
     * @param string $idcontacto
     * @param string $link
     *
     * @return WebTeamLog
     */
    protected function searchTeamLog($idteam, $idcontacto, $link)
    {
        $teamLog = new WebTeamLog();
        $where = [
            new DataBaseWhere('idteam', $idteam),
            new DataBaseWhere('idcontacto', $idcontacto),
            new DataBaseWhere('link', $link)
        ];
        return $teamLog->all($where, ['time' => 'DESC']);
    }
}
