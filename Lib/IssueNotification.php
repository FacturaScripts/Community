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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Lib\Email\ButtonBlock;
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
        $i18n = static::toolBox()->i18n();
        $link = static::toolBox()->appSettings()->get('webportal', 'url', '') . $issue->url('public');

        $mail = new NewMail();
        $mail->fromName = static::toolBox()->appSettings()->get('webportal', 'title');
        $mail->title = $issue->title() . ' de ' . $issue->getContactAlias();
        $mail->text = $issue->description();
        $mail->addMainBlock(new ButtonBlock($i18n->trans('read-more'), $link));

        static::addTeamEmails($mail, $issue);
        $mail->send();
    }

    /**
     * 
     * @param IssueComment $comment
     */
    public static function notifyComment(&$comment)
    {
        $i18n = static::toolBox()->i18n();
        $issue = $comment->getIssue();
        $contact = $issue->getContact();
        $link = static::toolBox()->appSettings()->get('webportal', 'url', '') . $issue->url('public');

        $mail = new NewMail();
        $mail->fromName = static::toolBox()->appSettings()->get('webportal', 'title');
        $mail->title = $issue->title() . ': comentario de ' . $comment->getContactAlias();
        $mail->text = '<b>' . $issue->title() . '</b><br/>' . $issue->description()
            . '<br/><br/>'
            . '<b>Comentario de ' . $comment->getContactAlias() . '</b><br/>'
            . $comment->resume(60);
        $mail->addMainBlock(new ButtonBlock($i18n->trans('read-more'), $link));

        if ($issue->lastcommidcontacto === $comment->idcontacto) {
            /// don't notify
            return;
        } elseif ($issue->idcontacto !== $comment->idcontacto) {
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
     * @param NewMail $mail
     * @param Issue   $issue
     */
    protected static function addTeamEmails(&$mail, &$issue)
    {
        $memberModel = new WebTeamMember();
        $where = [
            new DataBaseWhere('idteam', $issue->idteam),
            new DataBaseWhere('idcontacto', $issue->idcontacto, '!='),
            new DataBaseWhere('accepted', true),
        ];
        foreach ($memberModel->all($where, [], 0, 0) as $member) {
            $memberContact = $member->getContact();
            $mail->addBCC($memberContact->email, $memberContact->fullName());
        }
    }

    /**
     * 
     * @return ToolBox
     */
    protected static function toolBox()
    {
        return new ToolBox();
    }
}
