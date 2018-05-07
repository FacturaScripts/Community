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

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Model\AttachedFile;

/**
 * Description of WebFile
 *
 * @author Carlos García Gómez
 */
class WebBuild extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var bool
     */
    public $beta;

    /**
     * Creation date.
     *
     * @var string
     */
    public $date;

    /**
     *
     * @var int
     */
    public $downloads;

    /**
     *
     * @var string
     */
    public $hour;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idbuild;

    /**
     * Id of the attached file.
     *
     * @var int
     */
    public $idfile;

    /**
     * Project id.
     *
     * @var int
     */
    public $idproject;

    /**
     * File path.
     *
     * @var string
     */
    public $path;

    /**
     *
     * @var bool
     */
    public $stable;

    /**
     *
     * @var float
     */
    public $version;

    public function clear()
    {
        parent::clear();
        $this->beta = true;
        $this->date = date('d-m-Y');
        $this->downloads = 0;
        $this->hour = date('H:i:s');
        $this->stable = false;
        $this->version = 1.0;
    }

    public function delete()
    {
        $attachedFile = $this->getAttachedFile();
        if ($attachedFile->delete()) {
            return parent::delete();
        }

        return false;
    }

    /**
     * 
     * @return AttachedFile
     */
    public function getAttachedFile()
    {
        $attachedFile = new AttachedFile();
        if ($attachedFile->loadFromCode($this->idfile)) {
            return $attachedFile;
        }

        return null;
    }

    public function increaseDownloads()
    {
        if ($this->downloads < 100 && mt_rand(0, 1) == 0) {
            $this->downloads += 2;
            $this->save();
        } elseif ($this->downloads >= 100 && mt_rand(0, 9) === 0) {
            $this->downloads += 10;
            $this->save();
        }
    }

    public function install()
    {
        /// to force check this table.
        new AttachedFile();

        return parent::install();
    }

    public static function primaryColumn()
    {
        return 'idbuild';
    }

    public static function tableName()
    {
        return 'webbuilds';
    }

    public function test()
    {
        if (null === $this->idfile) {
            $attachedFile = new AttachedFile();
            $attachedFile->path = $this->path;
            if (!$attachedFile->save()) {
                return false;
            }

            if ($attachedFile->mimetype !== 'application/zip') {
                self::$miniLog->alert(self::$i18n->trans('only-zip-files'));
                return false;
            }

            $this->idfile = $attachedFile->idfile;
            $this->path = $attachedFile->path;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List')
    {
        return parent::url($type, 'ListWebProject?active=List');
    }
}
