<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Model;

use FacturaScripts\Core\Model\Base;

/**
 * Description of WebProject model.
 *
 * @author Carlos García Gómez
 */
class WebProject extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $creationdate;

    /**
     *
     * @var int
     */
    public $idproject;

    /**
     *
     * @var string
     */
    public $name;

    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y');
    }

    public static function primaryColumn()
    {
        return 'idproject';
    }

    public static function tableName()
    {
        return 'webprojects';
    }
}
