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
use FacturaScripts\Plugins\webportal\Lib\WebPortal\Permalink;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\Widget\Markdown;
use FacturaScripts\Plugins\webportal\Model\Base\WebPageClass;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of WebDocPage model.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WebDocPage extends WebPageClass
{

    use Base\ModelTrait;

    const CUSTOM_CONTROLLER = 'WebDocumentation';
    const MAX_PERMALINK_LENGTH = 250;
    const PERMALINK_LENGTH = 90;

    /**
     * Page text.
     *
     * @var string
     */
    public $body;

    /**
     * Primary key.
     *
     * @var int
     */
    public $iddoc;

    /**
     * Parent doc page.
     *
     * @var int
     */
    public $idparent;

    /**
     * Related project key.
     *
     * @var int
     */
    public $idproject;

    /**
     *
     * @var string
     */
    public $permalink;

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
     * 
     * @return string
     */
    public function body($mode = 'raw')
    {
        switch ($mode) {
            case 'html':
                return Markdown::render($this->body);

            case 'raw':
                return Utils::fixHtml($this->body);

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
        return Utils::trueTextBreak($this->body, $length);
    }

    /**
     * Returns children items. Items with this page as parent.
     *
     * @return WebDocPage[]
     */
    public function getChildrenPages(): array
    {
        $where = [
            new DataBaseWhere('idparent', $this->iddoc),
            new DataBaseWhere('langcode', $this->langcode)
        ];
        return $this->all($where, ['ordernum' => 'ASC'], 0, 0);
    }

    /**
     * Returns the parent page.
     *
     * @return WebDocPage
     */
    public function getParentPage()
    {
        return $this->get($this->idparent);
    }

    /**
     * Returns items with the same parent or project.
     *
     * @return WebDocPage[]
     */
    public function getSisterPages(): array
    {
        $operator = is_null($this->idparent) ? 'IS' : '=';
        $where = [
            new DataBaseWhere('idparent', $this->idparent, $operator),
            new DataBaseWhere('idproject', $this->idproject),
            new DataBaseWhere('langcode', $this->langcode)
        ];
        return $this->all($where, ['ordernum' => 'ASC'], 0, 0);
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'iddoc';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'webdocpages';
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

        if (strlen($this->title) < 1 || strlen($this->title) > 200) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'title', '%min%' => '1', '%max%' => '200']));
        }

        $this->permalink = is_null($this->permalink) ? $this->newPermalink() : $this->permalink;
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
        if (in_array($type, ['public', 'public-list'])) {
            $url = $this->getCustomUrl($type);
            if ($type === 'public-list') {
                return $url;
            }

            return empty($this->permalink) ? $url : $url . '/' . $this->permalink;
        }

        return parent::url($type, 'ListWebProject?active=List');
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

        $webPage = new WebPage();
        $where = [
            new DataBaseWhere('customcontroller', self::CUSTOM_CONTROLLER),
            new DataBaseWhere('langcode', $this->langcode)
        ];
        foreach ($webPage->all($where) as $wpage) {
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
        $permalink = null;
        if (!is_null($this->idparent)) {
            /// gets parent page to use on permalink
            $parent = $this->getParentPage();
            $permalink = $parent->permalink . '/' . Permalink::get($this->title, self::PERMALINK_LENGTH);
        }

        if (is_null($permalink) || strlen($permalink) > self::MAX_PERMALINK_LENGTH) {
            $permalink = $this->idproject . '/' . Permalink::get($this->title, self::PERMALINK_LENGTH);
        }

        /// Are there more pages with this permalink?
        foreach ($this->all([new DataBaseWhere('permalink', $permalink)]) as $coincidence) {
            if ($coincidence->iddoc != $this->iddoc) {
                return $permalink . '-' . mt_rand(2, 999);
            }
        }

        return $permalink;
    }
}
