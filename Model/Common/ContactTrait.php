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
namespace FacturaScripts\Plugins\Community\Model\Common;

use FacturaScripts\Dinamic\Model\Contacto;

/**
 * Trait to use in models with idcontact column.
 *
 * @author Carlos García Gómez
 */
trait ContactTrait
{

    /**
     * Contact identifier.
     *
     * @var int
     */
    public $idcontacto;

    /**
     *
     * @var Contacto[]
     */
    private static $contacts = [];

    /**
     * Return the actual contact.
     *
     * @return Contacto
     */
    public function getContact()
    {
        $contact = new Contacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    /**
     * Returns contact name.
     *
     * @return string
     */
    public function getContactName(): string
    {
        if (empty($this->idcontacto)) {
            return '-';
        }

        if (isset(self::$contacts[$this->idcontacto])) {
            return self::$contacts[$this->idcontacto]->fullName();
        }

        self::$contacts[$this->idcontacto] = new Contacto();
        if (self::$contacts[$this->idcontacto]->loadFromCode($this->idcontacto)) {
            return self::$contacts[$this->idcontacto]->fullName();
        }

        return '-';
    }
}
