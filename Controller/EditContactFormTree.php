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
use FacturaScripts\Dinamic\Lib\ExtendedController;

/**
 * Description of EditContactFormTree
 *
 * @author carlos
 */
class EditContactFormTree extends ExtendedController\PanelController
{

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'contact-form-tree';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fa-code-fork';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    protected function createViews()
    {
        $this->addEditView('EditContactFormTree', 'ContactFormTree', 'edit', 'fa-edit');
        $this->addListView('ListContactFormTree', 'ContactFormTree', 'children', 'fa-code-fork');

        $this->views['ListContactFormTree']->disableColumn('parent', true);
    }

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
