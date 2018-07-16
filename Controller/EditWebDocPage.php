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
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditWebDocPage controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditWebDocPage extends PortalController
{

    /**
     * This doc page.
     *
     * @var WebDocPage
     */
    public $webDocPage;

    /**
     * Undo html scape from Utils::noHtml() method, BUT without undo scape of <
     * We do this to prevent html inyections on the markdown javascript editor.
     *
     * @param string $txt
     *
     * @return null|string
     */
    public function fixHtml($txt)
    {
        $original = ['&gt;', '&quot;', '&#39;'];
        $final = ['>', "'", "'"];

        return ($txt === null) ? null : trim(str_replace($original, $final, $txt));
    }

    /**
     * Returns the back url.
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        if ($this->webDocPage->exists()) {
            return $this->webDocPage->url('public');
        }

        $parent = $this->webDocPage->getParentPage();
        if ($parent) {
            return $parent->url('public');
        }

        return $this->webDocPage->url('public-list');
    }

    /**
     * Returns a list of doc pages.
     *
     * @return array
     */
    public function getProjectDocPages(): array
    {
        $where = [
            new DataBaseWhere('idproject', $this->webDocPage->idproject),
            new DataBaseWhere('langcode', $this->webDocPage->langcode)
        ];

        if (null !== $this->webDocPage->iddoc) {
            $where[] = new DataBaseWhere('iddoc', $this->webDocPage->iddoc, '!=');
        }

        return $this->webDocPage->all($where, ['LOWER(title)' => 'ASC'], 0, 0);
    }

    /**
     * * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadWebDocPage();
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
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
                new DataBaseWhere('idcontacto', $this->contact->idcontacto),
                new DataBaseWhere('idteam', $idteamdoc),
                new DataBaseWhere('accepted', true)
            ];

            if ($member->loadFromCode('', $where)) {
                $continue = true;
            } else {
                $team = new WebTeam();
                $team->loadFromCode($idteamdoc);
                $this->miniLog->alert($this->i18n->trans('join-team', ['%team%' => $team->name]));
                $this->setTemplate('Master/AccessDenied');
            }
        }

        if ($continue) {
            $this->loadWebDocPage();
        }
    }

    /**
     * Code for delete action.
     */
    private function deleteAction()
    {
        if ($this->webDocPage->exists() && $this->webDocPage->delete()) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
        }

        $idteamdoc = AppSettings::get('community', 'idteamdoc', '');
        if (empty($idteamdoc)) {
            return;
        }

        $teamLog = new WebTeamLog();
        $teamLog->description = 'Deleted documentation page: ' . $this->webDocPage->title;
        $teamLog->idteam = $idteamdoc;
        $teamLog->idcontacto = is_null($this->contact) ? null : $this->contact->idcontacto;
        $teamLog->save();
    }

    /**
     * Loads the doc page data.
     */
    private function loadWebDocPage()
    {
        $this->setTemplate('EditWebDocPage');

        $code = $this->request->get('code', '');
        $idParent = $this->request->get('idparent');
        $title = $this->request->request->get('title', '');
        $this->webDocPage = new WebDocPage();

        /// loads doc page
        if (!$this->webDocPage->loadFromCode($code)) {
            /// if it's a new doc page, then use the idproject and langcode
            $this->webDocPage->idproject = $this->request->get('idproject', $this->webDocPage->idproject);
            $this->webDocPage->langcode = $this->request->get('langcode', $this->webDocPage->langcode);

            if (!empty($idParent)) {
                $this->newChildrenPage($idParent);
            }
        }

        $action = $this->request->get('action', '');
        switch ($action) {
            case 'delete':
                $this->deleteAction();
                break;

            case 'save':
                $this->saveAction($idParent, $title);
                break;
        }

        $this->title = $this->webDocPage->title . ' - ' . $this->i18n->trans('edit');
    }

    /**
     * Adds a new page as children of another page.
     *
     * @param int $idParent
     */
    private function newChildrenPage(int $idParent)
    {
        $parentDocPage = $this->webDocPage->get($idParent);
        if ($parentDocPage) {
            $this->webDocPage->idparent = $idParent;
            $this->webDocPage->idproject = $parentDocPage->idproject;
            $this->webDocPage->langcode = $parentDocPage->langcode;
        }
    }

    /**
     * Code for save action.
     *
     * @param $idParent
     * @param $title
     */
    private function saveAction($idParent, $title)
    {
        if ('' === $title) {
            return;
        }

        $previousMod = $this->webDocPage->lastmod;

        $this->webDocPage->body = $this->request->request->get('body', '');
        $this->webDocPage->idparent = empty($idParent) ? null : $idParent;
        $this->webDocPage->ordernum = (int) $this->request->get('ordernum', '100');
        $this->webDocPage->title = $title;

        if ($this->webDocPage->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            $this->saveTeamLog($previousMod);
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    /**
     * Store a log detail for the previous modification.
     *
     * @param string $previousMod
     */
    private function saveTeamLog(string $previousMod)
    {
        /// we only save a log the first time this doc is modified today
        if (date('d-m-Y') === $previousMod) {
            return;
        }

        $idteamdoc = AppSettings::get('community', 'idteamdoc', '');
        if (empty($idteamdoc)) {
            return;
        }

        $teamLog = new WebTeamLog();
        $teamLog->description = 'Modified documentation page: ' . $this->webDocPage->title;
        $teamLog->idteam = $idteamdoc;
        $teamLog->idcontacto = is_null($this->contact) ? null : $this->contact->idcontacto;
        $teamLog->link = $this->webDocPage->url('public');
        $teamLog->save();
    }
}
