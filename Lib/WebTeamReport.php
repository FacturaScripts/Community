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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Plugins\Community\Model\Publication;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use PHPMailer\PHPMailer\PHPMailer;

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

    /**
     *
     * @var EmailTools
     */
    protected $emailTools;

    /**
     *
     * @var Translator
     */
    protected $i18n;

    public function __construct()
    {
        $this->emailTools = new EmailTools();
        $this->i18n = new Translator();
    }

    /**
     * 
     * @param string $period
     */
    public function sendMail($period)
    {
        $teamModel = new WebTeam();
        $memberTeam = new WebTeamMember();

        foreach ($teamModel->all([], [], 0, 0) as $team) {
            $where = [
                new DataBaseWhere('idteam', $team->idteam),
                new DataBaseWhere('accepted', true),
            ];
            $members = $memberTeam->all($where, [], 0, 0);
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
                    $this->emailTools->send($mail);
                    $mail = $this->loadMail($team->name, $publication, $logs);
                    $iterator = 0;
                } else {
                    $iterator++;
                }

                $mail->addBCC($member->getContact()->email);
            }

            if ($iterator <= self::MAX_EMAIL_BCC) {
                $this->emailTools->send($mail);
            }
        }
    }

    /**
     * Build the body of the tabla for the email.
     * 
     * @param string $title
     * @param Publication[] $publications
     * @param WebTeamLog[]  $logs
     *
     * @return string
     */
    protected function buildTableBody($title, $publications, $logs): string
    {
        $content = '';
        $url = AppSettings::get('webportal', 'url', '');

        /// publications
        if (!empty($publications)) {
            $content .= '<h1>' . $this->i18n->trans('publications') . '</h1>'
                . '<ul>';
            foreach ($publications as $pub) {
                $content .= '<li><a href="' . $url . $pub->url('public') . '">' . $pub->title . '</a></li>';
            }
            $content .= '</ul>';
        }

        /// logs
        if (!empty($logs)) {
            $content .= '<h2>' . $this->i18n->trans('logs') . '</h2>'
                . '<ul>';
            foreach ($logs as $log) {
                $content .= '<li>';
                if (empty($log->link)) {
                    $content .= $log->description . ' - ' . $log->time;
                } elseif (substr($log->link, 0, 1) === '/') {
                    $content .= '<a href="' . $url . $log->link . '">' . $log->description . '</a> - ' . $log->time;
                } else {
                    $content .= '<a href="' . $url . '/' . $log->link . '">' . $log->description . '</a> - ' . $log->time;
                }

                $content .= ' - ' . $log->getContactAlias() . '</li>';
            }
            $content .= '</ul>';
        }

        $params = [
            'body' => $content,
            'company' => $title,
            'footer' => $title,
            'title' => $title,
        ];
        return $this->emailTools->getTemplateHtml($params);
    }

    /**
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

        return $publication->all($where, ['creationdate' => 'DESC'], 0, 0);
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
     * Create and load new object Mail.
     *
     * @param string $teamName
     * @param Publication[] $publications
     * @param WebTeamLog    $logs
     *
     * @return PHPMailer
     */
    protected function loadMail($teamName, $publications, $logs)
    {
        $mail = $this->emailTools->newMail();
        $mail->Subject = $this->i18n->trans('weekly-report', ['%teamName%' => $teamName]);
        $mail->msgHTML($this->buildTableBody($mail->Subject, $publications, $logs));

        return $mail;
    }
}
