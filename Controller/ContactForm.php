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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\Community\Model\ContactFormTree;
use FacturaScripts\Plugins\Community\Model\WebProject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ContactForm
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ContactForm extends PortalController
{

    const SEND_ISSUE_CONTROLLER = 'AddIssue';

    /**
     * The current contact form tree.
     *
     * @var ContactFormTree
     */
    public $currentTree;

    /**
     * A list of end actions.
     *
     * @var array
     */
    public $endActions = [];

    /**
     * A list of contact form tree.
     *
     * @var ContactFormTree[]
     */
    public $formTrees;

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['title'] = 'contact';

        return $data;
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
        $this->commonCore();
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->commonCore();
    }

    /**
     * TODO: Undocumented function
     *
     * @param string $title
     * @param string $link
     * @param string $icon
     * @param string $observations
     */
    protected function addEndAction(string $title, string $link = '', string $icon = 'fas fa-circle-o', string $observations = '')
    {
        $this->endActions[] = [
            'icon' => $icon,
            'link' => empty($link) ? self::SEND_ISSUE_CONTROLLER . '?idtree=' . $this->currentTree->idtree : $link,
            'observations' => $observations,
            'title' => $title,
        ];
    }

    /**
     * Execute common code between private and public core.
     */
    protected function commonCore()
    {
        if (empty($this->contact)) {
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

    /**
     * Get full tree for current item.
     *
     * @param string $code
     */
    protected function getCurrentTree(string $code)
    {
        $this->currentTree = new ContactFormTree();
        if (!$this->currentTree->loadFromCode($code)) {
            $this->miniLog->alert($this->i18n->trans('no-data'));
            $this->getRoot();
            return;
        }

        $ipAddress = is_null($this->request->getClientIp()) ? '::1' : $this->request->getClientIp();
        $this->currentTree->increaseVisitCount($ipAddress);

        $this->formTrees = $this->currentTree->getChildrenPages();
        switch ($this->currentTree->endaction) {
            case 'send-issue':
                $this->response->headers->set('Refresh', '0; ' . self::SEND_ISSUE_CONTROLLER . '?idtree=' . $this->currentTree->idtree);
                break;

            case 'select-plugin':
                $this->selectPlugin();
                break;
        }
    }

    /**
     * Get root contact form tree items.
     */
    protected function getRoot()
    {
        $formTree = new ContactFormTree();
        $where = [new DataBaseWhere('idparent', null, 'IS')];
        $this->formTrees = $formTree->all($where, ['ordernum' => 'ASC']);
    }

    /**
     * TODO: Undocumented function
     */
    protected function selectPlugin()
    {
        $pluginProject = new WebProject();
        $where = [new DataBaseWhere('plugin', true)];
        foreach ($pluginProject->all($where, ['name' => 'ASC'], 0, 0) as $plugin) {
            $link = self::SEND_ISSUE_CONTROLLER . '?idtree=' . $this->currentTree->idtree . '&idproject=' . $plugin->idproject;
            $this->addEndAction($plugin->name, $link, 'fas fa-plug');
        }
    }
}
