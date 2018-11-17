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
namespace FacturaScripts\Plugins\Community\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of Language
 *
 * @author Raul Jimenez <raul.jimenez@nazcanetworks.com>
 */
class Language extends Base\ModelClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

    /**
     * Description of Language
     *
     * @var string
     */
    public $description;

    /**
     * Language code
     *
     * @var string
     */
    public $langcode;

    /**
     * Last modification date.
     *
     * @var string
     */
    public $lastmod;

    /**
     *
     * @var int
     */
    public $needsrevision;

    /**
     *
     * @var int
     */
    public $numtranslations;

    /**
     * Parent code for variations
     *
     * @var string
     */
    public $parentcode;

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
        $this->needsrevision = 0;
        $this->numtranslations = 0;
    }
    
    public function install()
    {
        /// needed dependencies
        new Contacto();
        
        return parent::install();
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'langcode';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'languages';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $lenLangCode = strlen($this->langcode);
        if ($lenLangCode < 1 || $lenLangCode > 8) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'langcode', '%min%' => '1', '%max%' => '8']));
        }

        $this->description = Utils::noHtml($this->description);
        $lenDesc = strlen($this->description);
        if ($lenDesc < 1 || $lenDesc > 50) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'description', '%min%' => '1', '%max%' => '50']));
        }

        if (empty($this->parentcode)) {
            $this->parentcode = null;
        }

        return parent::test();
    }

    /**
     * Updates language stats.
     */
    public function updateStats()
    {
        $where = [new DataBaseWhere('langcode', $this->langcode)];
        $translation = new Translation();
        $this->numtranslations = $translation->count($where);

        $where[] = new DataBaseWhere('needsrevision', true);
        $this->needsrevision = $translation->count($where);
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
}
