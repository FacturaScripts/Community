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
use FacturaScripts\Plugins\webportal\Model\WebPage;

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

    /**
     *
     * @var int
     */
    public $nummembers;

    /**
     *
     * @var int
     */
    public $numrequests;

    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y');
        $this->nummembers = 0;
        $this->numrequests = 0;
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

        if (empty($this->creationdate)) {
            $this->creationdate = date('d-m-Y');
        }

        $this->updateStats();
        return parent::test();
    }

    public function updateStats()
    {
        $member = new WebTeamMember();
        $whereMembers = [
            new DataBaseWhere('idteam', $this->idteam),
            new DataBaseWhere('accepted', true)
        ];
        $this->nummembers = $member->count($whereMembers);

        $whereRequests = [
            new DataBaseWhere('idteam', $this->idteam),
            new DataBaseWhere('accepted', true, '!=')
        ];
        $this->numrequests = $member->count($whereRequests);
    }

    public function url(string $type = 'auto', string $list = 'List')
    {
        $webPage = new WebPage();
        if ($type === 'public') {
            foreach ($webPage->all([new DataBaseWhere('customcontroller', 'TeamList')]) as $wpage) {
                return $wpage->url('link');
            }
        } elseif ($type != 'list') {
            foreach ($webPage->all([new DataBaseWhere('customcontroller', 'EditWebTeam')]) as $wpage) {
                return $wpage->url('link') . $this->idteam;
            }
        }

        return parent::url($type, $list);
    }
}
