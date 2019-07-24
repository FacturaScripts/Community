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
namespace FacturaScripts\Plugins\Community\Lib;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use ZipArchive;

/**
 * Description of PluginBuildValidator
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PluginBuildValidator
{

    /**
     *
     * @var Translator
     */
    protected $i18n;

    /**
     *
     * @var MiniLog
     */
    protected $minilog;

    public function __construct()
    {
        $this->i18n = new Translator();
        $this->minilog = new MiniLog();
    }

    /**
     * 
     * @param string $path
     * @param array  $params
     *
     * @return bool
     */
    public function validateIni($path, $params)
    {
        $ini = parse_ini_file($path);
        foreach ($params as $key => $value) {
            if (!isset($ini[$key])) {
                $this->minilog->alert($this->i18n->trans('facturascripts-ini-key-not-found', ['%key%' => $key]));
                return false;
            }

            if ($ini[$key] != $value) {
                $this->minilog->alert(
                    $this->i18n->trans('facturascripts-ini-wrong-value', ['%key%' => $key, '%value%' => $ini[$key], '%expected%' => $value])
                );
                return false;
            }
        }

        return true;
    }

    /**
     * 
     * @param string $path
     * @param array  $params
     *
     * @return bool
     */
    public function validateZip($path, $params)
    {
        $zip = new ZipArchive();
        $zipStatus = $zip->open($path, ZipArchive::CHECKCONS);
        if ($zipStatus !== true) {
            $this->minilog->alert('ZIP error: ' . $zipStatus);
            return false;
        }

        /// extract facturascripts.ini
        $found = false;
        $result = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (basename($filename) != 'facturascripts.ini') {
                continue;
            }

            $found = true;
            $newPath = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . mt_rand(1, 99999999) . '.ini';
            if (copy("zip://" . $path . "#" . $filename, $newPath)) {
                $result = $this->validateIni($newPath, $params);
                unlink($newPath);
            }
        }

        if (!$found) {
            $this->minilog->alert($this->i18n->trans('facturascripts-ini-not-found'));
        }

        $zip->close();
        return $result;
    }
}
