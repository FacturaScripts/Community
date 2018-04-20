<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Plugins\Community\Model\WebDocPage;
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

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'web-doc-page';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fa-folder';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadWebDocPage();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        if (null === $this->contact) {
            $this->setTemplate('Master/LoginToContinue');
        } else {
            $this->loadWebDocPage();
        }
    }

    private function loadWebDocPage()
    {
        $this->setTemplate('EditWebDocPage');

        $body = $this->request->request->get('body', '');
        $code = $this->request->get('code');
        $idparent = $this->request->get('idparent');
        $idproject = $this->request->get('idproject');
        $langcode = $this->request->get('langcode');
        $title = $this->request->request->get('title', '');

        $this->webDocPage = new WebDocPage();

        /// loads doc page
        if (!$this->webDocPage->loadFromCode($code)) {
            /// if it's a new doc page, then use the idproject and langcode
            $this->webDocPage->idproject = $idproject;
            $this->webDocPage->langcode = $langcode;

            if (!empty($idparent)) {
                $this->newChildrenPage($idparent);
            }

            $this->webDocPage->idcontacto = is_null($this->contact) ? null : $this->contact->idcontacto;
        }

        if ('' !== $title) {
            $this->webDocPage->idparent = empty($idparent) ? null : $idparent;
            $this->webDocPage->title = $title;
            $this->webDocPage->body = $body;
            if ($this->webDocPage->save()) {
                $this->miniLog->info($this->i18n->trans('save-ok'));
            } else {
                $this->miniLog->alert($this->i18n->trans('save-error'));
            }
        }
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
}
