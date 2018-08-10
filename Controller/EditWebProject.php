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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\ExtendedController;

/**
 * Description of EditWebProject controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditWebProject extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'project';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fa-folder';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        $this->addEditView('EditWebProject', 'WebProject', 'project', 'fa-folder');
        $this->addListView('ListWebDocPage', 'WebDocPage', 'documentation', 'fa-book');
        $this->addListView('ListWebBuild', 'WebBuild', 'builds', 'fa-file-archive');

        $this->views['ListWebDocPage']->disableColumn('project', true);
        $this->views['ListWebBuild']->disableColumn('project', true);
    }

    /**
     * Load data view procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditWebProject':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListWebDocPage':
            case 'ListWebBuild':
                $idproject = $this->getViewModelValue('EditWebProject', 'idproject');
                $view->loadData(false, [new DataBaseWhere('idproject', $idproject)]);
                break;
        }
    }
}
