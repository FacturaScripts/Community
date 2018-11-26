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
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;

/**
 * Description of WebTeamPoints
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WebTeamPoints
{

    const MAX_POINTS_CONTACT_PER_WEEK = 5;
    const MAX_POINTS_PER_LOG = 0.5;

    /**
     * 
     * @param string $period
     */
    public function run(string $period)
    {
        $teams = [];
        $teamLog = new WebTeamLog();
        $where = [
            new DataBaseWhere('idcontacto', null, 'IS NOT'),
            new DataBaseWhere('link', null, 'IS NOT'),
            new DataBaseWhere('link', '', '!='),
            new DataBaseWhere('time', date('d-m-Y H:i:s', strtotime('-' . $period)), '>')
        ];

        /// how much logs per team?
        $total = 0;
        foreach ($teamLog->all($where, ['time' => 'ASC'], 0, 0) as $tLog) {
            if (!isset($teams[$tLog->idteam])) {
                $teams[$tLog->idteam] = 0;
            }

            $teams[$tLog->idteam] ++;
            $total++;
        }

        if (empty($teams)) {
            return;
        }

        $this->calculatePoints($teams, $total);

        /// how much points per contact?
        $contacts = [];
        foreach ($teamLog->all($where, ['time' => 'ASC'], 0, 0) as $tLog) {
            if (!isset($contacts[$tLog->idcontacto])) {
                $contacts[$tLog->idcontacto] = 0;
            }

            $contacts[$tLog->idcontacto] += $teams[$tLog->idteam];
        }
        $this->savePoints($contacts);
    }

    /**
     * Distribute points by team. More logs, less points per one.
     *
     * @param array $teams
     */
    private function calculatePoints(array &$teams, $total)
    {
        $webTeam = new WebTeam();
        $max = $total / $webTeam->count();
        foreach ($teams as $key => $value) {
            if ($value > $max) {
                $teams[$key] = min([$max / $value, self::MAX_POINTS_PER_LOG]);
                continue;
            }

            $teams[$key] = self::MAX_POINTS_PER_LOG;
        }
    }

    /**
     * 
     * @param array $contacts
     */
    private function savePoints(array &$contacts)
    {
        foreach ($contacts as $idcontacto => $points) {
            if ($points < 1) {
                continue;
            }

            $contact = new Contacto();
            if ($contact->loadFromCode($idcontacto)) {
                $contact->puntos += (int) min([$points, self::MAX_POINTS_CONTACT_PER_WEEK]);
                $contact->save();
            }
        }
    }
}
