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
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of EditWebTeam
 *
 * @author Carlos García Gómez
 */
class EditWebTeam extends SectionController
{

    public function contactCanEdit(): bool
    {
        if ($this->user) {
            return true;
        }

        if (null === $this->contact) {
            return false;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $this->request->get('code', '')),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
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
        }
    }

    protected function createSections()
    {
        $this->addSection('team', [
            'fixed' => true,
            'model' => new WebTeam(),
            'template' => 'Section/Team.html.twig',
        ]);

        $this->addListSection('members', 'WebTeamMember', 'Section/TeamMembers', 'members', 'fa-users');
        $this->addListSection('requests', 'WebTeamMember', 'Section/TeamRequests', 'requests', 'fa-address-card');
    }

    protected function editAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return;
        }

        $code = $this->request->get('code', '');
        $team = new WebTeam();
        if (!empty($code) && $team->loadFromCode($code)) {
            $team->description = $this->request->get('description', '');
            if ($team->save()) {
                $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
            } else {
                $this->miniLog->alert($this->i18n->trans('record-save-error'));
            }
        }
    }

    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'accept-request':
                $this->acceptAction();
                break;

            case 'edit':
                $this->editAction();
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

        $member = new WebTeamMember();
        $member->idcontacto = $this->contact->idcontacto;
        $member->idteam = $this->request->get('code', '');
        if ($member->save()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    protected function leaveAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $this->request->get('code', '')),
        ];

        if (!$member->loadFromCode('', $where)) {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return;
        }

        if ($member->delete()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
        }
    }

    protected function loadData($sectionName)
    {
        switch ($sectionName) {
            case 'team':
                $code = $this->request->get('code', '');
                $team = new WebTeam();
                if (!empty($code) && $team->loadFromCode($code)) {
                    $this->sections[$sectionName]['cursor'] = [$team];
                    $this->title = $team->name;
                    $this->description = $team->description();
                } else {
                    $this->miniLog->alert($this->i18n->trans('no-data'));
                    $this->webPage->noindex = true;
                }
                break;

            case 'members':
                $where = [
                    new DataBaseWhere('idteam', $this->request->get('code', '')),
                    new DataBaseWhere('accepted', true),
                ];
                $this->loadListSection($sectionName, $where);
                break;

            case 'requests':
                $where = [
                    new DataBaseWhere('idteam', $this->request->get('code', '')),
                    new DataBaseWhere('accepted', false),
                ];
                $this->loadListSection($sectionName, $where);
                break;
        }
    }
}
