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
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of TranslationList
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class TranslationList extends SectionController
{

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        /// translations
        $this->addListSection('ListTranslation', 'Translation', 'translations', 'fas fa-copy');
        $this->addSearchOptions('ListTranslation', ['name', 'description', 'translation']);
        $this->addOrderOption('ListTranslation', ['name'], 'code');
        $this->addOrderOption('ListTranslation', ['lastmod'], 'last-update', 2);

        if ($this->contact) {
            $addButton = [
                'action' => 'AddTranslation',
                'icon' => 'fas fa-plus',
                'label' => 'new',
                'type' => 'link',
            ];
            $this->addButton('ListTranslation', $addButton);
        }

        /// languages
        $this->addListSection('ListLanguage', 'Language', 'languages', 'fas fa-language');
        $this->addSearchOptions('ListLanguage', ['langcode', 'description']);
        $this->addOrderOption('ListLanguage', ['langcode'], 'code');
        $this->addOrderOption('ListLanguage', ['description'], 'description');
        $this->addOrderOption('ListLanguage', ['lastmod'], 'last-update');
        $this->addOrderOption('ListLanguage', ['numtranslations'], 'number-of-translations', 2);

        if ($this->user) {
            $importButton = [
                'action' => $this->url() . '?action=import-lang',
                'label' => 'import',
                'type' => 'link',
            ];
            $this->addButton('ListLanguage', $importButton);
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
            case 'import-lang':
                $this->importLanguagesAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Code for import languages action.
     */
    protected function importLanguagesAction()
    {
        if (!$this->user) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return;
        }

        foreach ($this->i18n->getAvailableLanguages() as $key => $value) {
            $language = new Language();
            $language->langcode = $key;
            $language->description = $value;
            $language->save();
        }
    }
}
