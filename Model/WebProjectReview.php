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
 * Description of WebProjectReview model.
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class WebProjectReview extends Base\ModelClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

    /**
     *
     * @var string
     */
    public $date;

    /**
     * Primary key
     *
     * @var integer
     */
    public $id;

    /**
     * Foreign key reference contactos table.
     *
     * @var integer
     */
    public $idcontacto;

    /**
     * Foreign key reference webprojects table.
     *
     * @var integer
     */
    public $idproject;

    /**
     *
     * @var string
     */
    public $observations;

    /**
     *
     * @var integer
     */
    public $score;

    /**
     *
     * @var float
     */
    public $version;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->date = date('d-m-Y');
        $this->score = 0;
        $this->version = 0.0;
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
        return 'webprojects_review';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if ($this->version < 0.0) {
            self::$miniLog->alert(self::$i18n->trans('invalid-quantity', ['%column%' => 'version', '%min%' => '0']));
            return false;
        }

        return parent::test();
    }
}
