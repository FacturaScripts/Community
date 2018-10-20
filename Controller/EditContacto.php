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
use FacturaScripts\Core\Controller\EditContacto as ParentController;

/**
 * Description of EditContacto
 *
 * @author carlos
 */
class EditContacto extends ParentController
{

    protected function createViews()
    {
        parent::createViews();

        /// tabs on top
        $this->setTabsPosition('top');

        $this->addListView('ListIssue', 'Issue', 'issues', 'fas fa-question-circle');
        $this->setSettings('ListIssue', 'btnNew', false);
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListIssue':
                $code = $this->getViewModelValue('EditContacto', 'idcontacto');
                $where = [new DataBaseWhere('idcontacto', $code)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
