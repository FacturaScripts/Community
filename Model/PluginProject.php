<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Description of PluginProject
 *
 * @author carlos
 */
class PluginProject extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Creation date.
     *
     * @var string
     */
    public $date;
    
    /**
     *
     * @var string
     */
    public $description;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Contact identifier.
     *
     * @var int
     */
    public $idcontacto;

    /**
     * Project identifier.
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
        $this->date = date('d-m-Y');
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pluginprojects';
    }
}
