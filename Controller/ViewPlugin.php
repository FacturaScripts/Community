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
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ViewPlugin
 *
 * @author Carlos García Gómez
 */
class ViewPlugin extends SectionController
{

    public function contactCanEdit(): bool
    {
        if ($this->user) {
            return true;
        }

        if (null === $this->contact) {
            return false;
        }

        $idteamdev = AppSettings::get('community', 'idteamdev', '');
        if (empty($idteamdev)) {
            return false;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $idteamdev),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    protected function createSections()
    {
        $this->addSection('plugin', [
            'fixed' => true,
            'model' => new WebProject(),
            'template' => 'Section/Plugin.html.twig',
        ]);
    }

    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'plugin':
                $this->loadPlugin($sectionName);
                break;
        }
    }

    protected function loadPlugin(string $sectionName)
    {
        $idplugin = $this->request->get('code', '');
        if (!empty($idplugin)) {
            if (!$this->sections[$sectionName]['model']->loadFromCode($idplugin)) {
                $this->miniLog->alert($this->i18n->trans('no-data'));
                $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
                $this->webPage->noindex = true;
            }

            return;
        }

        $uri = explode('/', $this->uri);
        if ($this->sections[$sectionName]['model']->loadFromCode('', [new DataBaseWhere('name', end($uri))])) {
            return;
        }

        $this->miniLog->alert($this->i18n->trans('no-data'));
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->webPage->noindex = true;
    }
}
