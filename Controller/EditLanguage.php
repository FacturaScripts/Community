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
use FacturaScripts\Plugins\Community\Model\Language;
use FacturaScripts\Plugins\Community\Model\Translation;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to manage an existing language.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda@x-netdigital.com>
 */
class EditLanguage extends EditSectionController
{

    /**
     * This language.
     *
     * @var Language
     */
    private $languageModel;

    /**
     * Returns true if contact can edit this language.
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

        $language = $this->getMainModel();
        return ($language->idcontacto === $this->contact->idcontacto);
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
     * Returns the language loaded by code.
     * 
     * @param bool $reload
     *
     * @return Language
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->languageModel) && !$reload) {
            return $this->languageModel;
        }

        $this->languageModel = new Language();
        $code = $this->request->query->get('code', '');
        if (!empty($code)) {
            $this->languageModel->loadFromCode($code);
            return $this->languageModel;
        }

        $uri = explode('/', $this->uri);
        $this->languageModel->loadFromCode(end($uri));
        return $this->languageModel;
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->fixedSection();

        $this->addHtmlSection('language', 'language', 'Section/Language');
        $language = $this->getMainModel();
        $this->addNavigationLink($language->url('public-list') . '?activetab=ListLanguage', $this->toolBox()->i18n()->trans('languages'));

        $this->createSectionRevisions();
        $this->createSectionTranslations();

        /// admin
        if ($this->contactCanEdit()) {
            $this->addEditSection('EditLanguage', 'Language', 'edit', 'fas fa-edit', 'admin');
        }
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionRevisions($name = 'ListTranslation-rev')
    {
        $this->addListSection($name, 'Translation', 'needs-revisions', 'fas fa-eye');
        $this->sections[$name]->template = 'Section/Translations.html.twig';
        $this->addSearchOptions($name, ['name', 'description', 'translation']);
        $this->addOrderOption($name, ['name'], 'code', 1);
        $this->addOrderOption($name, ['lastmod'], 'last-update');
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionTranslations($name = 'ListTranslation')
    {
        $this->addListSection($name, 'Translation', 'translations', 'fas fa-copy');
        $this->sections[$name]->template = 'Section/Translations.html.twig';
        $this->addSearchOptions($name, ['name', 'description', 'translation']);
        $this->addOrderOption($name, ['name'], 'code', 1);
        $this->addOrderOption($name, ['lastmod'], 'last-update');

        /// buttons
        $button = [
            'action' => 'AddTranslation',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link',
        ];
        $this->addButton($name, $button);
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
            case 'json':
                $this->jsonExport();
                return false;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Export content to a JSON file.
     */
    protected function jsonExport()
    {
        $this->setTemplate(false);
        $json = [];
        $language = $this->getMainModel();
        $translation = new Translation();

        if (!empty($language->parentcode)) {
            $where = [new DataBaseWhere('langcode', $language->parentcode)];
            foreach ($translation->all($where, ['name' => 'ASC'], 0, 0) as $transParent) {
                $json[$transParent->name] = $this->toolBox()->utils()->fixHtml($transParent->translation);
            }
        }

        $where = [new DataBaseWhere('langcode', $language->langcode)];
        foreach ($translation->all($where, ['name' => 'ASC'], 0, 0) as $trans) {
            $json[$trans->name] = $this->toolBox()->utils()->fixHtml($trans->translation);
        }
        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->setContent(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $language = $this->getMainModel();
        switch ($sectionName) {
            case 'EditLanguage':
                $this->sections[$sectionName]->loadData($language->primaryColumnValue());
                break;

            case 'language':
                $this->loadLanguage();
                break;

            case 'ListTranslation-rev':
                $where = [
                    new DataBaseWhere('langcode', $language->langcode),
                    new DataBaseWhere('needsrevision', true)
                ];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListTranslation':
                $where = [new DataBaseWhere('langcode', $language->langcode)];
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }

    protected function loadLanguage()
    {
        if (!$this->getMainModel(true)->exists()) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        $this->title = $this->languageModel->langcode;
        $this->description = $this->languageModel->description;
    }
}
