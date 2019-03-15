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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Lib;
use FacturaScripts\Plugins\Community\Model\Issue;
use FacturaScripts\Plugins\Community\Model\IssueComment;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
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

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $this->getMainModel()->idteam),
            new DataBaseWhere('accepted', true)
        ];

        return $member->loadFromCode('', $where);
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

        return $this->getMainModel()->idcontacto === $this->contact->idcontacto;
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

        $close = $this->request->request->get('close', '');
        $text = $this->request->get('newComment', '');
        if (empty($text) && $close === 'TRUE') {
            $text = $this->i18n->trans('close');
        }

        if (empty($text)) {
            return false;
        }

        $issue = $this->getMainModel();
        $comment = new IssueComment();
        $comment->body = $text;
        $comment->idcontacto = $this->contact->idcontacto;
        $comment->idissue = $issue->idissue;
        if (!$comment->save()) {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
            return false;
        }

        /// update issue
        $issue->lastcommidcontacto = $this->contact->idcontacto;
        $issue->closed = ($close === 'TRUE') ? true : $issue->closed;
        if ($issue->save()) {
            $this->evaluateSolution($issue);
        }

        $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
        $this->response->headers->set('Refresh', '0; ' . $issue->url('public') . '#comm' . $comment->primaryColumnValue());
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
        }

        if ($this->user) {
            $this->createSectionEditComments();
        }
    }

    /**
     * Delete the comment specify by the user.
     *
     * @return bool
     */
    protected function deleteComment()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-delete'));
            $this->response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            return false;
        }

        $idComment = $this->request->request->get('idcomment', '');
        $issueComment = new IssueComment();
        if ($issueComment->loadFromCode($idComment) && $issueComment->delete()) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));

            /// update issue
            $this->getMainModel()->lastcommidcontacto = null;
            foreach ($this->getMainModel()->getComments() as $comment) {
                $this->getMainModel()->lastcommidcontacto = $comment->idcontacto;
            }
            $this->getMainModel()->save();
            return true;
        }

        $this->miniLog->alert($this->i18n->trans('record-deleted-error'));
        return false;
    }

    /**
     * 
     * @param Issue $issue
     *
     * @return bool
     */
    protected function evaluateSolution($issue)
    {
        /// issue must be closed and last comment from author to continue
        if (!$issue->closed || $issue->lastcommidcontacto != $issue->idcontacto) {
            return false;
        }

        $idcontacts = [];
        foreach ($issue->getComments() as $comm) {
            if (empty($comm->idcontacto) || $comm->idcontacto == $issue->idcontacto) {
                continue;
            }

            $idcontacts[] = $comm->idcontacto;
        }

        if (empty($idcontacts)) {
            return false;
        }

        shuffle($idcontacts);
        $teamLog = new WebTeamLog();
        $teamLog->description = $issue->title() . ' solved';
        $teamLog->idcontacto = $idcontacts[0];
        $teamLog->idteam = AppSettings::get('community', 'idteamsup');
        $teamLog->link = $issue->url();
        return $teamLog->save();
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
            case 'delete-comment':
                $this->deleteComment();
                return true;

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
            $this->miniLog->warning($this->i18n->trans('no-data'));
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        if (!$this->contactCanSee()) {
            $this->miniLog->warning($this->i18n->trans('access-denied'));
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->webPage->noindex = true;

            $template = empty($this->contact) ? 'Master/LoginToContinue' : 'Master/AccessDenied';
            $this->setTemplate($template);
            return;
        }

        $this->title = $this->getMainModel()->title();
        $this->description = $this->getMainModel()->description();
        $this->canonicalUrl = $this->getMainModel()->url('public');

        $ipAddress = is_null($this->request->getClientIp()) ? '::1' : $this->request->getClientIp();
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
