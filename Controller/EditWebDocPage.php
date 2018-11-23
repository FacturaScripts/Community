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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Lib;
use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;

/**
 * Description of EditWebDocPage controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditWebDocPage extends EditSectionController
{

    use Lib\WebTeamMethodsTrait;

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

        $idteam = AppSettings::get('community', 'idteamdoc', '');
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
        $this->addEditSection('EditWebDocPage', 'WebDocPage', 'documentation');

        /// log
        $this->addListSection('ListWebTeamLog', 'WebTeamLog', 'log', 'fas fa-file-medical-alt');
        $this->sections['ListWebTeamLog']->template = 'Section/TeamLogs.html.twig';
        $this->addOrderOption('ListWebTeamLog', ['time'], 'date', 2);
    }

    protected function deleteAction()
    {
        $original = $this->getMainModel();
        $result = parent::deleteAction();
        if ($result && $this->active === 'EditWebDocPage') {
            $idteam = AppSettings::get('community', 'idteamdoc', '');
            $description = 'Deleted documentation page: ' . $original->title;
            $this->saveTeamLog($idteam, $description);
        }

        return $result;
    }

    protected function editAction()
    {
        $result = parent::editAction();
        if ($result && $this->active === 'EditWebDocPage') {
            $idteam = AppSettings::get('community', 'idteamdoc', '');
            $description = 'Modified documentation page: ' . $this->getMainModel()->title;
            $link = $this->getMainModel()->url('public');

            /// we only save one log per day
            $logs = $this->searchTeamLog($idteam, $this->contact->idcontacto, $link);
            if (empty($logs) || time() - strtotime($logs[0]->time) > 86400) {
                $this->saveTeamLog($idteam, $description, $link);
            }
        }

        return $result;
    }

    protected function insertAction()
    {
        $result = parent::insertAction();
        if ($result && $this->active === 'EditWebDocPage') {
            /// load new data
            $this->getMainModel();
            $this->mainModel->loadFromCode($this->sections[$this->active]->newCode);

            $idteam = AppSettings::get('community', 'idteamdoc', '');
            $description = 'Created documentation page: ' . $this->getMainModel()->title;
            $link = $this->getMainModel()->url('public');

            /// we only save one log per day
            $logs = $this->searchTeamLog($idteam, $this->contact->idcontacto, $link);
            if (empty($logs) || time() - strtotime($logs[0]->time) > 86400) {
                $this->saveTeamLog($idteam, $description, $link);
            }
        }

        return $result;
    }

    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'EditWebDocPage':
                $this->loadDocPage($sectionName);
                break;

            case 'ListWebTeamLog':
                $docPage = $this->getMainModel();
                $where = [new DataBaseWhere('link', $docPage->url('public'))];
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }

    protected function loadDocPage(string $sectionName)
    {
        if (!$this->contactCanEdit()) {
            $idteam = AppSettings::get('community', 'idteamdoc', '');
            $this->contactNotInTeamError($idteam);
            return;
        }

        $this->sections[$sectionName]->loadData($this->getMainModel()->primaryColumnValue());
        $this->title = $this->getMainModel()->title;
        $this->description = $this->getMainModel()->title;
    }

    protected function loadNavigationLinks()
    {
        $docPage = $this->getMainModel();
        $this->addNavigationLink($docPage->url('public-list'), $this->i18n->trans('documentation'));
        if ($docPage->exists()) {
            $this->addNavigationLink($docPage->url('public'), $docPage->title);
        }
    }
}
