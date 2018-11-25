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
 * Description of WebTeamMember
 *
 * @author carlos
 */
class WebTeamMember extends Base\ModelClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

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
     * Team identifier.
     *
     * @var int
     */
    public $idteam;

    /**
     *
     * @var string
     */
    public $observations;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->accepted = false;
        $this->creationdate = date('d-m-Y');
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
        return 'webteams_members';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->observations = Utils::noHtml($this->observations);

        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        $team = new WebTeam();
        if ($team->loadFromCode($this->idteam)) {
            switch ($type) {
                case 'accept':
                    return $team->url('public') . '?action=accept-request&idrequest=' . $this->id;

                case 'expel':
                    return $team->url('public') . '?action=expel&idrequest=' . $this->id;
            }
        }

        return parent::url($type, 'ListWebProject?active=List');
    }
}
