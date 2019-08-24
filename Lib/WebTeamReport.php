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
namespace FacturaScripts\Plugins\Community\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Core\Lib\Email\TableBlock;
use FacturaScripts\Plugins\Community\Model\Publication;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;

/**
 * Description of WebTeamReport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WebTeamReport
{

    /**
     * Quantity of contact to simulate pagination to send email.
     */
    const MAX_EMAIL_BCC = 50;

    public function expelInactiveMembers()
    {
        $teamModel = new WebTeam();
        foreach ($teamModel->all([], [], 0, 0) as $team) {
            if (empty($team->maxinactivitydays)) {
                continue;
            }

            $minTime = strtotime('-' . $team->maxinactivitydays . ' days');
            foreach ($this->getTeamMembers($team) as $member) {
                if (strtotime($member->getContact()->lastactivity) < $minTime) {
                    $member->expel(true);
                }
            }
        }
    }

    /**
     * 
     * @param string $period
     */
    public function sendMail($period)
    {
        $teamModel = new WebTeam();
        foreach ($teamModel->all([], [], 0, 0) as $team) {
            $members = $this->getTeamMembers($team);
            if (empty($members)) {
                continue;
            }

            $publication = $this->getPublications($team, $period);
            $logs = $this->getTeamLogs($team, $period);
            if (empty($publication) && empty($logs)) {
                continue;
            }

            /// we send an email to every MAX_EMAIL_BCC people
            $iterator = 0;
            $mail = $this->loadMail($team->name, $publication, $logs);
            foreach ($members as $member) {
                if (self::MAX_EMAIL_BCC == $iterator) {
                    $mail->send();

                    $mail = $this->loadMail($team->name, $publication, $logs);
                    $iterator = 0;
                } else {
                    $iterator++;
                }

                $mail->addBCC($member->getContact()->email);
            }

            if ($iterator <= self::MAX_EMAIL_BCC) {
                $mail->send();
            }
        }
    }

    /**
     * Build the body for the email.
     * 
     * @param NewMail       $mail
     * @param Publication[] $publications
     * @param WebTeamLog[]  $logs
     */
    protected function buildTableBody(&$mail, $publications, $logs)
    {
        $url = $this->toolBox()->appSettings()->get('webportal', 'url', '');

        /// publications
        if (!empty($publications)) {
            $pubHeaders = [$this->toolBox()->i18n()->trans('publication'), $this->toolBox()->i18n()->trans('date')];
            $pubRows = [];
            foreach ($publications as $pub) {
                $pubRows[] = [
                    '<a href="' . $url . $pub->url('public') . '">' . $pub->title . '</a>',
                    $pub->creationdate
                ];
            }

            $mail->addMainBlock(new TableBlock($pubHeaders, $pubRows));
        }

        /// logs
        if (!empty($logs)) {
            $logHeaders = [$this->toolBox()->i18n()->trans('name'), $this->toolBox()->i18n()->trans('description'), $this->toolBox()->i18n()->trans('date')];
            $logRows = [];
            foreach ($logs as $log) {
                if (empty($log->link)) {
                    $logRows[] = [$log->getContactAlias(), $log->description, $log->time];
                    continue;
                }

                if (substr($log->link, 0, 1) === '/') {
                    $logRows[] = [$log->getContactAlias(), '<a href="' . $url . $log->link . '">' . $log->description . '</a>', $log->time];
                    continue;
                }

                $logRows[] = [$log->getContactAlias(), '<a href="' . $url . '/' . $log->link . '">' . $log->description . '</a>', $log->time];
            }

            $mail->addMainBlock(new TableBlock($logHeaders, $logRows));
        }
    }

    /**
     * Returns team publications and main publications.
     * 
     * @param WebTeam $team
     * @param string  $period
     *
     * @return Publication[]
     */
    protected function getPublications(WebTeam $team, string $period): array
    {
        $publication = new Publication();
        $where = [
            new DataBaseWhere('idteam', $team->idteam),
            new DataBaseWhere('creationdate', date('d-m-Y H:i:s', strtotime('-' . $period)), '>')
        ];
        $teamPublications = $publication->all($where, ['creationdate' => 'DESC'], 0, 0);

        $where2 = [
            new DataBaseWhere('idteam', null),
            new DataBaseWhere('creationdate', date('d-m-Y H:i:s', strtotime('-' . $period)), '>')
        ];
        $mainPublications = $publication->all($where2, ['creationdate' => 'DESC'], 0, 0);

        return array_merge($teamPublications, $mainPublications);
    }

    /**
     * 
     * @param WebTeam $team
     * @param string  $period
     *
     * @return WebTeamLog[]
     */
    protected function getTeamLogs(WebTeam $team, string $period): array
    {
        $teamLog = new WebTeamLog();
        $where = [
            new DataBaseWhere('idteam', $team->idteam),
            new DataBaseWhere('time', date('d-m-Y H:i:s', strtotime('-' . $period)), '>')
        ];

        return $teamLog->all($where, ['time' => 'DESC'], 0, 0);
    }

    /**
     * 
     * @param WebTeam $team
     *
     * @return WebTemMember[]
     */
    protected function getTeamMembers(WebTeam $team)
    {
        $memberTeam = new WebTeamMember();
        $where = [
            new DataBaseWhere('idteam', $team->idteam),
            new DataBaseWhere('accepted', true),
        ];

        return $memberTeam->all($where, [], 0, 0);
    }

    /**
     * Create and load new object Mail.
     *
     * @param string $teamName
     * @param Publication[] $publications
     * @param WebTeamLog    $logs
     *
     * @return NewMail
     */
    protected function loadMail($teamName, $publications, $logs)
    {
        $mail = new NewMail();
        $mail->fromName = $this->toolBox()->appSettings()->get('webportal', 'title');
        $mail->title = $mail->text = $this->toolBox()->i18n()->trans('weekly-report', ['%teamName%' => $teamName]);
        $this->buildTableBody($mail, $publications, $logs);
        return $mail;
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
