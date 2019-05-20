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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Plugins\webportal\Model\WebPage;
use FacturaScripts\Plugins\webportal\Model\Base\WebPageClass;

/**
 * Description of WebTeam
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WebTeam extends WebPageClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

    /**
     *
     * @var int
     */
    public $defaultpublication;

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
     * @var int
     */
    public $maxinactivitydays;

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

    /**
     *
     * @var bool
     */
    public $private;

    /**
     *
     * @var array
     */
    private static $urls = [];

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->maxinactivitydays = 0;
        $this->nummembers = 0;
        $this->numrequests = 0;
        $this->private = false;
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
        return Utils::trueTextBreak($this->description, $length);
    }

    /**
     * 
     * @return string
     */
    public function html()
    {
        $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
        $html = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $this->description);
        return nl2br($html);
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idteam';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'webteams';
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
        if (!preg_match('/^[a-zA-Z0-9_\-\+]{1,50}$/', $this->name)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'name', '%min%' => '1', '%max%' => '50']));
            return false;
        }

        return parent::test();
    }

    /**
     * Update details for this team.
     */
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

        $this->save();
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
        switch ($type) {
            case 'public-list':
                return $this->getCustomUrl($type);

            case 'public':
                return $this->getCustomUrl($type) . $this->name;
        }

        return parent::url($type, $list);
    }

    /**
     * Return the public url from custom controller.
     *
     * @param string $type
     *
     * @return string
     */
    protected function getCustomUrl(string $type): string
    {
        if (isset(self::$urls[$type])) {
            return self::$urls[$type];
        }

        $controller = ('public-list' === $type) ? 'TeamList' : 'EditWebTeam';
        $webPage = new WebPage();
        foreach ($webPage->all([new DataBaseWhere('customcontroller', $controller)]) as $wpage) {
            self::$urls[$type] = $wpage->url('public');
            return self::$urls[$type];
        }

        return '#';
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
            /// adds owner as a member
            $member = new WebTeamMember();
            $member->accepted = true;
            $member->idcontacto = $this->idcontacto;
            $member->idteam = $this->idteam;
            $member->save();

            return true;
        }

        return false;
    }
}
