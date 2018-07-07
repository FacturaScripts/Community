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

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;

/**
 * Description of Translation
 *
 * @author Raul Jimenez <raul.jimenez@nazcanetworks.com>
 */
class Translation extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
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
     * Name
     * 
     * @var string 
     */
    public $name;

    /**
     * Translation of text in a language.
     * 
     * @var string
     */
    public $translation;

    public function clear()
    {
        parent::clear();
        $this->lastmod = date('d-m-Y H:i:s');
    }

    public function install()
    {
        new Language();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return int
     */
    public static function primaryColumn()
    {
        return 'id';
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
        return parent::test();
    }
}
