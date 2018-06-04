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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Plugins\webportal\Model\Base\WebPageClass;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of Issue model.
 *
 * @author Carlos García Gómez
 */
class Issue extends WebPageClass
{

    use Base\ModelTrait;

    /**
     * Page text.
     * 
     * @var string
     */
    public $body;

    /**
     *
     * @var string
     */
    public $creationroute;

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
    public $idissue;

    /**
     * Related project key.
     *
     * @var int
     */
    public $idproject;

    /**
     * Identifier of the team assigned to solve this issue.
     *
     * @var int
     */
    public $idteam;

    /**
     * Related contact form tree key.
     *
     * @var int
     */
    public $idtree;

    /**
     * Title of the document page.
     *
     * @var string
     */
    public $title;

    /**
     *
     * @var array
     */
    private static $urls = [];

    /**
     * Returns a maximun legth of $legth form the body property of this block.
     * 
     * @param int $length
     *
     * @return string
     */
    public function description(int $length = 300): string
    {
        return Utils::trueTextBreak($this->body, $length);
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idissue';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'issues';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->body = Utils::noHtml($this->body);
        $this->title = Utils::noHtml($this->title);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List')
    {
        switch ($type) {
            case 'public':
                return $this->getCustomUrl($type) . $this->idissue;
        }

        return parent::url($type, $list);
    }

    protected function getCustomUrl(string $type): string
    {
        if (isset(self::$urls[$type])) {
            return self::$urls[$type];
        }

        $controller = 'EditIssue';
        $webPage = new WebPage();
        foreach ($webPage->all([new DataBaseWhere('customcontroller', $controller)]) as $wpage) {
            self::$urls[$type] = $wpage->url('public');
            return self::$urls[$type];
        }

        return '#';
    }
}
