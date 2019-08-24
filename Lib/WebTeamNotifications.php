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

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\Community\Model\Publication;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;

/**
 * Description of WebTeamNotifications
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WebTeamNotifications
{

    /**
     * Notify to member that was accepted to team.
     *
     * @param WebTeamMember $member
     */
    public static function notifyAccept(WebTeamMember $member)
    {
        $contact = $member->getContact();
        $team = $member->getTeam();
        $link = static::toolBox()->appSettings()->get('webportal', 'url', '') . $team->url('public');
        $title = static::toolBox()->i18n()->trans('accepted-to-team', ['%teamName%' => $team->name]);
        $txt = static::toolBox()->i18n()->trans(
            'accepted-to-team-msg',
            ['%link%' => $link, '%teamName%' => $team->name, '%teamDescription%' => $team->description]
        );

        $publication = new Publication();
        if (!empty($team->defaultpublication) && $publication->loadFromCode($team->defaultpublication)) {
            $url = static::toolBox()->appSettings()->get('webportal', 'url', '');
            $txt .= "<br/><br/><a href='" . $url . '/' . $publication->url('public') . "'>" . $publication->title . "</a>"
                . "<br/>" . $publication->description();
        }

        static::notifySend($contact, $title, $txt);
    }

    /**
     * Notify to member that was accepted to team.
     *
     * @param WebTeamMember $member
     */
    public static function notifyExpel(WebTeamMember $member)
    {
        $contact = $member->getContact();
        $team = $member->getTeam();
        $link = static::toolBox()->appSettings()->get('webportal', 'url', '') . $team->url('public');
        $title = static::toolBox()->i18n()->trans('expelled-from-team', ['%teamName%' => $team->name]);
        $txt = static::toolBox()->i18n()->trans('expelled-from-team-msg', ['%link%' => $link, '%teamName%' => $team->name]);

        static::notifySend($contact, $title, $txt);
    }

    /**
     * 
     * @param Contacto $contact
     * @param string   $title
     * @param string   $txt
     */
    protected static function notifySend($contact, $title, $txt)
    {
        $mail = new NewMail();
        $mail->fromName = static::toolBox()->appSettings()->get('webportal', 'title');
        $mail->addAddress($contact->email, $contact->fullName());
        $mail->title = $title;
        $mail->text = $txt;
        $mail->send();
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
