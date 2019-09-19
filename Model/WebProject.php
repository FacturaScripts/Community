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

    const DEFAULT_LICENSE = 'LGPL';
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
    public $imageurl;

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
        $this->license = self::DEFAULT_LICENSE;
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
        return $this->toolBox()->utils()->trueTextBreak($this->description, $length);
    }

    /**
     * 
     * @return WebBuild[]
     */
    public function getBuilds()
    {
        $build = new WebBuild();
        $where = [new DataBaseWhere('idproject', $this->idproject)];
        return $build->all($where, ['version' => 'DESC'], 0, 0);
    }

    /**
     * 
     * @return License
     */
    public function getLicense()
    {
        $license = new License();
        $license->loadFromCode($this->license);
        return $license;
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
        $utils = $this->toolBox()->utils();
        $this->description = $utils->noHtml($this->description);
        $this->imageurl = $utils->noHtml($this->imageurl);
        $this->name = $utils->noHtml($this->name);
        $this->publicrepo = $utils->noHtml($this->publicrepo);

        if (strlen($this->name) < 1 || strlen($this->name) > 50) {
            $this->toolBox()->i18nLog()->error('invalid-column-lenght', ['%column%' => 'name', '%min%' => '1', '%max%' => '50']);
            return false;
        }

        if (!preg_match("/^[A-Za-z]/", $this->name) || !preg_match("/^[a-z0-9_\-]+$/i", $this->name)) {
            $this->toolBox()->i18nLog()->error('invalid-name');
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

    public function updateStats()
    {
        $downloads = 0;
        $this->lastmod = $this->creationdate;
        $this->version = 0.0;

        foreach ($this->getBuilds() as $build) {
            $downloads += $build->downloads;

            if ($build->version > $this->version) {
                $this->version = $build->version;
                $this->lastmod = $build->date;
            }
        }

        $this->downloads = max([$downloads, $this->downloads]);
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

    /**
     * 
     * @param string $translation
     *
     * @return bool
     */
    protected function newTeamLog($translation)
    {
        $teamLog = new WebTeamLog();
        $teamLog->description = $this->toolBox()->i18n()->trans($translation, ['%pluginName%' => $this->name, '%version%' => $this->version]);
        $teamLog->idcontacto = $this->idcontacto;
        $teamLog->idteam = $this->toolBox()->appSettings()->get('community', 'idteamdev');
        $teamLog->link = $this->url('public');
        return $teamLog->save();
    }

    /**
     * 
     * @param string $field
     *
     * @return bool
     */
    protected function onChange($field)
    {
        switch ($field) {
            case 'version':
                if ($this->plugin) {
                    $this->newTeamLog('updated-plugin');
                }
                return true;

            default:
                return parent::onChange($field);
        }
    }

    protected function onDelete()
    {
        if ($this->plugin) {
            $this->newTeamLog('deleted-plugin');
        }
    }

    protected function onInsert()
    {
        if ($this->plugin) {
            $this->newTeamLog('new-plugin');
        }
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['version'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
