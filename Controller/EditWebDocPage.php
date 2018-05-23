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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;

/**
 * Description of EditWebDocPage controller.
 *
 * @author Carlos García Gómez
 */
class EditWebDocPage extends PortalController
{

    /**
     *
     * @var WebDocPage
     */
    public $webDocPage;

    public function getProjectDocPages()
    {
        $where = [
            new DataBaseWhere('idproject', $this->webDocPage->idproject),
            new DataBaseWhere('langcode', $this->webDocPage->langcode)
        ];

        if (null !== $this->webDocPage->iddoc) {
            $where[] = new DataBaseWhere('iddoc', $this->webDocPage->iddoc, '!=');
        }

        return $this->webDocPage->all($where, ['ordernum' => 'ASC'], 0, 0);
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadWebDocPage();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);

        /// can this contact edit this page?
        $continue = false;
        $idteamdoc = AppSettings::get('community', 'idteamdoc', '');
        if (null === $this->contact) {
            $this->setTemplate('Master/LoginToContinue');
        } elseif (!empty($idteamdoc)) {
            $member = new WebTeamMember();
            $where = [
                new DataBaseWhere('idteam', $idteamdoc),
                new DataBaseWhere('accepted', true)
            ];

            if ($member->loadFromCode('', $where)) {
                $continue = true;
            } else {
                $team = $member->getTeam();
                $this->miniLog->alert($this->i18n->trans('join-team', ['%team%' => $team->name]));
            }
        }

        if ($continue) {
            $this->loadWebDocPage();
        }
    }

    private function loadWebDocPage()
    {
        $this->setTemplate('EditWebDocPage');

        $code = $this->request->get('code', '');
        $idparent = $this->request->get('idparent');
        $title = $this->request->request->get('title', '');
        $this->webDocPage = new WebDocPage();

        /// loads doc page
        if (!$this->webDocPage->loadFromCode($code)) {
            /// if it's a new doc page, then use the idproject and langcode
            $this->webDocPage->idproject = $this->request->get('idproject', $this->webDocPage->idproject);
            $this->webDocPage->langcode = $this->request->get('langcode', $this->webDocPage->langcode);

            if (!empty($idparent)) {
                $this->newChildrenPage($idparent);
            }
        }

        if ('' !== $title) {
            $this->saveDoc($idparent, $title);
        }

        $this->title = $this->webDocPage->title . ' - ' . $this->i18n->trans('edit');
    }

    private function newChildrenPage(int $idparent)
    {
        $parentDocPage = $this->webDocPage->get($idparent);
        if ($parentDocPage) {
            $this->webDocPage->idparent = $idparent;
            $this->webDocPage->idproject = $parentDocPage->idproject;
            $this->webDocPage->langcode = $parentDocPage->langcode;
        }
    }

    private function saveDoc($idparent, $title)
    {
        $this->webDocPage->body = $this->request->request->get('body', '');
        $this->webDocPage->idparent = empty($idparent) ? null : $idparent;
        $this->webDocPage->ordernum = (int) $this->request->get('ordernum', '100');
        $this->webDocPage->title = $title;
        if ($this->webDocPage->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            $this->saveTeamLog();
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    private function saveTeamLog()
    {
        $idteamdoc = AppSettings::get('community', 'idteamdoc', '');
        if (empty($idteamdoc)) {
            return;
        }

        $teamLog = new WebTeamLog();
        $teamLog->description = 'Modified documentation page: ' . $this->webDocPage->title;
        $teamLog->idteam = $idteamdoc;
        $teamLog->idcontacto = is_null($this->contact) ? null : $this->contact->idcontacto;
        $teamLog->link = $this->webDocPage->url('link');
        $teamLog->save();
    }
}
