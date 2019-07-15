<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditContactFormTree
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditContactFormTree extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'ContactFormTree';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'web';
        $pageData['title'] = 'contact-form';
        $pageData['icon'] = 'fas fa-project-diagram';
        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewChildren();
        $this->createViewIssues();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewChildren($viewName = 'ListContactFormTree')
    {
        $this->addListView($viewName, 'ContactFormTree', 'children', 'fas fa-project-diagram');
        $this->views[$viewName]->searchFields = ['title', 'body'];
        $this->views[$viewName]->addOrderBy(['ordernum'], 'sort');

        $this->views[$viewName]->disableColumn('parent', true);
    }

    /**
     * 
     * @param string $viewName
     */
    public function createViewIssues($viewName = 'ListIssue')
    {
        $this->addListView($viewName, 'Issue', 'issues', 'fas fa-question-circle');
        $this->views[$viewName]->searchFields = ['body'];
        $this->views[$viewName]->addOrderBy(['lastmod'], 'last-update', 2);
        
        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Load data view procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $idtree = $this->getViewModelValue('EditContactFormTree', 'idtree');

        switch ($viewName) {
            case 'ListIssue':
                $where = [new DataBaseWhere('idtree', $idtree)];
                $view->loadData('', $where);
                break;

            case 'ListContactFormTree':
                $where = [new DataBaseWhere('idparent', $idtree)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
