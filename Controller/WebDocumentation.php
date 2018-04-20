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

    public function getDocPageUrl(WebDocPage $page): string
    {
        return $this->urlPrefix . '/' . $page->idproject . '/' . $page->permalink;
    }

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
        return $parser->text($txt);
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

    private function loadData()
    {
        $this->setTemplate('WebDocumentation');
        $this->defaultIdproject = AppSettings::get('community', 'idproject', '');

        /// get url prefix
        $this->urlPrefix = substr($this->webPage->permalink, 1);
        if ('*' === substr($this->webPage->permalink, -1)) {
            $this->urlPrefix = substr($this->webPage->permalink, 1, -1);
        }
        if ('/' === substr($this->urlPrefix, -1)) {
            $this->urlPrefix = substr($this->urlPrefix, 1, -1);
        }

        $url = explode('/', $this->uri);
        $idproject = isset($url[2]) ? $url[2] : $this->defaultIdproject;
        $docPermalink = isset($url[3]) ? $url[3] : null;

        /// current project
        $this->currentProject = new WebProject();
        $this->currentProject->loadFromCode($idproject);

        /// all projects
        $this->projects = $this->currentProject->all([], ['name' => 'ASC'], 0, 0);

        /// doc pages for this project
        $this->docPage = new WebDocPage();
        $where = [
            new DataBaseWhere('idproject', $this->currentProject->idproject),
            new DataBaseWhere('langcode', $this->webPage->langcode)
        ];
        $this->docPages = $this->docPage->all($where, [], 0, 0);

        /// doc page permalink?
        if (null !== $docPermalink) {
            if ($this->docPage->loadFromCode('', [new DataBaseWhere('permalink', $docPermalink)])) {
                $this->setTemplate('WebDocPage');
            }
        }
    }
}
