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
namespace FacturaScripts\Plugins\Community\Lib\WebPortal;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Plugins\Community\Model\Publication;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SearchEngine as ParentEngine;

/**
 * Description of SearchEngine
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class SearchEngine extends ParentEngine
{

    /**
     * 
     * @param array  $results
     * @param string $query
     */
    protected function findResults(&$results, $query)
    {
        parent::findResults($results, $query);
        $this->findDocPages($results, $query);
        $this->findPlugins($results, $query);
        $this->findPublications($results, $query);
    }

    /**
     * Search the query on pages.
     * 
     * @param array  $results
     * @param string $query
     * @param int    $priority
     */
    protected function findDocPages(&$results, $query, $priority = 1)
    {
        $defaultIdproject = $this->toolBox()->appSettings()->get('community', 'idproject', '');

        $docPage = new WebDocPage();
        $where = [new DataBaseWhere('body|title', $query, 'XLIKE')];
        foreach ($docPage->all($where, ['visitcount' => 'DESC']) as $docPage) {
            $this->addSearchResults($results, [
                'icon' => 'fas fa-book',
                'title' => $docPage->title,
                'description' => $docPage->body,
                'link' => $docPage->url('public'),
                'priority' => ($docPage->idproject == $defaultIdproject) ? $priority : 0,
                ], $query);
        }
    }

    /**
     * Search the query on plugins.
     * 
     * @param array  $results
     * @param string $query
     * @param int    $priority
     */
    protected function findPlugins(&$results, $query, $priority = 3)
    {
        $pluginProject = new WebProject();
        $where = [
            new DataBaseWhere('name|description', $query, 'XLIKE'),
            new DataBaseWhere('plugin', true),
            new DataBaseWhere('private', false)
        ];
        foreach ($pluginProject->all($where) as $plugin) {
            $this->addSearchResults($results, [
                'icon' => 'fas fa-plug',
                'title' => $plugin->name,
                'description' => $plugin->description,
                'link' => $plugin->url('public'),
                'priority' => $priority,
                ], $query);
        }
    }

    /**
     * Search the query on publications.
     *
     * @param array  $results
     * @param string $query
     * @param int    $priority
     */
    protected function findPublications(&$results, $query, $priority = -1)
    {
        $publication = new Publication();
        $where = [new DataBaseWhere('title|body', $query, 'XLIKE')];
        foreach ($publication->all($where) as $pub) {
            $this->addSearchResults($results, [
                'icon' => 'fas fa-newspaper',
                'title' => $pub->title,
                'description' => $pub->body,
                'link' => $pub->url('public'),
                'priority' => $priority,
                ], $query);
        }
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
