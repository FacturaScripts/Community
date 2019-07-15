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

use FacturaScripts\Dinamic\Lib\ExtendedController\ListController;

/**
 * Description of ListContactFormTree
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListContactFormTree extends ListController
{

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
        $viewName = 'ListContactFormTree';
        $this->addView($viewName, 'ContactFormTree', 'contact-form', 'fas fa-project-diagram');
        $this->addSearchFields($viewName, ['title', 'body']);
        $this->addOrderBy($viewName, ['idparent', 'ordernum'], 'sort');
        $this->addOrderBy($viewName, ['title']);
        $this->addOrderBy($viewName, ['visitcount'], 'visit-counter');
        $this->addOrderBy($viewName, ['lastmod'], 'last-update');
    }
}
