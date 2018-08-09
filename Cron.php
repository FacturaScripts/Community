<?php
namespace FacturaScripts\Plugins\Community;

use FacturaScripts\Core\Base\CronClass;
use FacturaScripts\Core\Lib\EmailTools;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Dinamic\Model\Contacto;

/**
 * Define the taks of Community's crons.
 * 
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class Cron extends CronClass
{
    const PLUGIN_NAME = 'Community';

    /**
     * Quantity of contact to simulate pagination to send email.
     */
    const QUANTITY_CONTACT = 50;

    public function run()
    {
        if ($this->isTimeForJob(self::PLUGIN_NAME, 'send-reports-email', '1 week')) {
            $team = new WebTeam();
            $teams = $team->all([], [], 0, 0);

            foreach ($teams as $team) {
                $memberTeam = new WebTeamMember();
                $members = $memberTeam->all([new DataBaseWhere('idteam', $team->idteam)], [], 0, 0);

                if (!empty($members)) {
                    $teamLogs = new WebTeamLog();
                    $logs = $teamLogs->all([new DataBaseWhere('idteam', $team->idteam)], [], 0, 0);

                    $emailTools = new EmailTools();

                    $mail = $this->loadMail($emailTools, $team->name, $logs);

                    $iterator = 0;
                    foreach ($members as $member) {
                        if (self::QUANTITY_CONTACT == $iterator) {
                            $emailTools->send($mail);
                            $mail = $this->loadMail($emailTools, $team->name, $logs);
                            $iterator = 0;
                        } else {
                            $iterator++;
                        }
                        $mail->addBCC($member->getContact()->email);
                    }

                    if ($iterator <= self::QUANTITY_CONTACT) {
                        $emailTools->send($mail);
                    }
                }
            }
            
            $this->jobDone(self::PLUGIN_NAME, 'send-reports-email');
        }
    }

    /**
     * Create and load new object Mail.
     *
     * @param EmailTools $emailTools
     * @param string $nameTeam
     * @param array $logs Array of WebTeamLog objects.
     * @return void
     */
    private function loadMail($emailTools, $nameTeam, $logs)
    {
        $mail = $emailTools->newMail();

        $mail->Subject = self::$i18n->trans('weekly-report-FacturaScript-' . $nameTeam);

        $mail->msgHTML($this->buildTableBody($logs));

        return $mail;
    }

    /**
     * Build the body of the tabla for the email.
     *
     * @param array Array of WebTeamLog objects.
     * @return string
     */
    private function buildTableBody(array $logs) : string
    { 
        $content = '<table style="text-align: left;">';
        $content .= '<tr><td style="padding:5px;">' . self::$i18n->trans('name') . '</td>';
        $content .= '<td style="padding:5px;">' . self::$i18n->trans('description') . '</td>';
        $content .= '<td style="padding:5px;">' . self::$i18n->trans('date') . '</td></tr>';
        foreach ($logs as $log) {
            $contact = new Contacto();
            $contact->loadFromCode($log->idcontacto);

            $content .= '<tr><th style="padding:5px;"><a href="' . $log->link . '">' . self::$i18n->trans($log->description) . '</a></th>';
            $content .= '<th style="padding:5px;">' . $log->time . '</th>';
            $content .= '<th style="padding:5px;">' . $contact->nombre . '</th></tr>';
        }
        return $content .= '</table>';
    }
}