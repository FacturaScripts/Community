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

    public static function primaryColumn()
    {
        return 'name';
    }

    public static function tableName()
    {
        return 'licenses';
    }

    public function test()
    {
        $this->description = Utils::noHtml($this->description);
        $this->name = Utils::noHtml($this->name);
        $this->title = Utils::noHtml($this->title);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListWebProject?activetab=List')
    {
        return parent::url($type, $list);
    }
}
