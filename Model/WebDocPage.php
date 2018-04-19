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
 * Description of WebDocPage model.
 *
 * @author Carlos García Gómez
 */
class WebDocPage extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Page text.
     * 
     * @var string
     */
    public $body;

    /**
     *
     * @var string
     */
    public $creationdate;

    /**
     * Primary key.
     *
     * @var int
     */
    public $iddoc;

    /**
     * Related project key.
     *
     * @var int
     */
    public $idproject;

    /**
     * Title of the document page.
     *
     * @var string
     */
    public $title;

    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y');
    }

    public static function primaryColumn()
    {
        return 'iddoc';
    }

    public static function tableName()
    {
        return 'webdocpages';
    }
}
