<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use FacturaScripts\Plugins\webportal\Model\WebPage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of WebDocumentation
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WebDocumentation extends PortalController
{

    /**
     * This project.
     *
     * @var WebProject
     */
    public $currentProject;

    /**
     * The default project ID.
     *
     * @var mixed
     */
    public $defaultIdproject;

    /**
     * Contains the index of the project.
     *
     * @var array
     */
    public $docIndex;

    /**
     * This documentation page.
     *
     * @var WebDocPage
     */
    public $docPage;

    /**
     * A list of doc pages.
     *
     * @var WebDocPage[]
     */
    public $docPages;

    /**
     * A list of projects.
     *
     * @var WebProject[]
     */
    public $projects;

    /**
     * The prefix to use in the url.
     *
     * @var string
     */
    public $urlPrefix;

    /**
     * Return the project url.
     *
     * @param WebProject $project
     *
     * @return string
     */
    public function getProjectUrl(WebProject $project): string
    {
        if ($project->idproject == $this->defaultIdproject) {
            return $this->urlPrefix;
        }

        return $this->urlPrefix . '/' . $project->idproject;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadData();
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->loadData();
    }

    /**
     * Returns the project index.
     *
     * @param null  $docPage
     * @param array $subIndex
     *
     * @return array
     */
    protected function getProjectIndex($docPage = null, array $subIndex = []): array
    {
        if (is_null($docPage)) {
            $docPage = $this->docPage;
        }

        $index = [];
        foreach ($docPage->getSisterPages() as $sisterPage) {
            $index[] = [
                'page' => $sisterPage,
                'more' => ($sisterPage->iddoc === $docPage->iddoc) ? $subIndex : [],
                'selected' => $sisterPage->iddoc === $docPage->iddoc
            ];
        }

        if (!empty($index) && !is_null($index[0]['page']->idparent)) {
            return $this->getProjectIndex($docPage->getParentPage(), $index);
        }

        return $index;
    }

    /**
     * Returns extra params from URL.
     *
     * @return array
     */
    protected function getUrlExtraParams(): array
    {
        if ($this->uri === '/' . $this->getClassName()) {
            return [];
        }

        $params = explode('/', substr($this->uri, \strlen($this->webPage->permalink)));
        return empty($params[0]) ? [] : $params;
    }

    /**
     * Returns the prefix of this url. Returns false if it haven't prefix.
     *
     * @return bool|string
     */
    protected function getUrlPrefix()
    {
        $urlPrefix = substr($this->webPage->permalink, 1);
        if ('*' === substr($this->webPage->permalink, -1)) {
            $urlPrefix = substr($this->webPage->permalink, 1, -1);
        }
        if ('/' === substr($urlPrefix, -1)) {
            $urlPrefix = substr($urlPrefix, 0, -1);
        }

        return $urlPrefix;
    }

    /**
     * Returns this webpage.
     *
     * @return WebPage
     */
    protected function getWebPage()
    {
        $webPage = parent::getWebPage();
        if ($webPage->customcontroller === $this->getClassName()) {
            return $webPage;
        }

        if (!$webPage->loadFromCode('', [new DataBaseWhere('customcontroller', $this->getClassName())])) {
            /// create the webpage
            $webPage->customcontroller = $this->getClassName();
            $webPage->description = 'Doc';
            $webPage->permalink = 'doc*';
            $webPage->shorttitle = 'Doc';
            $webPage->title = 'Doc';
            $webPage->save();
        }

        return $webPage;
    }

    /**
     * Load data.
     */
    protected function loadData()
    {
        $this->setTemplate('WebDocumentation');
        $this->defaultIdproject = AppSettings::get('community', 'idproject', '');
        $this->urlPrefix = $this->getUrlPrefix();

        $urlParams = $this->getUrlExtraParams();
        $idproject = $urlParams[0] ?? $this->defaultIdproject;
        $docPermalink = isset($urlParams[1]) ? implode('/', $urlParams) : null;

        /// current project
        $this->currentProject = new WebProject();
        if (!$this->currentProject->loadFromCode($idproject)) {
            $this->miniLog->warning($this->i18n->trans('no-data'));
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        /// all projects
        $this->loadProjects();

        $this->docPage = new WebDocPage();

        /// doc page permalink?
        if (empty($docPermalink)) {
            /// project doc pages
            $this->loadProject();
        } elseif ($this->docPage->loadFromCode('', [new DataBaseWhere('permalink', $docPermalink)])) {
            /// individual doc page
            $this->loadPage();
        } else {
            $this->miniLog->warning($this->i18n->trans('no-data'));
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
        }
    }

    /**
     * Load this page data.
     */
    protected function loadPage()
    {
        $ipAddress = is_null($this->request->getClientIp()) ? '::1' : $this->request->getClientIp();
        $this->docPage->increaseVisitCount($ipAddress);
        $this->docPages = $this->docPage->getChildrenPages();
        $this->docIndex = $this->getProjectIndex();

        $this->title = $this->docPage->title;
        $this->description = $this->docPage->description(300);
        $this->canonicalUrl = $this->docPage->url('public');
        $this->setTemplate('WebDocPage');
    }

    /**
     * Load the project data related to this page.
     */
    protected function loadProject()
    {
        $this->title .= ' - ' . $this->currentProject->name;
        $this->description .= ' - Project: ' . $this->currentProject->name;
        $this->canonicalUrl = '/' . $this->getProjectUrl($this->currentProject);

        $where = [
            new DataBaseWhere('idparent', null, 'IS'),
            new DataBaseWhere('idproject', $this->currentProject->idproject),
            new DataBaseWhere('langcode', $this->webPage->langcode)
        ];
        $this->docPages = $this->docPage->all($where, ['ordernum' => 'ASC'], 0, 0);
    }

    protected function loadProjects()
    {
        $codeModel = new CodeModel();
        foreach ($codeModel->all('webdocpages', 'idproject', 'idproject', false) as $item) {
            $project = new WebProject();
            if ($project->loadFromCode($item->code) && !$project->plugin) {
                $this->projects[] = $project;
            }
        }

        /// sort by name
        uasort($this->projects, function ($item1, $item2) {
            if ($item1->name < $item2->name) {
                return -1;
            } elseif ($item1->name > $item2->name) {
                return 1;
            }

            return 0;
        });
    }
}
