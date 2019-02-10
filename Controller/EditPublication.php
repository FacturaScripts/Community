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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\Publication;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditPublication
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditPublication extends EditSectionController
{

    /**
     *
     * @var Publication
     */
    protected $mainModel;

    /**
     * 
     * @return bool
     */
    public function contactCanEdit()
    {
        if ($this->user) {
            return true;
        }

        if (empty($this->contact)) {
            return false;
        }

        $publication = $this->getMainModel();
        return $publication->idcontacto === $this->contact->idcontacto;
    }

    /**
     * 
     * @return bool
     */
    public function contactCanSee()
    {
        return true;
    }

    /**
     * 
     * @param bool $reload
     *
     * @return Publication
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->mainModel) && !$reload) {
            return $this->mainModel;
        }

        $this->mainModel = new Publication();
        $uri = explode('/', $this->uri);
        $this->mainModel->loadFromCode('', [new DataBaseWhere('permalink', end($uri))]);
        return $this->mainModel;
    }

    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('publication', 'publication', 'Section/Publication');
        $this->setNavigationLinks();

        if ($this->contactCanEdit()) {
            $this->addEditSection('EditPublication', 'Publication', 'edit', 'fas fa-edit');
        }
    }

    protected function loadData(string $sectionName)
    {
        $publication = $this->getMainModel();
        switch ($sectionName) {
            case 'EditPublication':
                $this->sections[$sectionName]->loadData($publication->primaryColumnValue());
                break;

            case 'publication':
                $this->loadPublication();
                break;
        }
    }

    protected function loadPublication()
    {
        if ($this->getMainModel(true)->exists()) {
            $this->title = $this->getMainModel()->title;
            $this->description = $this->getMainModel()->description();
            $this->canonicalUrl = $this->getMainModel()->url('public');

            $ipAddress = is_null($this->request->getClientIp()) ? '::1' : $this->request->getClientIp();
            $this->getMainModel()->increaseVisitCount($ipAddress);
            return;
        }

        $this->miniLog->warning($this->i18n->trans('no-data'));
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->webPage->noindex = true;
        $this->setTemplate('Master/Portal404');

        if ('/' == substr($this->uri, -1)) {
            /// redit to homepage
            $this->response->headers->set('Refresh', '0; ' . AppSettings::get('webportal', 'url'));
        }
    }

    protected function setNavigationLinks()
    {
        $publication = $this->getMainModel();
        if ($publication->idteam) {
            $team = new WebTeam();
            if ($team->loadFromCode($publication->idteam)) {
                $this->addNavigationLink($team->url('public-list'), $this->i18n->trans('teams'));
                $this->addNavigationLink($team->url('public'), $team->name);
            }
        }

        if ($publication->idproject) {
            $project = new WebProject();
            if ($project->loadFromCode($publication->idproject) && $project->plugin) {
                $this->addNavigationLink($project->url('public'), $project->name);
            }
        }
    }
}
