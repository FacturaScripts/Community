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
use FacturaScripts\Plugins\Community\Lib;
use FacturaScripts\Plugins\Community\Model\Issue;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditIssue
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditIssue extends EditSectionController
{

    use Lib\PointsMethodsTrait;

    /**
     * The selected issue.
     *
     * @var Issue
     */
    protected $issue;

    /**
     * Returns true if contact can edit this issue.
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

        $team = new WebTeam();
        $where = [new DataBaseWhere('idcontacto', $this->contact->idcontacto)];
        return $team->loadFromCode('', $where);
    }

    /**
     * Returns true if contact can see this issue.
     *
     * @return bool
     */
    public function contactCanSee()
    {
        if ($this->contactCanEdit()) {
            return true;
        }

        if (empty($this->contact)) {
            return false;
        }

        if ($this->getMainModel()->idcontacto === $this->contact->idcontacto) {
            return true;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $this->getMainModel()->idteam),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
    }

    /**
     * Return the related issue.
     * 
     * @param bool $reload
     *
     * @return Issue
     */
    public function getMainModel($reload = false)
    {
        if (isset($this->issue) && !$reload) {
            return $this->issue;
        }

        $this->issue = new Issue();
        $uri = explode('/', $this->uri);
        if ($this->issue->loadFromCode(end($uri))) {
            return $this->issue;
        }

        $code = $this->request->query->get('code', '');
        $this->issue->loadFromCode($code);
        return $this->issue;
    }

    /**
     * Add a new comment to this issue.
     *
     * @return bool
     */
    protected function addNewComment(): bool
    {
        if (!$this->contactCanSee()) {
            return false;
        }

        $close = ($this->request->request->get('close', '') === 'TRUE');
        $text = $this->request->get('newComment', '');
        if (empty($text) && $close) {
            $text = $this->toolBox()->i18n()->trans('close');
        }

        if (empty($text)) {
            return false;
        }

        $issue = $this->getMainModel();
        if (!$issue->newComment($this->contact->idcontacto, $text, $close)) {
            $this->toolBox()->i18nLog()->error('record-save-error');
            return false;
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        $this->redirect($issue->url('public') . '#comm' . $issue->getLastComment()->primaryColumnValue());
        return true;
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionComments($name = 'ListIssueComment')
    {
        $this->addListSection($name, 'IssueComment', 'comments', 'fas fa-comments');
        $this->sections[$name]->template = 'Section/IssueComments.html.twig';
        $this->addOrderOption($name, ['creationdate'], 'date');
        $this->addOrderOption($name, ['idcontacto'], 'user');
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionEditComments($name = 'EditIssueComment')
    {
        $this->addEditListSection($name, 'IssueComment', 'comments', 'fas fa-edit', 'edit');
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionEditIssue($name = 'EditIssue')
    {
        $this->addEditSection($name, 'Issue', 'issue', 'fas fa-edit', 'edit');
    }

    /**
     * 
     * @param string $name
     */
    protected function createSectionRelatedIssues($name = 'ListIssue', $title = 'related', $group = '')
    {
        $this->addListSection($name, 'Issue', $title, 'fas fa-question-circle', $group);
        $this->sections[$name]->template = 'Section/Issues.html.twig';
        $this->addSearchOptions($name, ['body', 'creationroute']);
        $this->addOrderOption($name, ['creationdate'], 'date', 2);
        $this->addOrderOption($name, ['lastmod'], 'last-update');
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->fixedSection();
        $this->addHtmlSection('issue', 'issue', 'Section/Issue');

        $this->createSectionComments();
        $this->createSectionRelatedIssues();
        $this->createSectionRelatedIssues('ListIssue-contact', 'issues', 'contact');

        if ($this->contactCanEdit()) {
            $this->createSectionEditIssue();
            $this->createSectionEditComments();
        }
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
            case 'new-comment':
                $this->addNewComment();
                return true;

            case 're-open':
                $this->reopenAction();
                return true;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $issue = $this->getMainModel();
        switch ($sectionName) {
            case 'EditIssue':
                $this->sections[$sectionName]->loadData($issue->primaryColumnValue());
                break;

            case 'issue':
                $this->loadIssue();
                break;

            case 'EditIssueComment':
            case 'ListIssueComment':
                $where = [new DataBaseWhere('idissue', $issue->idissue)];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListIssue':
                $where = [
                    new DataBaseWhere('creationroute', $issue->creationroute),
                    new DataBaseWhere('idissue', $issue->idissue, '!=')
                ];
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListIssue-contact':
                $where = [
                    new DataBaseWhere('idcontacto', $issue->idcontacto),
                    new DataBaseWhere('idissue', $issue->idissue, '!=')
                ];
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }

    /**
     * Loads an existing issue.
     */
    protected function loadIssue()
    {
        if (!$this->getMainModel(true)->exists()) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        if (!$this->contactCanSee()) {
            $this->toolBox()->i18nLog()->warning('access-denied');
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->webPage->noindex = true;

            $template = empty($this->contact) ? 'Master/LoginToContinue' : 'Master/AccessDenied';
            $this->setTemplate($template);
            return;
        }

        $this->title = $this->getMainModel()->title();
        $this->description = $this->getMainModel()->description();
        $this->canonicalUrl = $this->getMainModel()->url('public');

        $ipAddress = $this->toolBox()->ipFilter()->getClientIp();
        $this->getMainModel()->increaseVisitCount($ipAddress);
    }

    /**
     * Re-open an existing issue.
     * 
     * @return bool
     */
    protected function reopenAction()
    {
        if (!$this->contactCanSee()) {
            return false;
        }

        if (!$this->contactHasPoints($this->pointCost())) {
            return $this->redirToYouNeedMorePointsPage();
        }

        $issue = $this->getMainModel();
        $issue->closed = false;
        if ($issue->save()) {
            $this->subtractPoints();
            return true;
        }

        return false;
    }
}
