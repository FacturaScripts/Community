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

use FacturaScripts\Plugins\Community\Model\Publication;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\webportal\Controller\Sitemap as parentController;

/**
 * Description of Sitemap
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Sitemap extends parentController
{

    /**
     * Return a list of items to generate the sitemap.
     *
     * @return array
     */
    protected function getSitemapItems(): array
    {
        $items = parent::getSitemapItems();

        foreach ($this->getPluginItems() as $item) {
            $items[] = $item;
        }

        foreach ($this->getPublications() as $item) {
            $items[] = $item;
        }

        foreach ($this->getDocPagesItems() as $item) {
            $items[] = $item;
        }

        foreach ($this->getTeamItems() as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Returns a list of items for doc pages.
     *
     * @return array
     */
    protected function getDocPagesItems(): array
    {
        $items = [];

        $docPageModel = new WebDocPage();
        foreach ($docPageModel->all([], ['lastmod' => 'DESC'], 0, 500) as $docPage) {
            $items[] = $this->createItem($docPage->url('public'), strtotime($docPage->lastmod));
        }

        return $items;
    }

    /**
     * Returns a list of items for plugins.
     *
     * @return array
     */
    protected function getPluginItems(): array
    {
        $items = [];

        $projectModel = new WebProject();
        foreach ($projectModel->all([], ['lastmod' => 'DESC'], 0, 500) as $project) {
            if (!$project->plugin || $project->private) {
                continue;
            }

            $items[] = $this->createItem($project->url('public'), strtotime($project->lastmod));
        }

        return $items;
    }

    protected function getPublications(): array
    {
        $items = [];

        $publicationModel = new Publication();
        foreach ($publicationModel->all([], ['lastmod' => 'DESC'], 0, 500) as $publication) {
            $items[] = $this->createItem($publication->url('public'), strtotime($publication->lastmod));
        }

        return $items;
    }

    /**
     * Returns a list of items for Teams.
     *
     * @return array
     */
    protected function getTeamItems(): array
    {
        $items = [];

        $teamModel = new WebTeam();
        foreach ($teamModel->all([], ['lastmod' => 'DESC'], 0, 500) as $team) {
            if ($team->private) {
                continue;
            }

            $items[] = $this->createItem($team->url('public'), strtotime($team->creationdate));
        }

        return $items;
    }
}
