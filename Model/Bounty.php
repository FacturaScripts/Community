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
 * Description of Bounty model.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class Bounty extends Base\ModelClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

    /**
     * Foreign key with table contactos
     * 
     * @var integer
     */
    public $author;

    /**
     * Foreign key with table contactos
     * 
     * @var integer
     */
    public $assigned;

    /**
     *
     * @var bool
     */
    public $closed;

    /**
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
     * Primary key
     * 
     * @var integer
     */
    public $idbounty;

    /**
     * Foreign key with table webprojects.
     * 
     * @var integer
     */
    public $idproject;

    /**
     * Foreign key with table webteams.
     * 
     * @var integer
     */
    public $idteam;

    /**
     * 
     * @var integer
     */
    public $points;

    /**
     * 
     * @var string
     */
    public $status;

    /**
     * 
     * @var string
     */
    public $title;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->closed = false;
        $this->date = date('d-m-Y');
        $this->points = 0;
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idbounty';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'bounties';
    }

    public function test()
    {
        $this->description = Utils::noHtml($this->description);
        $this->title = Utils::noHtml($this->title);

        return parent::test();
    }
}
