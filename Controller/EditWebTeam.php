<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

    protected function commonCore()
    {
        parent::commonCore();
        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    protected function acceptAction()
    {
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
        $this->addListSection('team', 'WebTeam', 'Section/Team', 'team', 'fa-users');
        $this->addListSection('members', 'WebTeamMember', 'Section/TeamMembers', 'members', 'fa-users');
        $this->addListSection('requests', 'WebTeamMember', 'Section/TeamRequests', 'requests', 'fa-users');
    }

    protected function execAction(string $action)
    {
        switch ($action) {
            case 'accept-request':
                $this->acceptAction();
                break;

            case 'join':
                $this->joinAction();
                break;
        }
    }

    protected function joinAction()
    {
        if (null === $this->contact) {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return;
        }

        $member = new WebTeamMember();
        $member->idcontacto = $this->contact->idcontacto;
        $member->idteam = $this->sections['team']['cursor'][0]->idteam;
        if ($member->save()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
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
