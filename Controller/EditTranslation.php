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
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of EditTranslation
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditTranslation extends SectionController
{

    /**
     *
     * @var Translation
     */
    private $translationModel;

    public function contactCanEdit(): bool
    {
        if ($this->user) {
            return true;
        }

        // Contact is member of translation team?
        $idteamtra = AppSettings::get('community', 'idteamtra');
        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $idteamtra),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    public function getTranslationModel(): Translation
    {
        if (isset($this->translationModel)) {
            return $this->translationModel;
        }

        $translation = new Translation();
        $code = $this->request->get('code', '');
        if (!empty($code)) {
            $translation->loadFromCode($code);
            return $translation;
        }

        $uri = explode('/', $this->uri);
        $translation->loadFromCode(end($uri));
        return $translation;
    }

    protected function checkRevisions(Translation $translation)
    {
        $mainlangcode = AppSettings::get('community', 'mainlanguage');
        if ($translation->langcode != $mainlangcode) {
            return;
        }

        // when we change a translation in main language, we check equivalent translations for revision
        $where = [
            new DataBaseWhere('name', $translation->name),
            new DataBaseWhere('id', $translation->id, '!=')
        ];
        foreach ($translation->all($where, [], 0, 0) as $trans) {
            $trans->needsrevision = true;
            $trans->save();
        }
    }

    protected function createSections()
    {
        $this->addSection('translation', ['fixed' => true, 'template' => 'Section/Translation']);

        $this->addListSection('translations', 'Translation', 'Section/Translations', 'translations', 'fa-copy');
        $this->addSearchOptions('translations', ['name', 'description', 'translation']);
        $this->addOrderOption('translations', 'name', 'code', 1);
        $this->addOrderOption('translations', 'lastmod', 'last-update');
    }

    protected function editAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
        }

        $translation = $this->getTranslationModel();
        $translation->description = $this->request->request->get('description', '');
        $translation->translation = $this->request->request->get('translation', '');
        $translation->lastmod = date('d-m-Y H:i:s');
        $translation->needsrevision = false;

        if ($translation->save()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
            $this->checkRevisions($translation);
            $this->updateLanguageStats($translation->langcode);
            $this->saveTeamLog($translation);
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'edit':
                $this->editAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'translations':
                $translation = $this->getTranslationModel();
                $where = [
                    new DataBaseWhere('name', $translation->name),
                    new DataBaseWhere('id', $translation->id, '!=')
                ];
                $this->loadListSection($sectionName, $where);
                break;
        }
    }

    private function saveTeamLog(Translation $translation)
    {
        $idteamtra = AppSettings::get('community', 'idteamtra', '');
        if (empty($idteamtra)) {
            return;
        }

        $teamLog = new WebTeamLog();
        $teamLog->description = 'Modified translation: ' . $translation->langcode . ' / ' . $translation->name;
        $teamLog->idteam = $idteamtra;
        $teamLog->idcontacto = is_null($this->contact) ? null : $this->contact->idcontacto;
        $teamLog->link = $translation->url('public');
        $teamLog->save();
    }

    private function updateLanguageStats(string $langcode)
    {
        $language = new Language();
        if ($language->loadFromCode($langcode)) {
            $language->updateStats();
            $language->save();
        }
    }
}
