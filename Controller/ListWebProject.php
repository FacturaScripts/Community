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
        $pageData['icon'] = 'fa-folder';

        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        /// projects
        $this->addView('ListWebProject', 'WebProject', 'projects', 'fa-folder');
        $this->addSearchFields('ListWebProject', ['name']);
        $this->addOrderBy('ListWebProject', ['name']);
        $this->addOrderBy('ListWebProject', ['creationdate'], 'date');

        $this->createViewsDocPages();
        $this->createViewsBuild();
    }

    /**
     * Load Views for builds
     */
    protected function createViewsBuild()
    {
        $this->addView('ListWebBuild', 'WebBuild', 'builds', 'fa-file-archive');
        $this->addSearchFields('ListWebBuild', ['path']);
        $this->addOrderBy('ListWebBuild', ['version']);
        $this->addOrderBy('ListWebBuild', ['date'], 'date', 2);
        $this->addOrderBy('ListWebBuild', ['downloads']);

        $this->addFilterCheckbox('ListWebBuild', 'beta', 'beta', 'beta');
        $this->addFilterCheckbox('ListWebBuild', 'stable', 'stable', 'stable');
    }

    /**
     * Load Views for doc pages
     */
    protected function createViewsDocPages()
    {
        $this->addView('ListWebDocPage', 'WebDocPage', 'documentation', 'fa-book');
        $this->addSearchFields('ListWebDocPage', ['title', 'body']);
        $this->addOrderBy('ListWebDocPage', ['title']);
        $this->addOrderBy('ListWebDocPage', ['creationdate'], 'date');
        $this->addOrderBy('ListWebDocPage', ['lastmod'], 'last-update', 2);
        $this->addOrderBy('ListWebDocPage', ['visitcount'], 'visit-counter');

        $projects = $this->codeModel::all('webprojects', 'idproject', 'name');
        $this->addFilterSelect('ListWebDocPage', 'idproject', 'project', 'idproject', $projects);

        $langValues = $this->codeModel::all('webdocpages', 'langcode', 'langcode');
        $this->addFilterSelect('ListWebDocPage', 'langcode', 'language', 'langcode', $langValues);
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
