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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditWebTeam
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditWebTeam extends EditSectionController
{

    /**
     * This team.
     *
     * @var WebTeam
     */
    protected $team;

    /**
     * Returns true if contact can edit this webteam.
     *
     * @return bool
     */
    public function contactCanEdit()
    {
        if ($this->user) {
            return true;
        }

        if (empty($this->contact)) {
            return false;
        }

        $team = $this->getMainModel();
        return ($team->idcontacto === $this->contact->idcontacto);
    }

    /**
     * 
     * @return bool
     */
    public function contactCanSee()
    {
        return $this->contactCanEdit() ? true : !$this->getMainModel()->private;
    }

    /**
     * Returns the team details.
     * 
     * @param bool $reload
     *
     * @return WebTeam
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->team) && !$reload) {
            return $this->team;
        }

        $this->team = new WebTeam();
        $uri = explode('/', $this->uri);
        $name = rawurldecode(end($uri));
        if ($this->team->loadFromCode('', [new DataBaseWhere('name', $name)])) {
            return $this->team;
        }

        $code = $this->request->query->get('code', '');
        $this->team->loadFromCode($code);
        return $this->team;
    }

    /**
     * Returns the status of this contact in this team: in|pending|out.
     *
     * @return string
     */
    public function getMemberStatus()
    {
        if (empty($this->contact)) {
            return 'out';
        }

        $member = new WebTeamMember();
        $team = $this->getMainModel();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $team->idteam),
        ];

        if ($member->loadFromCode('', $where)) {
            return $member->accepted ? 'in' : 'pending';
        }

        return 'out';
    }

    /**
     * Code for accept action.
     * 
     * @return bool
     */
    protected function acceptAction()
    {
        if (!$this->contactCanEdit()) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

        $idRequest = $this->request->get('idrequest', '');
        $member = new WebTeamMember();
        if ('' === $idRequest || !$member->loadFromCode($idRequest)) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return false;
        }

        if ($member->acceptedBy($this->contact->idcontacto)) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * 
     * @param string $name
     */
    protected function createPluginSection($name = 'ListWebProject')
    {
        $this->addListSection($name, 'WebProject', 'plugins', 'fas fa-plug');
        $this->sections[$name]->template = 'Section/Plugins.html.twig';
        $this->addOrderOption($name, ['LOWER(name)'], 'name');
        $this->addOrderOption($name, ['lastmod'], 'last-update', 2);
        $this->addOrderOption($name, ['version'], 'version');
        $this->addOrderOption($name, ['downloads'], 'downloads');
        $this->addOrderOption($name, ['visitcount'], 'visit-counter');
        $this->addSearchOptions($name, ['name', 'description']);

        /// filters
        $types = $this->codeModel->all('webprojects', 'type', 'type');
        $this->addFilterSelect($name, 'type', 'type', 'type', $types);

        $licenses = $this->codeModel->all('licenses', 'name', 'title');
        $this->addFilterSelect($name, 'license', 'license', 'license', $licenses);

        /// buttons
        $button = [
            'action' => 'AddPlugin',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link'
        ];
        $this->addButton($name, $button);
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionLogs($name = 'ListWebTeamLog')
    {
        $this->addListSection($name, 'WebTeamLog', 'logs', 'fas fa-file-medical-alt');
        $this->sections[$name]->template = 'Section/TeamLogs.html.twig';
        $this->addSearchOptions($name, ['description']);
        $this->addOrderOption($name, ['time'], 'date', 2);
    }

    /**
     * 
     * @param string $name
     * @param string $label
     * @param string $icon
     * @param string $group
     */
    protected function createSectionMembers($name, $label, $icon, $group = '')
    {
        $this->addListSection($name, 'WebTeamMember', $label, $icon, $group);
        $this->sections[$name]->template = 'Section/TeamMembers.html.twig';
        $this->addOrderOption($name, ['creationdate'], 'date', 2);
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionPublications($name = 'ListPublication')
    {
        $this->addListSection($name, 'Publication', 'publications', 'fas fa-newspaper');
        $this->addOrderOption($name, ['creationdate'], 'date', 2);
        $this->addSearchOptions($name, ['title', 'body']);

        /// buttons
        if ($this->contactCanEdit()) {
            $team = $this->getMainModel();
            $button = [
                'action' => 'AddPublication?idteam=' . $team->idteam,
                'color' => 'success',
                'icon' => 'fas fa-plus',
                'label' => 'new',
                'type' => 'link'
            ];
            $this->addButton($name, $button);
        }
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('team', 'team', 'Section/Team');

        /// navigation link
        $team = $this->getMainModel();
        $this->addNavigationLink($team->url('public-list'), $this->toolBox()->i18n()->trans('teams'));

        $this->createSectionPublications();

        if ($this->getMemberStatus() === 'in' || $this->user) {
            $this->createTeamIssuesSection();
            $this->createPluginSection();
        }

        $this->createSectionLogs();
        $this->createSectionMembers('ListWebTeamMember', 'members', 'fas fa-users');

        /// admin
        if ($this->contactCanEdit()) {
            $this->addEditSection('EditWebTeam', 'WebTeam', 'edit', 'fas fa-edit', 'admin');
            $this->addEditListSection('EditWebTeamMember', 'WebTeamMember', 'members', 'fas fa-users', 'admin');
            $this->createSectionMembers('ListWebTeamMember-req', 'requests', 'fas fa-address-card', 'admin');
        }
    }

    /**
     * 
     * @param string $name
     */
    protected function createTeamIssuesSection($name = 'ListIssue')
    {
        $this->addListSection($name, 'Issue', 'issues', 'fas fa-question-circle');
        $this->sections[$name]->template = 'Section/Issues.html.twig';
        $this->addSearchOptions($name, ['body', 'creationroute', 'idissue']);
        $this->addOrderOption($name, ['lastmod'], 'last-update');
        $this->addOrderOption($name, ['creationdate'], 'date');
        $this->addOrderOption($name, ['priority', 'lastmod'], 'priority', 2);

        /// buttons
        $contactButton = [
            'action' => 'ContactForm',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link',
        ];
        $this->addButton($name, $contactButton);

        /// filters
        $this->addFilterDatePicker($name, 'fromdate', 'from-date', 'creationdate', '>=');
        $this->addFilterDatePicker($name, 'untildate', 'until-date', 'creationdate', '<=');

        $where = [new DataBaseWhere('closed', false)];
        $this->addFilterCheckbox($name, 'closed', 'closed', 'closed', '=', true, $where);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'accept-request':
                $this->acceptAction();
                break;

            case 'expel':
                $this->expelAction();
                break;

            case 'join':
                $this->joinAction();
                break;

            case 'leave':
                $this->leaveAction();
                break;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * 
     * @return bool
     */
    protected function expelAction()
    {
        if (!$this->contactCanEdit()) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

        $member = new WebTeamMember();
        $code = $this->request->get('idrequest');
        if ($member->loadFromCode($code) && $member->expel()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * Code for join action.
     * 
     * @return bool
     */
    protected function joinAction()
    {
        if (empty($this->contact)) {
            $this->toolBox()->i18nLog()->warning('login-to-continue');
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

        $member = new WebTeamMember();
        $member->idcontacto = $this->contact->idcontacto;
        $member->idteam = $this->getMainModel()->idteam;
        $member->observations = $this->request->request->get('observations', '');
        if ($member->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * Code for leave action.
     * 
     * @return bool
     */
    protected function leaveAction()
    {
        if (empty($this->contact)) {
            return false;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $this->getMainModel()->idteam),
        ];

        if ($member->loadFromCode('', $where) && $member->leave()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $team = $this->getMainModel();
        $where = [new DataBaseWhere('idteam', $team->idteam)];
        switch ($sectionName) {
            case 'ListIssue':
            case 'ListWebProject':
            case 'EditWebTeamMember':
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'EditWebTeam':
                $this->sections[$sectionName]->loadData($team->primaryColumnValue());
                break;

            case 'ListPublication':
                $this->sections[$sectionName]->loadData('', $where, ['ordernum' => 'ASC', 'creationdate' => 'DESC']);
                break;

            case 'ListWebTeamLog':
                $this->sections[$sectionName]->loadData('', $where, ['time' => 'DESC']);
                break;

            case 'ListWebTeamMember':
                $where[] = new DataBaseWhere('accepted', true);
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListWebTeamMember-req':
                $where[] = new DataBaseWhere('accepted', false);
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'team':
                $this->loadTeam();
                break;
        }
    }

    /**
     * Load team details.
     */
    protected function loadTeam()
    {
        if (!$this->getMainModel(true)->exists()) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        if (!$this->contactCanSee()) {
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/AccessDenied');
            return;
        }

        $this->title = $this->toolBox()->i18n()->trans('team-title', ['%teamName%' => $this->getMainModel()->name]);
        $this->description = $this->getMainModel()->description();
        $this->canonicalUrl = $this->getMainModel()->url('public');

        $ipAddress = $this->toolBox()->ipFilter()->getClientIp();
        $this->getMainModel()->increaseVisitCount($ipAddress);
    }
}
