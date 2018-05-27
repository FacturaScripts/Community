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

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Model\Contacto;

/**
 * Description of WebTeamMember
 *
 * @author carlos
 */
class WebTeamMember extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var bool
     */
    public $accepted;

    /**
     * Creation date.
     *
     * @var string
     */
    public $creationdate;

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
     * Team identifier.
     *
     * @var int
     */
    public $idteam;

    public function clear()
    {
        parent::clear();
        $this->accepted = false;
        $this->creationdate = date('d-m-Y');
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
     * Returns team.
     * 
     * @return WebTeam
     */
    public function getTeam()
    {
        $team = new WebTeam();
        $team->loadFromCode($this->idteam);
        return $team;
    }

    public static function primaryColumn()
    {
        return 'id';
    }

    public static function tableName()
    {
        return 'webteams_members';
    }

    public function url(string $type = 'auto', string $list = 'List')
    {
        $team = new WebTeam();
        if ($type == 'accept' && $team->loadFromCode($this->idteam)) {
            return $team->url('public') . '?action=accept-request&idrequest=' . $this->id;
        }

        return parent::url($type, 'ListWebProject?active=List');
    }
}
