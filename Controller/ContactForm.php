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
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\Community\Model\ContactFormTree;
use FacturaScripts\Plugins\Community\Model\WebProject;

/**
 * Description of ContactForm
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ContactForm extends PortalController
{

    const SEND_ISSUE_CONTROLLER = 'AddIssue';

    /**
     *
     * @var ContactFormTree
     */
    public $currentTree;

    /**
     *
     * @var array
     */
    public $endActions = [];

    /**
     *
     * @var ContactFormTree[]
     */
    public $formTrees;

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

    protected function addEndAction(string $title, string $link = '', string $icon = 'fa-circle-o', string $observations = '')
    {
        $this->endActions[] = [
            'icon' => $icon,
            'link' => empty($link) ? self::SEND_ISSUE_CONTROLLER . '?idtree=' . $this->currentTree->idtree : $link,
            'observations' => $observations,
            'title' => $title,
        ];
    }

    protected function commonCore()
    {
        if (null === $this->contact) {
            $this->setTemplate('Master/LoginToContinue');
            return;
        }

        $this->setTemplate('ContactForm');
        $code = $this->request->get('code', '');
        if (empty($code)) {
            $this->getRoot();
        } else {
            $this->getCurrentTree($code);
        }
    }

    protected function getCurrentTree(string $code)
    {
        $this->currentTree = new ContactFormTree();
        if (!$this->currentTree->loadFromCode($code)) {
            $this->miniLog->alert($this->i18n->trans('no-data'));
            $this->getRoot();
            return;
        }

        $this->formTrees = $this->currentTree->getChildrenPages();
        $this->currentTree->increaseVisitCount($this->request->getClientIp());
        switch ($this->currentTree->endaction) {
            case 'send-issue':
                $this->response->headers->set('Refresh', '0; ' . self::SEND_ISSUE_CONTROLLER . '?idtree=' . $this->currentTree->idtree);
                break;

            case 'select-plugin':
                $this->selectPlugin();
                break;
        }
    }

    protected function getRoot()
    {
        $formTree = new ContactFormTree();
        $where = [new DataBaseWhere('idparent', null, 'IS')];
        $this->formTrees = $formTree->all($where, ['ordernum' => 'ASC']);
    }

    protected function selectPlugin()
    {
        $pluginProject = new WebProject();
        $where = [new DataBaseWhere('plugin', true)];
        foreach ($pluginProject->all($where, ['name' => 'ASC'], 0, 0) as $plugin) {
            $link = self::SEND_ISSUE_CONTROLLER . '?idtree=' . $this->currentTree->idtree . '&idproject=' . $plugin->idproject;
            $this->addEndAction($plugin->name, $link, 'fa-plug');
        }
    }
}
