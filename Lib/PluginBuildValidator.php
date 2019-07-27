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
    public function validateZip($path, $params)
    {
        $zipFile = new ZipArchive();
        $result = $zipFile->open($path, ZipArchive::CHECKCONS);
        if (true !== $result) {
            $this->minilog->error('ZIP error: ' . $result);
            return false;
        }

        /// get the facturascripts.ini file inside the zip
        $zipIndex = $zipFile->locateName('facturascripts.ini', ZipArchive::FL_NODIR);
        if (false === $zipIndex) {
            $this->minilog->alert($this->i18n->trans('facturascripts-ini-not-found'));
            return false;
        } else if (!$this->validateIni($zipFile->getFromIndex($zipIndex), $params)) {
            return false;
        }

        /// the zip must contain the plugin folder
        $pathINI = $zipFile->getNameIndex($zipIndex);
        if (count(explode('/', $pathINI)) !== 2) {
            $this->minilog->error($this->i18n->trans('zip-error-wrong-structure'));
            return false;
        }

        /// get folders inside the zip file
        $folders = [];
        for ($index = 0; $index < $zipFile->numFiles; $index++) {
            $data = $zipFile->statIndex($index);
            $path = explode('/', $data['name']);
            if (count($path) > 1) {
                $folders[$path[0]] = $path[0];
            }
        }

        //// the zip must contain a single plugin
        if (count($folders) != 1) {
            $this->minilog->error($this->i18n->trans('zip-error-wrong-structure'));
            return false;
        }

        return true;
    }

    /**
     * 
     * @param string $iniContent
     * @param array  $params
     *
     * @return bool
     */
    protected function validateIni($iniContent, $params)
    {
        $ini = parse_ini_string($iniContent);
        foreach ($params as $key => $value) {
            if (!isset($ini[$key]) && $key !== 'max_version') {
                $this->minilog->alert($this->i18n->trans('facturascripts-ini-key-not-found', ['%key%' => $key]));
                return false;
            }

            switch ($key) {
                case 'max_version':
                    if ((float) $ini['min_version'] > (float) $value) {
                        $this->minilog->alert(
                            $this->i18n->trans(
                                'facturascripts-ini-wrong-value', [
                                '%key%' => 'min_version',
                                '%value%' => $ini['min_version'],
                                '%expected%' => $params['min_version']
                                ]
                            )
                        );
                        return false;
                    }
                    break;

                case 'min_version':
                    if ((float) $ini[$key] < (float) $value) {
                        $this->minilog->alert(
                            $this->i18n->trans('facturascripts-ini-wrong-value', ['%key%' => $key, '%value%' => $ini[$key], '%expected%' => $value])
                        );
                        return false;
                    }
                    break;

                default:
                    if ($ini[$key] != $value) {
                        $this->minilog->alert(
                            $this->i18n->trans('facturascripts-ini-wrong-value', ['%key%' => $key, '%value%' => $ini[$key], '%expected%' => $value])
                        );
                        return false;
                    }
            }
        }

        return true;
    }
}
