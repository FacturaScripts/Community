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
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Plugins\Community\Lib\PluginBuildValidator;

/**
 * Description of WebBuild
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
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

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->beta = true;
        $this->date = date('d-m-Y');
        $this->downloads = 0;
        $this->hour = date('H:i:s');
        $this->stable = false;
        $this->version = 0.1;
    }

    /**
     * Remove the model data from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $attachedFile = $this->getAttachedFile();
        if ($attachedFile->delete()) {
            return parent::delete();
        }

        return false;
    }

    /**
     * Returns the file name for this build.
     *
     * @return string
     */
    public function fileName(): string
    {
        $extension = 'zip';
        $atFile = $this->getAttachedFile();
        if ($atFile) {
            $parts = explode('.', $atFile->filename);
            $extension = end($parts);
        }

        $project = new WebProject();
        if ($project->loadFromCode($this->idproject)) {
            return $project->name . '-' . $this->version . '.' . $extension;
        }

        return $this->idbuild . '.' . $extension;
    }

    /**
     * Return the attached file to this build.
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

    /**
     * Increase download counter.
     */
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

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// to force check this table.
        new AttachedFile();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idbuild';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'webbuilds';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if (empty($this->idfile)) {
            $filePath = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . $this->path;
            if (!$this->testPlugin($filePath)) {
                unlink($filePath);
                return false;
            }

            $attachedFile = new AttachedFile();
            $attachedFile->path = $this->path;
            if (!$attachedFile->save()) {
                unlink($filePath);
                return false;
            }

            $this->idfile = $attachedFile->idfile;
            $this->path = $attachedFile->path;
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
        return parent::url($type, 'ListWebProject?active=List');
    }

    /**
     * 
     * @param string $filePath
     *
     * @return bool
     */
    protected function testPlugin($filePath)
    {
        $project = new WebProject();
        if (!$project->loadFromCode($this->idproject)) {
            return false;
        }

        if (!$project->plugin) {
            return true;
        }

        /// is a zip file?
        if (mime_content_type($filePath) !== 'application/zip') {
            self::$miniLog->alert(self::$i18n->trans('only-zip-files'));
            return false;
        }

        /// is zip file ok?
        $params = ['version' => $this->version, 'name' => $project->name];
        $validator = new PluginBuildValidator();
        if ($validator->validateZip($filePath, $params)) {
            return true;
        }

        return false;
    }
}
