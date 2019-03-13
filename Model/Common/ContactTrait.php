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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\webportal\Model\WebPage;

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
     *
     * @var string
     */
    private static $profileUrl;

    /**
     * Return the actual contact.
     *
     * @return Contacto
     */
    public function getContact()
    {
        if (empty($this->idcontacto)) {
            return new Contacto();
        }

        if (isset(self::$contacts[$this->idcontacto])) {
            return self::$contacts[$this->idcontacto];
        }

        $contact = new Contacto();
        if ($contact->loadFromCode($this->idcontacto)) {
            self::$contacts[$this->idcontacto] = $contact;
        }

        return $contact;
    }

    /**
     * 
     * @return string
     */
    public function getContactAlias(): string
    {
        $contact = $this->getContact();
        return $contact->alias();
    }

    /**
     * 
     * @return string
     */
    public function getContactProfile(): string
    {
        if (empty($this->idcontacto)) {
            return '#';
        }

        $contact = $this->getContact();
        return $this->getProfileUrl($contact);
    }

    /**
     * 
     * @param Contacto $contact
     *
     * @return string
     */
    public function getProfileUrl($contact)
    {
        if (isset(self::$profileUrl)) {
            return self::$profileUrl . $contact->idcontacto;
        }

        $controller = 'ViewProfile';
        self::$profileUrl = $controller;

        $webPage = new WebPage();
        foreach ($webPage->all([new DataBaseWhere('customcontroller', $controller)]) as $wpage) {
            self::$profileUrl = $wpage->url('public');
            break;
        }

        return self::$profileUrl . $contact->idcontacto;
    }
}
