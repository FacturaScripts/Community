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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Controller;

require_once __DIR__ . '/../vendor/autoload.php';

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Parsedown;

/**
 * Description of WebDocumentation
 *
 * @author Carlos García Gómez
 */
class WebDocumentation extends PortalController
{

    /**
     *
     * @var WebProject
     */
    public $currentProject;

    /**
     *
     * @var mixed
     */
    public $defaultIdproject;

    /**
     *
     * @var WebDocPage
     */
    public $docPage;

    /**
     *
     * @var WebDocPage[]
     */
    public $docPages;

    /**
     *
     * @var WebProject[]
     */
    public $projects;

    /**
     *
     * @var string
     */
    public $urlPrefix;

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'documentation';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fa-book';

        return $pageData;
    }

    public function getProjectUrl(WebProject $project): string
    {
        if ($project->idproject == $this->defaultIdproject) {
            return $this->urlPrefix;
        }

        return $this->urlPrefix . '/' . $project->idproject;
    }

    public function parsedown(string $txt): string
    {
        $parser = new Parsedown();
        $html = $parser->text(Utils::fixHtml($txt));

        /// fix <pre> tag
        return str_replace('<pre>', '<pre class="code">', $html);
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadData();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->loadData();
    }

    private function getUrlExtraParams()
    {
        if ($this->uri === '/' . $this->getClassName()) {
            return [];
        }

        $params = explode('/', substr($this->uri, strlen($this->webPage->permalink)));
        return empty($params[0]) ? [] : $params;
    }

    private function getUrlPrefix()
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

    protected function getWebPage()
    {
        $webPage = parent::getWebPage();
        if ($webPage->customcontroller === $this->getClassName()) {
            return $webPage;
        }

        $webPage->loadFromCode('', [new DataBaseWhere('customcontroller', $this->getClassName())]);
        return $webPage;
    }

    private function loadData()
    {
        $this->setTemplate('WebDocumentation');
        $this->defaultIdproject = AppSettings::get('community', 'idproject', '');
        $this->urlPrefix = $this->getUrlPrefix();

        $urlParams = $this->getUrlExtraParams();
        $idproject = isset($urlParams[0]) ? $urlParams[0] : $this->defaultIdproject;
        $docPermalink = isset($urlParams[1]) ? $urlParams[0] . '/' . $urlParams[1] : null;

        /// current project
        $this->currentProject = new WebProject();
        $this->currentProject->loadFromCode($idproject);

        /// all projects
        $this->projects = $this->currentProject->all([], ['name' => 'ASC'], 0, 0);

        $this->docPage = new WebDocPage();

        /// doc page permalink?
        if (null === $docPermalink) {
            /// project doc pages
            $this->loadProject();
        } elseif ($this->docPage->loadFromCode('', [new DataBaseWhere('permalink', $docPermalink)])) {
            /// individual doc page
            $this->loadPage();
        }
    }

    private function loadPage()
    {
        $this->docPage->increaseVisitCount();
        $this->docPages = $this->docPage->getChildrenPages();

        $this->title = $this->docPage->title;
        $this->description = $this->docPage->description(300);
        $this->setTemplate('WebDocPage');
    }

    private function loadProject()
    {
        $this->title .= ' - ' . $this->currentProject->name;
        $this->description .= ' - Project: ' . $this->currentProject->name;

        $where = [
            new DataBaseWhere('idparent', null, 'IS'),
            new DataBaseWhere('idproject', $this->currentProject->idproject),
            new DataBaseWhere('langcode', $this->webPage->langcode)
        ];
        $this->docPages = $this->docPage->all($where, ['ordernum' => 'ASC'], 0, 0);
    }
}
