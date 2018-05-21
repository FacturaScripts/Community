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

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;

/**
 * Description of WebTeam
 *
 * @author carlos
 */
class WebTeam extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Creation date.
     *
     * @var string
     */
    public $creationdate;

    /**
     *
     * @var string
     */
    public $description;

    /**
     * Contact identifier.
     *
     * @var int
     */
    public $idcontacto;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idteam;

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

    /**
     * Returns a maximun legth of $legth form the body property of this block.
     * 
     * @param int $length
     *
     * @return string
     */
    public function description(int $length = 300): string
    {
        $description = '';
        foreach (explode(' ', $this->description) as $word) {
            if (mb_strlen($description . $word . ' ') >= $length) {
                break;
            }

            $description .= $word . ' ';
        }

        return trim($description);
    }

    public static function primaryColumn()
    {
        return 'idteam';
    }

    public static function tableName()
    {
        return 'webteams';
    }

    public function test()
    {
        $this->description = Utils::noHtml($this->description);
        $this->name = Utils::noHtml($this->name);

        if (strlen($this->name) < 1) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'name', '%min%' => '1', '%max%' => '50']));
            return false;
        }

        return parent::test();
    }
}
