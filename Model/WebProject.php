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
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of WebProject model.
 *
 * @author Carlos García Gómez
 */
class WebProject extends Base\ModelClass
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
    public $idproject;

    /**
     * Project name.
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var bool
     */
    public $plugin;

    /**
     *
     * @var string
     */
    public $publicrepo;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y');
        $this->plugin = true;
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

    /**
     * Returns contact name.
     *
     * @return string
     */
    public function getContactName()
    {
        $contact = new Contacto();
        if ($contact->loadFromCode($this->idcontacto)) {
            return $contact->fullName();
        }

        return '-';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idproject';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'webprojects';
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
        if (strlen($this->name) < 1) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'name', '%min%' => '1', '%max%' => '50']));
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List')
    {
        $webPage = new WebPage();
        if ($type === 'link-all') {
            foreach ($webPage->all([new DataBaseWhere('customcontroller', 'PluginList')]) as $wpage) {
                return $wpage->url('link');
            }
        } elseif ($type === 'link') {
            foreach ($webPage->all([new DataBaseWhere('customcontroller', 'ViewPlugin')]) as $wpage) {
                return $wpage->url('link') . $this->name;
            }
        }

        return parent::url($type, $list);
    }
}
