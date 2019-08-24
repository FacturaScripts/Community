<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;

/**
 * Description of PluginInfoList
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PluginInfoList extends PortalController
{

    protected function processWebPage()
    {
        parent::processWebPage();
        $this->setTemplate(false);

        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->setContent(json_encode($this->getPluginInfoList()));
    }

    /**
     * 
     * @return array
     */
    protected function getPluginInfoList()
    {
        $projectModel = new WebProject();
        $where = [
            new DataBaseWhere('plugin', true),
            new DataBaseWhere('private', false),
        ];
        $order = ['LOWER(name)' => 'ASC'];

        $domain = $this->toolBox()->appSettings()->get('webportal', 'url');

        $list = [];
        foreach ($projectModel->all($where, $order, 0, 0) as $plugin) {
            $list[] = [
                'description' => $this->toolBox()->utils()->fixHtml($plugin->description),
                'name' => $plugin->name,
                'url' => $domain . $plugin->url('public'),
                'version' => $plugin->version
            ];
        }

        return $list;
    }
}
