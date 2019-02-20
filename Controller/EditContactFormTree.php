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
use FacturaScripts\Dinamic\Lib\ExtendedController;

/**
 * Description of EditContactFormTree
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditContactFormTree extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'contact-form';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fas fa-code-branch';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        $this->addEditView('EditContactFormTree', 'ContactFormTree', 'edit', 'fas fa-edit');
        $this->addListView('ListContactFormTree', 'ContactFormTree', 'children', 'fas fa-code-branch');

        $this->views['ListContactFormTree']->disableColumn('parent', true);
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
            case 'EditContactFormTree':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'ListContactFormTree':
                $code = $this->getViewModelValue('EditContactFormTree', 'idtree');
                $where = [new DataBaseWhere('idparent', $code)];
                $view->loadData('', $where, 0, 0);
                break;
        }
    }
}
