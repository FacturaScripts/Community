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
use FacturaScripts\Plugins\webportal\Model\Base\WebPageClass;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of ContactFormTree model.
 *
 * @author Carlos García Gómez
 */
class ContactFormTree extends WebPageClass
{

    use Base\ModelTrait;

    const CUSTOM_CONTROLLER = 'ContactForm';

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
    public $endaction;

    /**
     *
     * @var string
     */
    public $icon;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idtree;

    /**
     * Parent doc page.
     *
     * @var int
     */
    public $idparent;

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
     * Return the body content.
     *
     * @return string
     */
    public function body(): string
    {
        return Utils::fixHtml($this->body);
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->body = 'Seleccione la opción que más se ajuste a su caso:';
        $this->icon = 'fas fa-circle';
    }

    /**
     * Returns children items. Items with this page as parent.
     *
     * @return ContactFormTree[]
     */
    public function getChildrenPages(): array
    {
        $where = [
            new DataBaseWhere('idparent', $this->idtree),
            new DataBaseWhere('langcode', $this->langcode)
        ];
        return $this->all($where, ['ordernum' => 'ASC'], 0, 0);
    }

    /**
     * Returns the parent page.
     *
     * @return ContactFormTree
     */
    public function getParentPage()
    {
        return $this->get($this->idparent);
    }

    /**
     * Returns items with the same parent or project.
     *
     * @return ContactFormTree[]
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
        return 'idtree';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'contactformtrees';
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

        if (strlen($this->title) < 1) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'title', '%min%' => '1', '%max%' => '100']));
        }

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
            return ($type === 'public-list') ? $url : $url . '?code=' . $this->idtree;
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
}
