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
use FacturaScripts\Plugins\Community\Lib\WebTeamMethodsTrait;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;

/**
 * Description of EditWebDocPage controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditWebDocPage extends EditSectionController
{

    use WebTeamMethodsTrait;

    /**
     *
     * @var WebDocPage
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

        $idteam = $this->toolBox()->appSettings()->get('community', 'idteamdoc', '');
        return $this->contactInTeam($idteam);
    }

    /**
     * 
     * @return bool
     */
    public function contactCanSee()
    {
        return $this->contactCanEdit();
    }

    /**
     * 
     * @param bool $reload
     *
     * @return WebDocPage
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->mainModel) && !$reload) {
            return $this->mainModel;
        }

        $this->mainModel = new WebDocPage();
        $code = $this->request->request->get('code', $this->request->query->get('code', ''));
        $this->mainModel->loadFromCode($code);
        return $this->mainModel;
    }

    protected function commonCore()
    {
        parent::commonCore();
        $this->loadNavigationLinks();
    }

    protected function createSections()
    {
        if (!$this->contactCanEdit()) {
            $idteam = $this->toolBox()->appSettings()->get('community', 'idteamdoc', '');
            $this->contactNotInTeamError($idteam);
            return;
        }

        $this->addEditSection('EditWebDocPage', 'WebDocPage', 'documentation');
        if ($this->contact) {
            $this->sections['EditWebDocPage']->model->setCurrentContact($this->contact->idcontacto);
        }

        /// log
        $this->addListSection('ListWebTeamLog', 'WebTeamLog', 'log', 'fas fa-file-medical-alt');
        $this->sections['ListWebTeamLog']->template = 'Section/TeamLogs.html.twig';
        $this->addOrderOption('ListWebTeamLog', ['time'], 'date', 2);
    }

    /**
     * 
     * @return bool
     */
    protected function deleteAction()
    {
        $parent = $this->getMainModel()->getParentPage();
        if (parent::deleteAction()) {
            if ($parent) {
                $this->redirect($parent->url('public'));
                return true;
            }

            $this->redirect($this->getMainModel()->url('public-list'));
            return true;
        }

        return flase;
    }

    /**
     * 
     * @return bool
     */
    protected function insertAction()
    {
        if (parent::insertAction()) {
            $this->redirect($this->sections[$this->active]->model->url('public'));
            return true;
        }

        return false;
    }

    /**
     * 
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'EditWebDocPage':
                $this->loadDocPage($sectionName);
                break;

            case 'ListWebTeamLog':
                $where = [new DataBaseWhere('link', $this->getMainModel()->url('public'))];
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }

    /**
     * 
     * @param string $sectionName
     */
    protected function loadDocPage(string $sectionName)
    {
        $this->sections[$sectionName]->loadData($this->getMainModel()->primaryColumnValue());
        $this->title = $this->getMainModel()->title;
        $this->description = $this->getMainModel()->title;
    }

    protected function loadNavigationLinks()
    {
        $docPage = $this->getMainModel();
        $this->addNavigationLink($docPage->url('public-list'), $this->toolBox()->i18n()->trans('documentation'));
        if ($docPage->exists()) {
            $this->addNavigationLink($docPage->url('public'), $docPage->title);
        }
    }
}
