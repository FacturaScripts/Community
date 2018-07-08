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
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\Community\Model\ContactFormTree;
use FacturaScripts\Plugins\Community\Model\Issue;
use FacturaScripts\Plugins\Community\Model\WebProject;

/**
 * Description of AddIssue
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AddIssue extends PortalController
{

    /**
     *
     * @var Issue
     */
    public $issue;

    public function getTreeList()
    {
        $list = [];

        $idtree = $this->request->get('idtree', '');
        $contactFormTree = new ContactFormTree();
        if (!empty($idtree) && $contactFormTree->loadFromCode($idtree)) {
            array_unshift($list, $contactFormTree->title);
            $parent = $contactFormTree->getParentPage();
            while ($parent && $parent->exists()) {
                array_unshift($list, $parent->title);
                $parent = $parent->getParentPage();
            }
        }

        $idproject = $this->request->get('idproject', '');
        $pluginProject = new WebProject();
        if (!empty($idproject) && $pluginProject->loadFromCode($idproject)) {
            $list[] = $pluginProject->name;
        }

        return $list;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->commonCore();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->commonCore();
    }

    protected function commonCore()
    {
        $this->setTemplate('AddIssue');
        $this->issue = new Issue();

        $body = $this->request->get('body', '');
        if (!empty($body)) {
            $this->issue->body = $body;
            $this->issue->creationroute = implode(', ', $this->getTreeList());
            $this->issue->idcontacto = $this->contact->idcontacto;
            $this->issue->idproject = $this->request->get('idproject');
            $this->issue->idteam = AppSettings::get('community', 'idteamsup');
            $this->issue->idtree = $this->request->get('idtree');
            
            if ($this->issue->save()) {
                $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                $this->response->headers->set('Refresh', '0; ' . $this->issue->url('public'));
            } else {
                $this->miniLog->alert($this->i18n->trans('record-save-error'));
            }
        }
    }
}
