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

use FacturaScripts\Core\Model\Base;

/**
 * Description of License
 *
 * @author Carlos García Gómez
 */
class License extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $description;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $title;

    /**
     * 
     * @return string
     */
    public static function primaryColumn()
    {
        return 'name';
    }

    /**
     * 
     * @return string
     */
    public static function tableName()
    {
        return 'licenses';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->description = $utils->noHtml($this->description);
        $this->name = $utils->noHtml($this->name);
        $this->title = $utils->noHtml($this->title);
        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListWebProject?activetab=List')
    {
        return parent::url($type, $list);
    }
}
