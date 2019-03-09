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
 * Description of WebProject model.
 *
 * @author Carlos García Gómez
 */
class WebProject extends WebPageClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

    const DEFAULT_TYPE = 'public';

    /**
     *
     * @var string
     */
    public $description;

    /**
     *
     * @var int
     */
    public $downloads;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idproject;
    
    /**
     *
     * @var int
     */
    public $idteam;

    /**
     *
     * @var string
     */
    public $license;

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
     * @var bool
     */
    public $private;

    /**
     *
     * @var string
     */
    public $publicrepo;

    /**
     *
     * @var string
     */
    public $type;

    /**
     *
     * @var string
     */
    public $version;

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
        $this->downloads = 0;
        $this->license = 'LGPL';
        $this->plugin = true;
        $this->private = false;
        $this->type = self::DEFAULT_TYPE;
        $this->version = 0.0;
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
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idproject';
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'name';
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
        if (strlen($this->name) < 1 || strlen($this->name) > 50) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'name', '%min%' => '1', '%max%' => '50']));
            return false;
        }

        if (!preg_match("/^[a-z0-9_-]+$/i", $this->name)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-name'));
            return false;
        }

        $this->private = false;
        switch ($this->type) {
            case 'private':
                $this->private = true;
                break;

            default:
                $this->type = self::DEFAULT_TYPE;
        }

        $this->lastmoddisable = true;
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

        $controller = ('public-list' === $type) ? 'PluginList' : 'ViewPlugin';
        $webPage = new WebPage();
        foreach ($webPage->all([new DataBaseWhere('customcontroller', $controller)]) as $wpage) {
            self::$urls[$type] = $wpage->url('public');
            return self::$urls[$type];
        }

        return '#';
    }
}
