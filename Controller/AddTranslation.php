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
use FacturaScripts\Plugins\Community\Model\Language;
use FacturaScripts\Plugins\Community\Model\Translation;
use FacturaScripts\Plugins\Community\Lib\WebPortal\PortalControllerWizard;

/**
 * This class allow us to manage new plugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AddTranslation extends PortalControllerWizard
{

    /**
     * Execute common code between private and public core.
     */
    protected function commonCore()
    {
        $this->setTemplate('AddTranslation');

        $action = $this->request->request->get('action', '');
        switch ($action) {
            case 'new':
                $name = $this->request->request->get('name', '');
                if (!empty($name)) {
                    $this->newTranslation($name);
                }
                break;

            case 'new-language':
                $code = $this->request->request->get('code', '');
                if ($this->user && !empty($code)) {
                    $this->newLanguage($code);
                }
                break;
        }
    }

    /**
     * 
     * @param string $langCode
     */
    protected function cloneTranslations(string $langCode)
    {
        $mainLangcode = AppSettings::get('community', 'mainlanguage');
        $mainProjectId = (int) AppSettings::get('community', 'idproject');

        $translationModel = new Translation();
        $where = [new DataBaseWhere('langcode', $mainLangcode)];
        foreach ($translationModel->all($where, [], 0, 0) as $trans) {
            $newTrans = new Translation();
            $newTrans->description = $trans->description;
            $newTrans->idproject = $mainProjectId;
            $newTrans->langcode = $langCode;
            $newTrans->name = $trans->name;
            $newTrans->translation = $trans->translation;
            $newTrans->save();
        }
    }

    /**
     * 
     * @param string $code
     *
     * @return bool
     */
    protected function newLanguage(string $code): bool
    {
        $language = new Language();

        /// language already exists?
        $where = [new DataBaseWhere('langcode', $code)];
        if ($language->loadFromCode('', $where)) {
            $this->miniLog->error($this->i18n->trans('duplicate-record'));
            return true;
        }

        /// save new language
        $language->description = $code;
        $language->langcode = $code;
        if ($language->save()) {
            $this->cloneTranslations($code);
            $language->updateStats();
            $language->save();

            /// redit to new language
            $this->response->headers->set('Refresh', '0; ' . $language->url('public'));
            return true;
        }

        return false;
    }

    /**
     * Adds a new translation in every important language.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function newTranslation(string $name): bool
    {
        /// contact is in translation team?
        $idteamtra = AppSettings::get('community', 'idteamtra', '');
        if (!$this->contactInTeam($idteamtra)) {
            $this->contactNotInTeamError($idteamtra);
            return false;
        }

        /// translation exists?
        $transModel = new Translation();
        $where = [new DataBaseWhere('name', $name)];
        if ($transModel->loadFromCode('', $where)) {
            $this->miniLog->error($this->i18n->trans('duplicate-record'));
            return true;
        }

        /// save new translation in every important language
        $langModel = new Language();
        $mainLangcode = AppSettings::get('community', 'mainlanguage');
        $mainProjectId = (int) AppSettings::get('community', 'idproject');
        foreach ($langModel->all([], [], 0, 0) as $language) {
            $newTrans = new Translation();
            $newTrans->description = $name;
            $newTrans->idproject = $mainProjectId;
            $newTrans->langcode = $language->langcode;
            $newTrans->name = $name;
            $newTrans->translation = $name;
            if (!$newTrans->save()) {
                return false;
            }

            if ($language->langcode == $mainLangcode) {
                $description = 'New translation: ' . $newTrans->langcode . ' / ' . $newTrans->name;
                $link = $newTrans->url('public');
                $this->saveTeamLog($idteamtra, $description, $link);

                /// redit to translation in main language
                $this->response->headers->set('Refresh', '0; ' . $newTrans->url('public'));
            }
        }

        return true;
    }
}
