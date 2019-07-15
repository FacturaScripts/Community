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
 * Description of EditWebProject controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditWebProject extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'WebProject';
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
        $pageData['title'] = 'project';
        $pageData['icon'] = 'fas fa-folder-open';
        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        /// builds
        $this->addEditListView('EditWebBuild', 'WebBuild', 'builds', 'fas fa-file-archive');

        /// documentation
        $this->addListView('ListWebDocPage', 'WebDocPage', 'documentation', 'fas fa-book');
        $this->views['ListWebDocPage']->addOrderBy(['lastmod'], 'last-update', 2);
        $this->views['ListWebDocPage']->searchFields = ['title', 'body'];
        $this->views['ListWebDocPage']->disableColumn('project', true);
    }

    /**
     * Load data view procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $idproject = $this->getViewModelValue('EditWebProject', 'idproject');
        switch ($viewName) {
            case 'ListWebDocPage':
                $view->loadData(false, [new DataBaseWhere('idproject', $idproject)]);
                break;

            case 'EditWebBuild':
                $view->loadData(false, [new DataBaseWhere('idproject', $idproject)], ['version' => 'DESC']);
                if (!$view->model->exists()) {
                    /// increase version
                    $view->model->version += $this->getViewModelValue('EditWebProject', 'version');
                }
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
