<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Plugins\Community\Model\Project;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;

/**
 * Description of WebDocumentation
 *
 * @author Carlos García Gómez
 */
class WebDocumentation extends PortalController
{

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'documentation';
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fa-book';

        return $pageData;
    }

    public function getProjects(): array
    {
        $project = new Project();
        return $project->all([], ['name' => 'ASC'], 0, 0);
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate('WebDocumentation');
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->setTemplate('WebDocumentation');
    }
}
