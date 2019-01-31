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
use FacturaScripts\Plugins\Community\Lib;
use FacturaScripts\Plugins\Community\Model\Language;
use FacturaScripts\Plugins\Community\Model\Translation;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to manage an existing translation.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda@x-netdigital.com>
 */
class EditTranslation extends EditSectionController
{

    use Lib\WebTeamMethodsTrait;

    /**
     * This translation.
     *
     * @var Translation
     */
    private $translationModel;

    /**
     * Returns true if contact can edit this translation.
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

        // Contact is member of translation team?
        $idteam = AppSettings::get('community', 'idteamtra');
        if (!$this->contactInTeam($idteam)) {
            return false;
        }

        // This language has a mantainer?
        $language = $this->getLanguageModel();
        return !($language->idcontacto && $language->idcontacto !== $this->contact->idcontacto);
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
     * @return Language
     */
    public function getLanguageModel(): Language
    {
        return $this->getMainModel()->getLanguage();
    }

    /**
     * Returns the translation loaded by code.
     * 
     * @param bool $reload
     *
     * @return Translation
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->translationModel) && !$reload) {
            return $this->translationModel;
        }

        $this->translationModel = new Translation();
        $code = $this->request->query->get('code', '');
        if (!empty($code)) {
            $this->translationModel->loadFromCode($code);
            return $this->translationModel;
        }

        $uri = explode('/', $this->uri);
        $this->translationModel->loadFromCode(end($uri));
        return $this->translationModel;
    }

    /**
     * Check the revisions for this translation.
     *
     * @param Translation $translation
     */
    protected function checkRevisions(Translation $translation)
    {
        $mainLangCode = AppSettings::get('community', 'mainlanguage');
        if ($translation->langcode !== $mainLangCode) {
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

    /**
     * 
     * @param string $name
     */
    protected function createLogSection($name = 'ListWebTeamLog')
    {
        $this->addListSection($name, 'WebTeamLog', 'log', 'fas fa-file-medical-alt');
        $this->sections[$name]->template = 'Section/TeamLogs.html.twig';
        $this->addOrderOption($name, ['time'], 'date', 2);
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('translation', 'translation', 'Section/Translation');
        $language = $this->getLanguageModel();
        $this->addNavigationLink($language->url('public-list') . '?activetab=ListTranslation', $this->i18n->trans('translations'));
        $this->addNavigationLink($language->url('public'), $language->description);

        $this->createTranslationSection('ListTranslation', 'translations', 'fas fa-copy');
        $this->createTranslationSection('ListTranslation-rev', 'needs-revisions', 'fas fa-eye');
        $this->createLogSection();
    }

    /**
     * 
     * @param string $name
     * @param string $label
     * @param string $icon
     */
    protected function createTranslationSection($name, $label, $icon)
    {
        $this->addListSection($name, 'Translation', $label, $icon);
        $this->sections[$name]->template = 'Section/Translations.html.twig';
        $this->addSearchOptions($name, ['name', 'description', 'translation']);
        $this->addOrderOption($name, ['name'], 'code', 1);
        $this->addOrderOption($name, ['lastmod'], 'last-update');
    }

    /**
     * Code for delete action.
     */
    protected function deleteAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-delete'));
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return;
        }

        $translation = $this->getMainModel();
        foreach ($translation->getEquivalents() as $trans) {
            $trans->delete();
        }

        if ($translation->delete()) {
            $this->miniLog->info($this->i18n->trans('record-deleted-correctly'));
            $idteam = AppSettings::get('community', 'idteamtra');
            $description = 'Deleted translation: ' . $translation->langcode . ' / ' . $translation->name;
            $this->saveTeamLog($idteam, $description);
        }
    }

    /**
     * Code for edit action.
     */
    protected function editAction()
    {
        if (!$this->contactCanEdit()) {
            $idteam = AppSettings::get('community', 'idteamtra');
            $this->contactNotInTeamError($idteam);
            return;
        }

        $translation = $this->getMainModel();
        $translation->description = $this->request->request->get('description', '');
        $translation->translation = $this->request->request->get('translation', '');
        $translation->lastmod = date('d-m-Y H:i:s');
        $translation->needsrevision = false;

        /// rename?
        $oldTransName = $translation->name;
        if ($this->request->request->get('name', '') !== '') {
            $translation->name = $this->request->request->get('name', '');
        }

        if (!$translation->save()) {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }

        /// rename
        if ($oldTransName != $translation->name) {
            foreach ($translation->getEquivalents($oldTransName) as $trans) {
                $trans->name = $translation->name;
                $trans->save();
            }
        }

        /// update children
        foreach ($translation->getChildren() as $trans) {
            if ($trans->needsrevision) {
                $trans->description = $translation->description;
                $trans->translation = $translation->translation;
                $trans->needsrevision = false;
                $trans->save();
            }
        }

        $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
        $idteam = AppSettings::get('community', 'idteamtra');
        $description = 'Updated translation: ' . $translation->langcode . ' / ' . $translation->name;
        $link = $translation->url('public');

        /// we only save one log per day
        $logs = $this->searchTeamLog($idteam, $this->contact->idcontacto, $link);
        if (empty($logs) || time() - strtotime($logs[0]->time) > 86400) {
            $this->saveTeamLog($idteam, $description, $link);
        }

        $this->checkRevisions($translation);
        $this->updateLanguageStats($translation->langcode);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'delete':
                $this->deleteAction();
                return true;

            case 'edit':
                $this->editAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $translation = $this->getMainModel();
        switch ($sectionName) {
            case 'ListWebTeamLog':
                $where = [new DataBaseWhere('link', $translation->url('public'))];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListTranslation-rev':
                $where = [
                    new DataBaseWhere('langcode', $translation->langcode),
                    new DataBaseWhere('needsrevision', true),
                    new DataBaseWhere('id', $translation->id, '!=')
                ];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListTranslation':
                $where = [
                    new DataBaseWhere('name', $translation->name),
                    new DataBaseWhere('id', $translation->id, '!=')
                ];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'translation':
                $this->loadTranslation();
                break;
        }
    }

    protected function loadTranslation()
    {
        if (!$this->getMainModel(true)->exists()) {
            $this->miniLog->warning($this->i18n->trans('no-data'));
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        $this->title = $this->translationModel->name;
        $this->description = $this->translationModel->description;
    }

    /**
     * Updates the details of the language.
     *
     * @param string $langcode
     */
    private function updateLanguageStats(string $langcode)
    {
        $language = new Language();
        if ($language->loadFromCode($langcode)) {
            $language->updateStats();
            $language->save();
        }
    }
}
