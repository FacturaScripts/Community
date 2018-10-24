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
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\Community\Model\Language;
use FacturaScripts\Plugins\Community\Model\Translation;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class allow us to manage new plugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AddTranslation extends PortalController
{

    /**
     * * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->commonCore();
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->commonCore();
    }

    /**
     * Execute common code between private and public core.
     */
    protected function commonCore()
    {
        if (empty($this->contact) && !$this->user) {
            $this->setTemplate('Master/LoginToContinue');
            return;
        }

        if ($this->newTranslation()) {
            return;
        }

        $this->setTemplate('AddTranslation');
    }

    /**
     * Return true if contact can add new plugins.
     * If is a user, or is a accepted team member contact, can add new plugins, otherwise can't.
     *
     *
     * @return bool
     */
    protected function contactCanAdd(): bool
    {
        if ($this->user) {
            return true;
        }

        if (null === $this->contact) {
            return false;
        }

        $idteamtra = AppSettings::get('community', 'idteamtra', '');
        if (empty($idteamtra)) {
            return false;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $idteamtra),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    protected function newTranslation(): bool
    {
        $name = $this->request->get('name', '');
        if (empty($name)) {
            return false;
        }

        $langModel = new Language();
        foreach ($langModel->all([], [], 0, 0) as $language) {
            if ($language->numtranslations === 0) {
                continue;
            }

            $newTrans = new Translation();
            $newTrans->description = $name;
            $newTrans->idproject = (int) AppSettings::get('community', 'idproject');
            $newTrans->langcode = $language->langcode;
            $newTrans->name = $name;
            $newTrans->translation = $name;
            if (!$newTrans->save()) {
                return false;
            }
        }

        $transModel = new Translation();
        $where = [
            new DataBaseWhere('name', $name),
            new DataBaseWhere('langcode', AppSettings::get('community', 'mainlanguage')),
        ];
        if ($transModel->loadFromCode('', $where)) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            $this->saveTeamLog($transModel);
            $this->response->headers->set('Refresh', '0; ' . $transModel->url('public'));
            return true;
        }

        return false;
    }

    /**
     * Store a log detail for this new translation.
     * 
     * @param Translation $translation
     */
    protected function saveTeamLog(Translation $translation)
    {
        $idteamtra = AppSettings::get('community', 'idteamtra', '');
        if (empty($idteamtra)) {
            return;
        }

        $teamLog = new WebTeamLog();
        $teamLog->description = 'New translation: ' . $translation->langcode . ' / ' . $translation->name;
        $teamLog->idteam = $idteamtra;
        $teamLog->link = $translation->url('public');
        if ($this->contact) {
            $teamLog->idcontacto = $this->contact->idcontacto;
        }

        $teamLog->save();
    }
}
