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
namespace FacturaScripts\Plugins\Community\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Lib\WebTeamNotifications;
use FacturaScripts\Dinamic\Model\Contacto;

/**
 * Description of WebTeamMember
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * 
     * @param int $idcontacto
     *
     * @return bool
     */
    public function acceptedBy($idcontacto)
    {
        $this->accepted = true;
        if ($this->save()) {
            $byContact = new Contacto();
            $byContact->loadFromCode($idcontacto);
            $this->newTeamLog('accepted-on-team-by', ['%by%' => $byContact->alias()]);
            WebTeamNotifications::notifyAccept($this);
            return true;
        }

        $this->accepted = false;
        return false;
    }

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
     * 
     * @return bool
     */
    public function delete()
    {
        if (parent::delete()) {
            $this->getTeam()->updateStats();
            return true;
        }

        return false;
    }

    /**
     * 
     * @param bool $inactivity
     *
     * @return bool
     */
    public function expel($inactivity = false)
    {
        if ($this->delete()) {
            $translation = $inactivity ? 'expelled-from-team-inactivity' : 'expelled-from-team';
            $this->newTeamLog($translation);
            WebTeamNotifications::notifyExpel($this);
            return true;
        }

        return false;
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
     * 
     * @return bool
     */
    public function leave()
    {
        if ($this->delete()) {
            $this->newTeamLog('leaves-team');
            return true;
        }

        return false;
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
     * 
     * @return bool
     */
    public function save()
    {
        if (parent::save()) {
            $this->getTeam()->updateStats();
            return true;
        }

        return false;
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
        $this->observations = $this->toolBox()->utils()->noHtml($this->observations);
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
    public function url(string $type = 'auto', string $list = 'ListWebProject?active=List')
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

        return parent::url($type, $list);
    }

    /**
     * 
     * @param string $translation
     * @param array  $extra
     *
     * @return bool
     */
    protected function newTeamLog($translation, $extra = [])
    {
        $teamLog = new WebTeamLog();
        $extra['%name%'] = $this->getContactAlias();
        $teamLog->description = $this->toolBox()->i18n()->trans($translation, $extra);
        $teamLog->idcontacto = $this->idcontacto;
        $teamLog->idteam = $this->idteam;
        return $teamLog->save();
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            if (!$this->accepted) {
                $this->newTeamLog('wants-to-join-team');
            }

            return true;
        }

        return false;
    }
}
