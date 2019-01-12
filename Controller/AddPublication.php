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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Lib\WebPortal\PortalControllerWizard;
use FacturaScripts\Plugins\Community\Model\Publication;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;

/**
 * Description of AddPublication
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AddPublication extends PortalControllerWizard
{

    /**
     *
     * @var Publication
     */
    public $publication;

    /**
     * 
     * @return WebProject[]
     */
    public function myProjects()
    {
        $project = new WebProject();
        if ($this->user) {
            return $project->all([], ['name' => 'ASC'], 0, 0);
        }

        $where = [new DataBaseWhere('idcontacto', $this->contact->idcontacto)];
        return $project->all($where, ['name' => 'ASC'], 0, 0);
    }

    /**
     * 
     * @return WebTeam[]
     */
    public function myTeams()
    {
        if ($this->user) {
            $team = new WebTeam();
            return $team->all();
        }

        $teamMember = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('accepted', true),
        ];

        $teams = [];
        foreach ($teamMember->all($where) as $member) {
            $teams[] = $member->getTeam();
        }

        return $teams;
    }

    protected function commonCore()
    {
        $this->setTemplate('AddPublication');
        $this->publication = new Publication();
        $this->publication->idproject = $this->request->get('idproject');
        $this->publication->idteam = $this->request->get('idteam');

        $action = $this->request->request->get('action');
        switch ($action) {
            case 'save':
                $this->saveAction();
                break;
        }
    }

    /**
     * 
     * @return bool
     */
    protected function saveAction()
    {
        $this->publication->idcontacto = $this->contact->idcontacto;
        $this->publication->title = $this->request->request->get('title', '');
        $this->publication->body = $this->request->request->get('body', '');

        foreach (['idproject', 'idteam'] as $key) {
            $value = $this->request->request->get($key);
            $this->publication->{$key} = empty($value) ? null : $value;
        }

        if ($this->publication->save()) {
            /// redir to new publication
            $this->response->headers->set('Refresh', '0; ' . $this->publication->url('public'));
            return true;
        }

        $this->miniLog->alert($this->i18n->trans('record-save-error'));
        return false;
    }
}
