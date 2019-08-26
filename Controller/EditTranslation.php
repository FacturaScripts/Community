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

    use WebTeamMethodsTrait;

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
        $idteam = $this->toolBox()->appSettings()->get('community', 'idteamtra');
        if (!$this->contactInTeam($idteam)) {
            return false;
        }

        // This language has a mantainer?
        $language = $this->getMainModel()->getLanguage();
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

        /// set current contact
        if ($this->contact) {
            $this->getMainModel()->setCurrentContact($this->contact->idcontacto);
        }

        /// navigation links
        $language = $this->getMainModel()->getLanguage();
        $this->addNavigationLink($language->url('public-list') . '?activetab=ListTranslation', $this->toolBox()->i18n()->trans('translations'));
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
        $this->addOrderOption($name, ['langcode'], 'code', 1);
        $this->addOrderOption($name, ['lastmod'], 'last-update');
    }

    /**
     * Code for delete action.
     */
    protected function deleteAction()
    {
        if (empty($this->contact)) {
            return false;
        }

        if (!$this->contactCanEdit()) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

        $translation = $this->getMainModel();
        foreach ($translation->getEquivalents() as $trans) {
            $trans->delete();
        }

        if ($translation->delete()) {
            $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-deleted-error');
        return false;
    }

    /**
     * Code for edit action.
     */
    protected function editAction()
    {
        if (empty($this->contact)) {
            return false;
        }

        if (!$this->contactCanEdit()) {
            $idteam = $this->toolBox()->appSettings()->get('community', 'idteamtra');
            $this->contactNotInTeamError($idteam);
            return false;
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
            $this->toolBox()->i18nLog()->error('record-save-error');
            return false;
        }

        /// rename
        if ($oldTransName != $translation->name) {
            foreach ($translation->getEquivalents($oldTransName) as $trans) {
                $trans->name = $translation->name;
                $trans->save();
            }
        }

        $translation->updateChildren();
        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        return true;
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
        }

        return parent::execPreviousAction($action);
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
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        $this->title = $this->translationModel->name;
        $this->description = $this->translationModel->description;
    }
}
