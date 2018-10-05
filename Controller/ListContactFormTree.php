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

use FacturaScripts\Dinamic\Lib\ExtendedController;

/**
 * Description of ListContactFormTree
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListContactFormTree extends ExtendedController\ListController
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

        return $pageData;
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        $this->addView('ListContactFormTree', 'ContactFormTree', 'contact-form', 'fas fa-code-branch');
        $this->addSearchFields('ListContactFormTree', ['title', 'body']);
        $this->addOrderBy('ListContactFormTree', ['idparent', 'ordernum'], 'sort');
        $this->addOrderBy('ListContactFormTree', ['title']);
        $this->addOrderBy('ListContactFormTree', ['visitcount'], 'visit-counter');
        $this->addOrderBy('ListContactFormTree', ['lastmod'], 'last-update');
    }
}
