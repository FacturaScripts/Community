<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\Permalink;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of WebDocPage model.
 *
 * @author Carlos García Gómez
 */
class WebDocPage extends Base\ModelClass
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
    public $creationdate;
    
    /**
     *
     * @var int
     */
    public $idcontacto;

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
     * Language code.
     *
     * @var string
     */
    public $langcode;

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

    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y');
        $this->langcode = substr(FS_LANG, 0, 2);
    }

    public function getChildrenPages()
    {
        $where = [
            new DataBaseWhere('idparent', $this->iddoc),
            new DataBaseWhere('langcode', $this->langcode)
        ];
        return $this->all($where, [], 0, 0);
    }

    public function getParentPage()
    {
        return $this->get($this->idparent);
    }

    public function getSisterPages()
    {
        $operator = is_null($this->idparent) ? 'IS' : '=';
        $where = [
            new DataBaseWhere('idparent', $this->idparent, $operator),
            new DataBaseWhere('idproject', $this->idproject),
            new DataBaseWhere('langcode', $this->langcode)
        ];
        return $this->all($where, [], 0, 0);
    }
    
    public function install()
    {
        /// needed as dependency
        new Contacto();
        
        return parent::install();
    }

    public static function primaryColumn()
    {
        return 'iddoc';
    }

    public static function tableName()
    {
        return 'webdocpages';
    }

    public function test()
    {
        $this->title = Utils::noHtml($this->title);
        $this->permalink = is_null($this->permalink) ? $this->idproject . '/' . Permalink::get($this->title, 150) : $this->permalink;

        return (strlen($this->title) > 1);
    }

    public function url(string $type = 'auto', string $list = 'List')
    {
        switch ($type) {
            case 'link':
                $url = '#';
                $webPage = new WebPage();
                if ($webPage->loadFromCode(AppSettings::get('community', 'docpage'))) {
                    $url = $webPage->permalink;
                }
                if ('*' === substr($url, -1)) {
                    $url = substr($url, 1, -1);
                }
                if ('/' === substr($url, -1)) {
                    $url = substr($url, 1, -1);
                }
                return empty($this->permalink) ? $url : $url . '/' . $this->permalink;

            default:
                return parent::url($type, $list);
        }
    }
}
