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
use FacturaScripts\Plugins\Community\Lib\WebPortal\PortalControllerWizard;

/**
 * Description of EditWebDocPage controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditWebDocPage extends PortalControllerWizard
{

    /**
     *
     * @var string
     */
    protected $idteamdoc;

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

    protected function commonCore()
    {
        /// contact is in doc team?
        $this->idteamdoc = AppSettings::get('community', 'idteamdoc', '');
        if (!$this->contactInTeam($this->idteamdoc)) {
            $this->contactNotInTeamError($this->idteamdoc);
            return;
        }

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
     * Code for delete action.
     */
    private function deleteAction()
    {
        if ($this->webDocPage->exists() && $this->webDocPage->delete()) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            $description = 'Deleted documentation page: ' . $this->webDocPage->title;
            $this->saveTeamLog($this->idteamdoc, $description, '');
            return;
        }

        $this->miniLog->alert($this->i18n->trans('record-delete-error'));
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
     * @param string $idParent
     * @param string $title
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

            /// we only save a log the first time this doc is modified today
            if (date('d-m-Y') != $previousMod) {
                $description = 'Modified documentation page: ' . $this->webDocPage->title;
                $link = $this->webDocPage->url('public');
                $this->saveTeamLog($this->idteamdoc, $description, $link);
            }
            return;
        }

        $this->miniLog->alert($this->i18n->trans('record-save-error'));
    }
}
