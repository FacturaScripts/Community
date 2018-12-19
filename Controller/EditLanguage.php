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
use FacturaScripts\Core\Base\Utils;
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
     * A list of main translations.
     *
     * @var array
     */
    private $mainTranslations = [];

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
     * Get a list of the parent languages.
     *
     * @return array
     */
    public function getParentLanguages(): array
    {
        $current = $this->getMainModel();
        $languages = [];
        foreach ($current->all([], ['langcode' => 'ASC'], 0, 0) as $language) {
            if ($language->langcode == $current->langcode) {
                continue;
            }

            if ($language->parentcode) {
                continue;
            }

            $languages[] = $language;
        }

        return $languages;
    }

    /**
     * Check available translations with translation name.
     *
     * @param Language $language
     * @param string   $translationName
     *
     * @return bool
     */
    private function checkTranslation(&$language, $translationName): bool
    {
        $mainLangCode = AppSettings::get('community', 'mainlanguage');
        if ($language->langcode === $mainLangCode) {
            return true;
        }

        if (empty($this->mainTranslations)) {
            $this->mainTranslations = [];
            $translation = new Translation();
            $where = [new DataBaseWhere('langcode', $mainLangCode)];
            foreach ($translation->all($where, [], 0, 0) as $trans) {
                $this->mainTranslations[] = $trans->name;
            }
        }

        return in_array($translationName, $this->mainTranslations);
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->fixedSection();

        $this->addHtmlSection('language', 'language', 'Section/Language');
        $language = $this->getMainModel();
        $this->addNavigationLink($language->url('public-list') . '?activetab=ListLanguage', $this->i18n->trans('languages'));

        $this->createSectionTranslations();
        $this->createSectionRevisions();

        /// admin
        if ($this->contactCanEdit()) {
            $this->addEditSection('EditLanguage', 'Language', 'edit', 'fas fa-edit', 'admin');
        }
    }

    protected function createSectionRevisions($name = 'ListTranslation-rev')
    {
        $this->addListSection($name, 'Translation', 'needs-revisions', 'fas fa-eye');
        $this->sections[$name]->template = 'Section/Translations.html.twig';
        $this->addSearchOptions($name, ['name', 'description', 'translation']);
        $this->addOrderOption($name, ['name'], 'code', 1);
        $this->addOrderOption($name, ['lastmod'], 'last-update');
    }

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

        if ($this->contactCanEdit()) {
            $language = $this->getMainModel();
            $button = [
                'action' => $language->url() . '&action=import-trans',
                'label' => 'import',
                'type' => 'link',
            ];
            $this->addButton($name, $button);
        }
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
            case 'import-trans':
                $this->importTranslationsAction();
                return true;

            case 'json':
                $this->jsonExport();
                return false;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Import all translations from Core.
     */
    protected function importTranslationsAction()
    {
        if (!$this->user) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return;
        }

        $language = $this->getMainModel();
        if ($language->parentcode) {
            $this->miniLog->alert("You can't import a language with parent.");
            return;
        }

        // import translations from file
        $newTranslations = [];
        $idproject = AppSettings::get('community', 'idproject');
        $json = (array) json_decode(file_get_contents(FS_FOLDER . '/Core/Translation/' . $language->langcode . '.json'), true);

        // start transaction
        $this->dataBase->beginTransaction();

        // main save process
        try {
            foreach ($json as $key => $value) {
                $translation = new Translation();
                $translation->idproject = $idproject;
                $translation->langcode = $language->langcode;
                $translation->name = $key;
                $translation->description = $translation->translation = $value;
                $translation->needsrevision = false;

                /// is this string in the main language?
                if (!$this->checkTranslation($language, $key)) {
                    continue;
                }

                if ($translation->save()) {
                    $newTranslations[] = $key;
                }
            }
            // confirm data
            $this->dataBase->commit();
        } catch (\Exception $e) {
            $this->miniLog->alert($e->getMessage());
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }

        // generate missing translations
        $mainLangCode = AppSettings::get('community', 'mainlanguage');
        foreach ($this->mainTranslations as $mainKey) {
            if (in_array($mainKey, $newTranslations)) {
                continue;
            }

            // we need main translation
            $mainTranslation = new Translation();
            $where = [
                new DataBaseWhere('langcode', $mainLangCode),
                new DataBaseWhere('name', $mainKey)
            ];
            $mainTranslation->loadFromCode('', $where);

            $newTranslation = new Translation();
            $newTranslation->description = $mainTranslation->description;
            $newTranslation->idproject = $idproject;
            $newTranslation->langcode = $language->langcode;
            $newTranslation->lastmod = $mainTranslation->lastmod;
            $newTranslation->name = $mainTranslation->name;
            $newTranslation->translation = $mainTranslation->translation;
            $newTranslation->save();
        }

        $language->updateStats();
        $language->save();
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
                $json[$transParent->name] = Utils::fixHtml($transParent->translation);
            }
        }

        $where = [new DataBaseWhere('langcode', $language->langcode)];
        foreach ($translation->all($where, ['name' => 'ASC'], 0, 0) as $trans) {
            $json[$trans->name] = Utils::fixHtml($trans->translation);
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
            $this->miniLog->warning($this->i18n->trans('no-data'));
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        $this->title = $this->languageModel->langcode;
        $this->description = $this->languageModel->description;
    }
}
