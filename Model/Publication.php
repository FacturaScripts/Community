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
use FacturaScripts\Plugins\webportal\Lib\WebPortal\Permalink;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\Widget\Markdown;
use FacturaScripts\Plugins\webportal\Model\Base\WebPageClass;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of Publication
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class Publication extends WebPageClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

    const PERMALINK_LENGTH = 90;

    /**
     *
     * @var string
     */
    public $body;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $idpublication;

    /**
     * Foreign key with webprojects table.
     *
     * @var integer
     */
    public $idproject;

    /**
     * Foreign key with webteam table.
     *
     * @var integer
     */
    public $idteam;

    /**
     *
     * @var string
     */
    public $permalink;

    /**
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
     * 
     * @return string
     */
    public function body($mode = 'raw')
    {
        switch ($mode) {
            case 'html':
                return Markdown::render($this->body);

            case 'raw':
                return $this->toolBox()->utils()->fixHtml($this->body);

            default:
                return $this->body;
        }
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
        $text = strip_tags($this->body('html'));
        $noLineBreaks = preg_replace("/\r|\n/", " ", $text);
        return $this->toolBox()->utils()->trueTextBreak($noLineBreaks, $length);
    }

    /**
     * 
     * @return WebProject
     */
    public function getProject()
    {
        $webProject = new WebProject();
        $webProject->loadFromCode($this->idproject);
        return $webProject;
    }

    /**
     * 
     * @return WebTeam
     */
    public function getTeam()
    {
        $webTeam = new WebTeam();
        $webTeam->loadFromCode($this->idteam);
        return $webTeam;
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idpublication';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'publications';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->body = $this->toolBox()->utils()->noHtml($this->body);
        $this->title = $this->toolBox()->utils()->noHtml($this->title);

        if (strlen($this->title) < 1 || strlen($this->title) > 200) {
            $this->toolBox()->i18nLog()->error('invalid-column-lenght', ['%column%' => 'title', '%min%' => '1', '%max%' => '200']);
            return false;
        }

        $this->permalink = is_null($this->permalink) ? $this->newPermalink() : $this->permalink;
        return parent::test();
    }

    /**
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
                return $this->getCustomUrl($type) . $this->permalink;
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

        $controller = ('public-list' === $type) ? 'TeamList' : 'EditPublication';
        $webPage = new WebPage();
        foreach ($webPage->all([new DataBaseWhere('customcontroller', $controller)]) as $wpage) {
            self::$urls[$type] = $wpage->url('public');
            return self::$urls[$type];
        }

        return '#';
    }

    /**
     * Generates a new permalink.
     *
     * @return string
     */
    private function newPermalink()
    {
        $permalink = Permalink::get($this->title, self::PERMALINK_LENGTH);

        /// Are there more pages with this permalink?
        foreach ($this->all([new DataBaseWhere('permalink', $permalink)]) as $coincidence) {
            if ($coincidence->idpublication != $this->idpublication) {
                return $permalink . '-' . mt_rand(2, 999);
            }
        }

        return $permalink;
    }
}
