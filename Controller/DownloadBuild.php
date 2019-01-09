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
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Plugins\Community\Model\WebBuild;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\PortalController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Description of DownloadBuild
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DownloadBuild extends PortalController
{

    /**
     * The current project details.
     *
     * @var WebProject
     */
    public $currentProject;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'web';
        $pageData['icon'] = 'fas fa-file-archive-o';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadProject();
    }

    /**
     * Execute the public part of the controller.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        parent::publicCore($response);
        $this->loadProject();
    }

    /**
     * 
     * @return bool
     */
    protected function contactCanDownload()
    {
        if (!$this->currentProject->private) {
            return true;
        }

        if (empty($this->contact)) {
            return false;
        }

        return $this->currentProject->idcontacto == $this->contact->idcontacto;
    }

    /**
     * Returns the download link from the build.
     *
     * @param WebBuild $build
     */
    protected function downloadBuild(WebBuild $build)
    {
        $attachedFile = $build->getAttachedFile();
        if (is_null($attachedFile)) {
            $this->response->setContent('FILE-NOT-FOUND');
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            return;
        }

        $build->increaseDownloads();

        $filePath = FS_FOLDER . DIRECTORY_SEPARATOR . $attachedFile->path;
        $this->response = new BinaryFileResponse($filePath);
        $this->response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $build->fileName());
    }

    /**
     * Returns the file for project/version if it's available.
     *
     * @param int   $idProject
     * @param float $buildVersion
     */
    protected function findBuild($idProject, $buildVersion)
    {
        if (!$this->contactCanDownload()) {
            $this->response->setContent('UNAUTHORIZED');
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return;
        }

        $where = [new DataBaseWhere('idproject', $idProject)];
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

        /// none selected? We chose the first one
        foreach ($buildModel->all($where, ['version' => 'DESC']) as $build) {
            return $this->downloadBuild($build);
        }

        $this->response->setContent('BUILD-NOT-FOUND');
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
    }

    /**
     * Return a list of available builds.
     *
     * @return array
     */
    protected function getBuilds(): array
    {
        $data = [];
        $buildModel = new WebBuild();
        foreach ($this->currentProject->all([], [], 0, 0) as $project) {
            $projectData = ['project' => $project->idproject, 'builds' => []];
            $where = [new DataBaseWhere('idproject', $project->idproject)];
            $order = ['version' => 'DESC'];
            foreach ($buildModel->all($where, $order, 0, 5) as $build) {
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

    /**
     * Returns extra params from URL.
     *
     * @return array
     */
    protected function getUrlExtraParams(): array
    {
        $params = [];
        foreach (explode('/', substr($this->uri, 1)) as $num => $param) {
            if ($num > 0) {
                $params[] = $param;
            }
        }
        return $params;
    }

    /**
     * Load project data.
     */
    protected function loadProject()
    {
        $this->setTemplate(false);

        $urlParams = $this->getUrlExtraParams();
        $idProject = $urlParams[0] ?? '';
        $buildVersion = $urlParams[1] ?? 'stable';

        /// current project
        $this->currentProject = new WebProject();
        if ('' === $idProject) {
            $this->response->setContent(json_encode($this->getBuilds()));
        } elseif ($this->currentProject->loadFromCode($idProject)) {
            $this->findBuild($idProject, $buildVersion);
        } else {
            $this->response->setContent('PROJECT-NOT-FOUND');
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
    }
}
