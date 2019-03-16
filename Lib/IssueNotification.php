<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Plugins\Community\Model\Issue;
use FacturaScripts\Plugins\Community\Model\IssueComment;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;

/**
 * Description of IssueNotification
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class IssueNotification
{

    /**
     * 
     * @param Issue $issue
     */
    public static function notify(&$issue)
    {
        $contact = $issue->getContact();
        $link = AppSettings::get('webportal', 'url', '') . $issue->url('public');
        $title = 'Issue #' . $issue->idissue . ' de ' . $contact->alias();
        $txt = '<h4>' . $issue->title() . '</h4>'
            . '<p>' . nl2br($issue->description()) . ' - <a href="' . $link . '">Leer más...</a></p>';

        $params = [
            'body' => $txt,
            'company' => AppSettings::get('webportal', 'title'),
            'footer' => AppSettings::get('webportal', 'copyright'),
            'title' => $title,
        ];

        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        $mail->msgHTML($emailTools->getTemplateHtml($params));
        $mail->Subject = $title;
        static::addTeamEmails($mail, $issue);
        $mail->send();
    }

    /**
     * 
     * @param IssueComment $comment
     */
    public static function notifyComment(&$comment)
    {
        $issue = new Issue();
        $issue->loadFromCode($comment->idissue);
        $contact = $issue->getContact();
        $link = AppSettings::get('webportal', 'url', '') . $issue->url('public');
        $title = 'Issue #' . $issue->idissue . ': comentario de ' . $comment->getContactAlias();
        $txt = '<h4>' . $issue->title() . '</h4>'
            . '<p>' . nl2br($issue->description()) . '</p>'
            . '<h4>Comentario de ' . $comment->getContactAlias() . '</h4>'
            . '<p>' . nl2br($comment->resume(60)) . ' - <a href="' . $link . '">Leer más...</a></p>';

        $params = [
            'body' => $txt,
            'company' => AppSettings::get('webportal', 'title'),
            'footer' => AppSettings::get('webportal', 'copyright'),
            'title' => $title,
        ];

        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        $mail->msgHTML($emailTools->getTemplateHtml($params));
        $mail->Subject = $title;

        if ($issue->idcontacto !== $comment->idcontacto) {
            $mail->addAddress($contact->email, $contact->fullName());
            $mail->send();
        } elseif (static::addCommentsOtherEmails($mail, $issue)) {
            $mail->send();
        }
    }

    /**
     * 
     * @param object $mail
     * @param Issue  $issue
     *
     * @return bool
     */
    protected static function addCommentsOtherEmails(&$mail, &$issue)
    {
        $added = false;
        foreach ($issue->getComments() as $comment) {
            if ($comment->idcontacto != $issue->idcontacto) {
                $contact = $comment->getContact();
                $mail->addBCC($contact->email, $contact->fullName());
                $added = true;
            }
        }

        return $added;
    }

    /**
     * 
     * @param object $mail
     * @param Issue  $issue
     */
    protected static function addTeamEmails(&$mail, &$issue)
    {
        $memberModel = new WebTeamMember();
        $where = [new DataBaseWhere('idteam', $issue->idteam)];
        foreach ($memberModel->all($where, [], 0, 0) as $member) {
            $memberContact = $member->getContact();
            $mail->addBCC($memberContact->email, $memberContact->fullName());
        }
    }
}
