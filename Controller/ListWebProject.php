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
use FacturaScripts\Dinamic\Lib\ExtendedController;
use FacturaScripts\Plugins\Community\Model\WebDocPage;

/**
 * Description of ListWebProject controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListWebProject extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'projects';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fas fa-folder-open';

        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        $this->createViewProjects();
        $this->createViewsBuild();
        $this->createViewsDocPages();
        $this->createViewLicenses();
    }

    /**
     * Load Views for builds
     */
    protected function createViewsBuild($viewName = 'ListWebBuild')
    {
        $this->addView($viewName, 'WebBuild', 'builds', 'fas fa-file-archive');
        $this->addSearchFields($viewName, ['path']);
        $this->addOrderBy($viewName, ['version']);
        $this->addOrderBy($viewName, ['date'], 'date', 2);
        $this->addOrderBy($viewName, ['downloads']);

        $this->addFilterCheckbox($viewName, 'beta', 'beta', 'beta');
        $this->addFilterCheckbox($viewName, 'stable', 'stable', 'stable');
    }

    /**
     * Load Views for doc pages
     */
    protected function createViewsDocPages($viewName = 'ListWebDocPage')
    {
        $this->addView($viewName, 'WebDocPage', 'documentation', 'fas fa-book');
        $this->addSearchFields($viewName, ['title', 'body']);
        $this->addOrderBy($viewName, ['title']);
        $this->addOrderBy($viewName, ['creationdate'], 'date');
        $this->addOrderBy($viewName, ['lastmod'], 'last-update', 2);
        $this->addOrderBy($viewName, ['visitcount'], 'visit-counter');

        $projects = $this->codeModel::all('webprojects', 'idproject', 'name');
        $this->addFilterSelect($viewName, 'idproject', 'project', 'idproject', $projects);

        $langValues = $this->codeModel::all('webdocpages', 'langcode', 'langcode');
        $this->addFilterSelect($viewName, 'langcode', 'language', 'langcode', $langValues);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewLicenses($viewName = 'ListLicense')
    {
        $this->addView($viewName, 'License', 'licenses', 'fas fa-file-signature');
        $this->addSearchFields($viewName, ['name', 'description']);
        $this->addOrderBy($viewName, ['name']);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewProjects($viewName = 'ListWebProject')
    {
        $this->addView($viewName, 'WebProject', 'projects', 'fas fa-folder-open');
        $this->addSearchFields($viewName, ['name', 'description']);
        $this->addOrderBy($viewName, ['name']);
        $this->addOrderBy($viewName, ['creationdate'], 'date');
    }

    /**
     * Runs the controller actions after data read.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        if ($action === 'regen-permalinks') {
            $this->regenPermalinksAction();
        }

        parent::execAfterAction($action);
    }

    /**
     * Regenerates a permalink for a doc page.
     *
     * @param WebDocPage $docPage
     */
    private function regenPermalinks(WebDocPage $docPage)
    {
        $docPage->permalink = null;
        if ($docPage->save()) {
            foreach ($docPage->getChildrenPages() as $children) {
                $this->regenPermalinks($children);
            }
        }
    }

    /**
     * Code for regenerate permalinks action.
     */
    private function regenPermalinksAction()
    {
        $docPageModel = new WebDocPage();
        $where = [new DataBaseWhere('idparent', null, 'IS')];
        foreach ($docPageModel->all($where) as $docPage) {
            $this->regenPermalinks($docPage);
        }

        $this->miniLog->info('done');
    }
}
