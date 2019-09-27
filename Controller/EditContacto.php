<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\BaseView;

/**
 * Description of EditContacto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditContacto extends ParentController
{

    protected function createViews()
    {
        parent::createViews();
        $this->createViewIssues();
        $this->createViewTeamLogs();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewIssues($viewName = 'ListIssue')
    {
        $this->addListView($viewName, 'Issue', 'issues', 'fas fa-question-circle');
        $this->views[$viewName]->searchFields = ['body'];
        $this->views[$viewName]->addOrderBy(['lastmod'], 'last-update', 2);

        /// disable column
        $this->views[$viewName]->disableColumn('contact');

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewTeamLogs($viewName = 'ListWebTeamLog')
    {
        $this->addListView($viewName, 'WebTeamLog', 'logs', 'fas fa-file-medical-alt');
        $this->views[$viewName]->searchFields = ['description'];
        $this->views[$viewName]->addOrderBy(['time'], 'time', 2);

        /// disable column
        $this->views[$viewName]->disableColumn('contact');

        /// disable button
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $code = $this->getViewModelValue('EditContacto', 'idcontacto');
        $where = [new DataBaseWhere('idcontacto', $code)];

        switch ($viewName) {
            case 'ListIssue':
                $view->loadData('', $where, ['creationdate' => 'DESC']);
                break;

            case 'ListWebTeamLog':
                $view->loadData('', $where, ['time' => 'DESC']);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
