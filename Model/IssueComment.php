<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018-2019 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base;

/**
 * Description of Issue model.
 *
 * @author Carlos García Gómez
 */
class IssueComment extends Base\ModelClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

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
     * Primary key.
     *
     * @var int
     */
    public $idcomment;

    /**
     * Issue identifier.
     *
     * @var int
     */
    public $idissue;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y H:i:s');
    }

    /**
     * 
     * @return string
     */
    public function html()
    {
        $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
        $html = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $this->body);
        return nl2br($html);
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcomment';
    }

    /**
     * 
     * @param int $length
     *
     * @return string
     */
    public function resume(int $length = 60): string
    {
        return Utils::trueTextBreak($this->body, $length);
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'issuecomments';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->body = Utils::noHtml($this->body);
        return parent::test();
    }
}
