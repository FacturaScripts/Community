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
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditWebTeam
 *
 * @author Carlos García Gómez
 */
class EditWebTeam extends SectionController
{

    /**
     *
     * @var WebTeam
     */
    protected $team;

    public function contactCanEdit(): bool
    {
        if ($this->user) {
            return true;
        }

        if (null === $this->contact) {
            return false;
        }

        $member = new WebTeamMember();
        $team = $this->getTeam();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $team->idteam),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    public function getTeam(): WebTeam
    {
        if (isset($this->team)) {
            return $this->team;
        }

        $team = new WebTeam();
        $idteam = $this->request->get('code', '');
        if (!empty($idteam)) {
            $team->loadFromCode($idteam);
            return $team;
        }

        $uri = explode('/', $this->uri);
        $team->loadFromCode('', [new DataBaseWhere('name', end($uri))]);
        return $team;
    }

    public function showJoinButton(): bool
    {
        if (null === $this->contact) {
            return false;
        }

        $member = new WebTeamMember();
        $team = $this->getTeam();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $team->idteam),
        ];

        return !$member->loadFromCode('', $where);
    }

    protected function acceptAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return;
        }

        $idrequest = $this->request->get('idrequest', '');
        $member = new WebTeamMember();
        if ('' === $idrequest || !$member->loadFromCode($idrequest)) {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return;
        }

        $member->accepted = true;
        if ($member->save()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));

            $nick = is_null($this->contact) ? $this->user->nick : $this->contact->fullName();
            $teamLog = new WebTeamLog();
            $teamLog->description = 'Accepted as new member by ' . $nick . '.';
            $teamLog->idcontacto = $member->idcontacto;
            $teamLog->idteam = $member->idteam;
            $teamLog->save();
        }
    }

    protected function createSections()
    {
        $this->addSection('team', [
            'fixed' => true,
            'template' => 'Section/Team.html.twig',
        ]);

        $this->addListSection('logs', 'WebTeamLog', 'Section/TeamLogs', 'logs', 'fa-file-text-o');
        $this->addSearchOptions('logs', ['description']);
        $this->addOrderOption('logs', 'time', 'date', 2);

        $this->addListSection('members', 'WebTeamMember', 'Section/TeamMembers', 'members', 'fa-users');
        $this->addOrderOption('members', 'creationdate', 'date', 2);

        $this->addListSection('requests', 'WebTeamMember', 'Section/TeamMembers', 'requests', 'fa-address-card');
        $this->addOrderOption('requests', 'creationdate', 'date', 2);
    }

    protected function editAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return;
        }

        $this->team->description = $this->request->get('description', '');
        if ($this->team->save()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    protected function execAfterAction(string $action)
    {
        switch ($action) {
            case 'accept-request':
            case 'join':
            case 'leave':
                /// we force save to update number of members and requests
                $this->team->save();
                break;

            case 'edit':
                $this->editAction();
                break;
        }
    }

    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'accept-request':
                $this->acceptAction();
                break;

            case 'join':
                $this->joinAction();
                break;

            case 'leave':
                $this->leaveAction();
                break;
        }

        return true;
    }

    protected function joinAction()
    {
        if (null === $this->contact) {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return;
        }

        $team = $this->getTeam();
        $member = new WebTeamMember();
        $member->idcontacto = $this->contact->idcontacto;
        $member->idteam = $team->idteam;
        if ($member->save()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
            $teamLog = new WebTeamLog();
            $teamLog->idcontacto = $member->idcontacto;
            $teamLog->idteam = $member->idteam;
            $teamLog->description = $member->getContactName() . ' wants to be member of this team.';
            $teamLog->save();
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    protected function leaveAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return;
        } elseif (empty($this->contact)) {
            return;
        }

        $member = new WebTeamMember();
        $team = $this->getTeam();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $team->idteam),
        ];

        if (!$member->loadFromCode('', $where)) {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return;
        }

        if ($member->delete()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
            $teamLog = new WebTeamLog();
            $teamLog->description = 'Leaves this team.';
            $teamLog->idcontacto = $member->idcontacto;
            $teamLog->idteam = $member->idteam;
            $teamLog->save();
        }
    }

    protected function loadData(string $sectionName)
    {
        $team = $this->getTeam();
        switch ($sectionName) {
            case 'logs':
                $where = [new DataBaseWhere('idteam', $team->idteam)];
                $this->loadListSection($sectionName, $where);
                break;

            case 'members':
                $where = [
                    new DataBaseWhere('idteam', $team->idteam),
                    new DataBaseWhere('accepted', true),
                ];
                $this->loadListSection($sectionName, $where);
                break;

            case 'requests':
                $where = [
                    new DataBaseWhere('idteam', $team->idteam),
                    new DataBaseWhere('accepted', false),
                ];
                $this->loadListSection($sectionName, $where);
                break;

            case 'team':
                $this->loadTeam();
                break;
        }
    }

    protected function loadTeam()
    {
        $this->team = $this->getTeam();
        if ($this->team->exists()) {
            $this->title = $this->team->name;
            $this->description = $this->team->description();
            return;
        }

        $this->miniLog->alert($this->i18n->trans('no-data'));
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->webPage->noindex = true;
    }
}
