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
namespace FacturaScripts\Plugins\Community\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of Translation
 *
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class Translation extends Base\ModelOnChangeClass
{

    use Base\ModelTrait;

    /**
     * Description of Translation
     *
     * @var string
     */
    public $description;

    /**
     * Primary key
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var int
     */
    public $idproject;

    /**
     * Language code
     *
     * @var string
     */
    public $langcode;

    /**
     *
     * @var int
     */
    public $lastidcontacto;

    /**
     * Last modification date.
     *
     * @var string
     */
    public $lastmod;

    /**
     * Name
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var bool
     */
    public $needsrevision;

    /**
     * Translation of text in a language.
     *
     * @var string
     */
    public $translation;

    /**
     *
     * @var int
     */
    private static $idcontacto;

    /**
     *
     * @var array
     */
    private static $urls = [];

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->lastmod = date('d-m-Y H:i:s');
        $this->needsrevision = true;
    }

    /**
     * 
     * @return self[]
     */
    public function getChildren()
    {
        $children = [];

        $childrenLanguages = [];
        $language = $this->getLanguage();
        foreach ($language->all([], [], 0, 0) as $lang) {
            if ($lang->parentcode == $language->langcode) {
                $childrenLanguages[] = $lang->langcode;
            }
        }

        foreach ($this->getEquivalents() as $trans) {
            if (in_array($trans->langcode, $childrenLanguages)) {
                $children[] = $trans;
            }
        }

        return $children;
    }

    /**
     * Returns equivalent translations.
     * 
     * @param string $name
     *
     * @return self[]
     */
    public function getEquivalents($name = '')
    {
        $findName = empty($name) ? $this->name : $name;
        $where = [
            new DataBaseWhere('name', $findName),
            new DataBaseWhere('id', $this->id, '!=')
        ];
        return $this->all($where, [], 0, 0);
    }

    /**
     * 
     * @return Language
     */
    public function getLanguage()
    {
        $language = new Language();
        $language->loadFromCode($this->langcode);
        return $language;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Language();
        new WebProject();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * 
     * @return string
     */
    public function primaryDescription()
    {
        return $this->langcode . '/' . $this->name;
    }

    /**
     * 
     * @param int $idcontacto
     */
    public static function setCurrentContact($idcontacto)
    {
        self::$idcontacto = $idcontacto;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'translations';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->description = Utils::noHtml($this->description);
        $this->name = Utils::noHtml($this->name);
        $this->translation = Utils::noHtml($this->translation);

        if (!preg_match('/^[a-zA-Z0-9_\-\+]{2,100}$/', $this->name)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-name') . ' ' . $this->name);
            return false;
        }

        /// set current contact id
        if (!empty(self::$idcontacto)) {
            $this->lastidcontacto = self::$idcontacto;
        }

        return parent::test();
    }

    public function updateChildren()
    {
        $mainLangCode = AppSettings::get('community', 'mainlanguage');
        if ($this->langcode == $mainLangCode) {
            foreach ($this->getEquivalents() as $trans) {
                $trans->needsrevision = true;
                $trans->save();
            }
        }

        foreach ($this->getChildren() as $child) {
            if ($child->needsrevision) {
                $child->description = $this->description;
                $child->translation = $this->translation;
                $child->needsrevision = false;
                $child->save();
            }
        }
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        switch ($type) {
            case 'public-list':
                return $this->getCustomUrl($type);
        }

        return parent::url($type, $list);
    }

    /**
     * Return the public url from custom controller.
     *
     * @param string $type
     *
     * @return string
     */
    protected function getCustomUrl(string $type): string
    {
        if (isset(self::$urls[$type])) {
            return self::$urls[$type];
        }

        $controller = 'TranslationList';
        $webPage = new WebPage();
        foreach ($webPage->all([new DataBaseWhere('customcontroller', $controller)]) as $wpage) {
            self::$urls[$type] = $wpage->url('public');
            return self::$urls[$type];
        }

        return '#';
    }

    /**
     * 
     * @param string $translation
     *
     * @return bool
     */
    protected function newTeamLog($translation)
    {
        $teamLog = new WebTeamLog();
        $teamLog->description = self::$i18n->trans($translation, ['%name%' => $this->primaryDescription()]);
        $teamLog->idcontacto = self::$idcontacto;
        $teamLog->idteam = (int) AppSettings::get('community', 'idteamtra');
        $teamLog->link = $this->url('public');
        return $teamLog->save();
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'translation':
                $this->newTeamLog('updated-translation');
                return true;

            default:
                return parent::onChange($field);
        }
    }

    protected function onDelete()
    {
        $mainLangCode = AppSettings::get('community', 'mainlanguage');
        if ($this->langcode == $mainLangCode) {
            $this->newTeamLog('deleted-translation');
        }
    }

    protected function onInsert()
    {
        $mainLangCode = AppSettings::get('community', 'mainlanguage');
        if ($this->langcode == $mainLangCode) {
            $this->newTeamLog('created-translation');
        }
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['translation'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
