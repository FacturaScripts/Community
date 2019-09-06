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

use FacturaScripts\Core\Base\ToolBox;
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
     * @param string $path
     * @param array  $params
     *
     * @return bool
     */
    public function validateZip($path, $params)
    {
        $zipFile = new ZipArchive();

        /// can we open the file?
        $result = $zipFile->open($path, ZipArchive::CHECKCONS);
        if (true !== $result) {
            $this->toolBox()->log()->error('ZIP error: ' . $result);
            return false;
        }

        if ($this->validatePluginStructure($zipFile, $params) && $this->validateComposer($zipFile) && $this->validateNPM($zipFile)) {
            $zipFile->close();
            return true;
        }

        $zipFile->close();
        return false;
    }

    /**
     * 
     * @param ZipArchive $zipFile
     * @param int        $level
     *
     * @return array
     */
    protected function getZipFolders(&$zipFile, $level = 0)
    {
        /// get folders inside the zip file
        $folders = [];
        for ($index = 0; $index < $zipFile->numFiles; $index++) {
            $data = $zipFile->statIndex($index);
            $path = explode('/', $data['name']);
            if (count($path) > 1) {
                $folders[$path[$level]] = $path[$level];
            }
        }

        return $folders;
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }

    /**
     * 
     * @param ZipArchive $zipFile
     *
     * @return bool
     */
    protected function validateComposer(&$zipFile)
    {
        /// Is there a composer.json file?
        $zipIndex = $zipFile->locateName('composer.json', ZipArchive::FL_NODIR);
        if (false === $zipIndex) {
            return true;
        }

        /// Is the file at the root of the plugin?
        $pathComposer = $zipFile->getNameIndex($zipIndex);
        if (count(explode('/', $pathComposer)) !== 2) {
            return true;
        }

        /// Is there a vendor folder?
        foreach ($this->getZipFolders($zipFile, 1) as $folder) {
            if ($folder === 'vendor') {
                return true;
            }
        }

        $this->toolBox()->i18nLog()->error('composer-vendor-not-found');
        return false;
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
                $this->toolBox()->i18nLog()->error('facturascripts-ini-key-not-found', ['%key%' => $key]);
                return false;
            }

            switch ($key) {
                case 'max_version':
                    $minVersion = isset($ini['min_version']) ? (float) $ini['min_version'] : 0.0;
                    if ($minVersion > (float) $value) {
                        $this->toolBox()->i18nLog()->error(
                            'facturascripts-ini-wrong-value', [
                            '%key%' => 'min_version',
                            '%value%' => $ini['min_version'],
                            '%expected%' => $params['min_version']
                            ]
                        );
                        return false;
                    }
                    break;

                case 'min_version':
                    if ((float) $ini[$key] < (float) $value) {
                        $this->toolBox()->i18nLog()->error(
                            'facturascripts-ini-wrong-value',
                            ['%key%' => $key, '%value%' => $ini[$key], '%expected%' => $value]
                        );
                        return false;
                    }
                    break;

                default:
                    if ($ini[$key] != $value) {
                        $this->toolBox()->i18nLog()->error(
                            'facturascripts-ini-wrong-value',
                            ['%key%' => $key, '%value%' => $ini[$key], '%expected%' => $value]
                        );
                        return false;
                    }
            }
        }

        return true;
    }

    /**
     * 
     * @param ZipArchive $zipFile
     *
     * @return bool
     */
    protected function validateNPM(&$zipFile)
    {
        /// Is there a package.json file?
        $zipIndex = $zipFile->locateName('package.json', ZipArchive::FL_NODIR);
        if (false === $zipIndex) {
            return true;
        }

        /// Is the file at the root of the plugin?
        $pathPackage = $zipFile->getNameIndex($zipIndex);
        if (count(explode('/', $pathPackage)) !== 2) {
            return true;
        }

        /// Is there a node_modules folder?
        foreach ($this->getZipFolders($zipFile, 1) as $folder) {
            if ($folder === 'node_modules') {
                return true;
            }
        }

        $this->toolBox()->i18nLog()->error('node-modules-not-found');
        return false;
    }

    /**
     * 
     * @param ZipArchive $zipFile
     * @param array      $params
     *
     * @return bool
     */
    protected function validatePluginStructure(&$zipFile, $params)
    {
        /// get the facturascripts.ini file inside the zip
        $zipIndex = $zipFile->locateName('facturascripts.ini', ZipArchive::FL_NODIR);
        if (false === $zipIndex) {
            $this->toolBox()->i18nLog()->error('facturascripts-ini-not-found');
            return false;
        } else if (!$this->validateIni($zipFile->getFromIndex($zipIndex), $params)) {
            return false;
        }

        /// the zip must contain the plugin folder
        $pathINI = $zipFile->getNameIndex($zipIndex);
        if (count(explode('/', $pathINI)) !== 2) {
            $this->toolBox()->i18nLog()->error('zip-error-wrong-structure');
            return false;
        }

        /// get folders inside the zip file
        $folders = $this->getZipFolders($zipFile);

        //// the zip must contain a single plugin
        if (count($folders) != 1) {
            $this->toolBox()->i18nLog()->error('zip-error-wrong-structure');
            return false;
        }

        return true;
    }
}
