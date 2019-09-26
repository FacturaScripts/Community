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
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ViewPlugin
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ViewPlugin extends EditSectionController
{

    /**
     * The project related to this plugin.
     *
     * @var WebProject
     */
    protected $project;

    /**
     * Returns true if contact can edit this plugin.
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

        $project = $this->getMainModel();
        return ($this->contact->idcontacto === $project->idcontacto);
    }

    /**
     * 
     * @return bool
     */
    public function contactCanSee()
    {
        $project = $this->getMainModel();
        if (!$project->private) {
            return true;
        }

        return $this->contactCanEdit();
    }

    /**
     * 
     * @param bool $reload
     *
     * @return WebProject
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->project) && !$reload) {
            return $this->project;
        }

        $this->project = new WebProject();
        $uri = explode('/', $this->uri);
        if ($this->project->loadFromCode('', [new DataBaseWhere('permalink', end($uri))])) {
            return $this->project;
        }

        $this->project->loadFromCode('', [new DataBaseWhere('name', end($uri))]);
        return $this->project;
    }

    /**
     * 
     * @param string $date
     * @param string $max
     *
     * @return bool
     */
    public function isDateOld($date, $max)
    {
        return strtotime($date) < strtotime($max);
    }

    /**
     * Returns true if we can edit this model object.
     *
     * @param object $model
     *
     * @return bool
     */
    protected function checkModelSecurity($model)
    {
        if (!$this->contactCanEdit()) {
            return false;
        }

        $project = $this->getMainModel();
        switch ($model->modelClassName()) {
            case 'WebBuild':
                return $model->idproject == $project->primaryColumnValue();

            case 'WebProject':
                return $model->primaryColumnValue() == $project->primaryColumnValue();

            default:
                return parent::checkModelSecurity($model);
        }
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionPublications($name = 'ListPublication')
    {
        $this->addListSection($name, 'Publication', 'publications', 'fas fa-newspaper');
        $this->addOrderOption($name, ['creationdate'], 'date', 2);
        $this->addSearchOptions($name, ['title', 'body']);

        /// buttons
        $plugin = $this->getMainModel();
        if ($this->contactCanEdit()) {
            $button = [
                'action' => 'AddPublication?idproject=' . $plugin->idproject,
                'color' => 'success',
                'icon' => 'fas fa-plus',
                'label' => 'new',
                'type' => 'link'
            ];
            $this->addButton($name, $button);
        }
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('plugin', 'plugin', 'Section/Plugin');

        $this->addListSection('ListWebDocPage', 'WebDocPage', 'documentation', 'fas fa-book');
        $this->sections['ListWebDocPage']->template = 'Section/Documentation.html.twig';

        $this->createSectionPublications();

        /// admin
        if ($this->contactCanEdit()) {
            $this->addEditSection('EditWebProject', 'WebProject', 'edit', 'fas fa-edit', 'admin');
            $this->addEditListSection('EditWebBuild', 'WebBuild', 'builds', 'fas fa-file-archive', 'admin');
            $this->addListSection('ListIssue', 'Issue', 'issues', 'fas fa-question-circle', 'admin');
            $this->sections['ListIssue']->template = 'Section/Issues.html.twig';
        }

        /// navigation links
        $project = $this->getMainModel();
        $this->addNavigationLink($project->url('public-list'), $this->toolBox()->i18n()->trans('plugins'));
        $this->addNavigationLink($project->url('public-list') . '?activetab=ListWebProject', '2018');
    }

    /**
     * 
     * @return bool
     */
    protected function insertAction()
    {
        $return = parent::insertAction();
        $this->sections[$this->active]->model->clear();
        return $return;
    }

    /**
     * 
     * @param string $sectionName
     */
    protected function loadAvaliablePluginTypes(string $sectionName)
    {
        $types = [];
        foreach (WebProject::avaliableTypes() as $type) {
            $types[] = ['value' => $type, 'title' => $type];
        }
        $columnType = $this->sections[$sectionName]->columnForName('type');
        if ($columnType) {
            $columnType->widget->setValuesFromArray($types, true);
        }
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $project = $this->getMainModel();
        switch ($sectionName) {
            case 'EditWebBuild':
                $where = [new DataBaseWhere('idproject', $project->idproject)];
                $this->sections[$sectionName]->loadData('', $where, ['version' => 'DESC']);
                if (!$this->sections[$sectionName]->model->exists()) {
                    /// increase version
                    $this->sections[$sectionName]->model->version += $this->getMainModel()->version;
                }
                break;

            case 'EditWebProject':
                $this->sections[$sectionName]->loadData($project->primaryColumnValue());
                $this->loadAvaliablePluginTypes($sectionName);
                break;

            case 'ListIssue':
            case 'ListPublication':
                $where = [new DataBaseWhere('idproject', $project->idproject)];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListWebDocPage':
                $where = [
                    new DataBaseWhere('idproject', $project->idproject),
                    new DataBaseWhere('idparent', null, 'IS'),
                ];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'plugin':
                $this->loadPlugin();
                break;
        }
    }

    /**
     * Load the plugin data.
     */
    protected function loadPlugin()
    {
        if (!$this->getMainModel(true)->exists() || !$this->getMainModel()->plugin) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        if (!$this->contactCanSee()) {
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/AccessDenied');
            return;
        }

        $this->title = 'Plugin ' . $this->getMainModel()->name;
        $this->description = $this->getMainModel()->description();
        $this->canonicalUrl = $this->getMainModel()->url('public');

        $ipAddress = $this->toolBox()->ipFilter()->getClientIp();
        $this->getMainModel()->increaseVisitCount($ipAddress);
    }
}
