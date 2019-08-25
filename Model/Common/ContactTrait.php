<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    abstract protected function toolBox();

    /**
     * Return the actual contact.
     *
     * @return Contacto
     */
    public function getContact()
    {
        return $this->getCustomContact($this->idcontacto);
    }

    /**
     * 
     * @return string
     */
    public function getContactAlias(): string
    {
        return $this->getContact()->alias();
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

    /**
     * 
     * @param int $idcontacto
     *
     * @return Contacto
     */
    protected function getCustomContact($idcontacto)
    {
        if (empty($idcontacto)) {
            return new Contacto();
        }

        if (isset(self::$contacts[$idcontacto])) {
            return self::$contacts[$idcontacto];
        }

        if (empty(self::$contacts)) {
            self::$contacts = $this->toolBox()->cache()->get('CONTACT_TRAIT_CONTACTS');
        }

        $contact = new Contacto();
        if ($contact->loadFromCode($idcontacto)) {
            self::$contacts[$idcontacto] = $contact;
            $this->toolBox()->cache()->set('CONTACT_TRAIT_CONTACTS', self::$contacts);
        }

        return $contact;
    }
}
