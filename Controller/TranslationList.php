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

use FacturaScripts\Plugins\Community\Model\Language;
use FacturaScripts\Plugins\Community\Model\Translation;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of TranslationList
 *
 * @author Carlos García Gómez
 */
class TranslationList extends SectionController
{

    protected function createSections()
    {
        $this->addListSection('languages', 'Language', 'Section/Languages', 'languages', 'fa-language');
        $this->addSearchOptions('languages', ['description']);
        $this->addOrderOption('languages', 'langcode', 'code', 1);
        $this->addOrderOption('languages', 'description', 'description');
        $this->addOrderOption('languages', 'lastmod', 'last-update');
        $this->addButton('languages', $this->url() . '?action=import-lang', 'import', '');

        $this->addListSection('translations', 'Translation', 'Section/Translations', 'translations', 'fa-copy');
        $this->addSearchOptions('translations', ['name', 'description', 'translation']);
        $this->addOrderOption('translations', 'name', 'code', 1);
        $this->addOrderOption('translations', 'lastmod', 'last-update');
        $this->addButton('translations', $this->url() . '?action=import-trans', 'import', '');
    }

    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'import-lang':
                $this->importLanguagesAction();
                return true;

            case 'import-trans':
                $this->importTranslationsAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    protected function importLanguagesAction()
    {
        if (!$this->user) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
        }

        foreach ($this->i18n->getAvailableLanguages() as $key => $value) {
            $language = new Language();
            $language->langcode = $key;
            $language->description = $value;
            $language->save();
        }
    }

    protected function importTranslationsAction()
    {
        if (!$this->user) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
        }

        $json = json_decode(file_get_contents(FS_FOLDER . '/Core/Translation/en_EN.json'), true);
        foreach ($json as $key => $value) {
            $translation = new Translation();
            $translation->langcode = 'en_EN';
            $translation->name = $key;
            $translation->description = $translation->translation = $value;
            if ($translation->exists()) {
                break;
            }
            $translation->save();
        }
    }

    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'languages':
                $this->loadListSection($sectionName);
                break;

            case 'translations':
                $this->loadListSection($sectionName);
                break;
        }
    }
}
