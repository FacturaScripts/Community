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
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\WebBuild;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of DownloadBuild
 *
 * @author Carlos García Gómez
 */
class DownloadBuild extends PortalController
{

    /**
     *
     * @var WebProject
     */
    public $currentProject;

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'web';
        $pagedata['icon'] = 'fa-file-archive-o';
        $pagedata['showonmenu'] = false;
        
        return $pagedata;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadProject();
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->loadProject();
    }

    protected function downloadBuild(WebBuild $build)
    {
        $attachedFile = $build->getAttachedFile();
        if (is_null($attachedFile)) {
            $this->response->setContent('FILE-NOT-FOUND');
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            return;
        }

        $build->increaseDownloads();

        $this->response->headers->set('Content-type', $attachedFile->mimetype);
        $this->response->headers->set('Content-disposition', 'attachment; filename="' . $build->idproject . '.zip"');
        $this->response->headers->set('Content-length', $attachedFile->size);
        $this->response->setContent(file_get_contents(FS_FOLDER . DIRECTORY_SEPARATOR . $attachedFile->path));
    }

    protected function findBuild($idproject, $buildVersion)
    {
        $where = [new DataBaseWhere('idproject', $idproject)];
        if (is_numeric($buildVersion)) {
            $where[] = new DataBaseWhere('version', $buildVersion);
        }

        $buildModel = new WebBuild();
        foreach ($buildModel->all($where, ['version' => 'DESC']) as $build) {
            if ($buildVersion == $build->version) {
                return $this->downloadBuild($build);
            }

            if ('stable' === $buildVersion && $build->stable) {
                return $this->downloadBuild($build);
            }

            if ('beta' === $buildVersion && ($build->beta || $build->stable)) {
                return $this->downloadBuild($build);
            }
        }

        $this->response->setContent('BUILD-NOT-FOUND');
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
    }

    protected function getBuilds()
    {
        $data = [];
        $buildModel = new WebBuild();
        foreach ($this->currentProject->all([], [], 0, 0) as $project) {
            $projectData = ['project' => $project->idproject, 'builds' => []];
            foreach ($buildModel->all([new DataBaseWhere('idproject', $project->idproject)], ['version' => 'DESC'], 0, 5) as $build) {
                $projectData['builds'][] = [
                    'version' => $build->version,
                    'stable' => $build->stable,
                    'beta' => $build->beta
                ];
            }

            $data[] = $projectData;
        }

        return $data;
    }

    protected function getUrlExtraParams()
    {
        $params = [];
        foreach (explode('/', substr($this->uri, 1)) as $num => $param) {
            if ($num > 0) {
                $params[] = $param;
            }
        }
        return $params;
    }

    protected function loadProject()
    {
        $this->setTemplate(false);

        $urlParams = $this->getUrlExtraParams();
        $idproject = isset($urlParams[0]) ? $urlParams[0] : '';
        $buildVersion = isset($urlParams[1]) ? $urlParams[1] : 'stable';

        /// current project
        $this->currentProject = new WebProject();
        if ('' === $idproject) {
            $this->response->setContent(json_encode($this->getBuilds()));
        } elseif ($this->currentProject->loadFromCode($idproject)) {
            $this->findBuild($idproject, $buildVersion);
        } else {
            $this->response->setContent('PROJECT-NOT-FOUND');
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
    }
}
