<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\EditSectionController;
use FacturaScripts\Plugins\Community\Model\Issue;
use FacturaScripts\Plugins\Community\Model\IssueComment;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of EditIssue
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditIssue extends EditSectionController
{

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
        if (empty($this->contact)) {
            return false;
        }

        $issue = $this->getMainModel();
        if ($issue->idcontacto === $this->contact->idcontacto) {
            return true;
        }

        $member = new WebTeamMember();
        $where = [
            new DataBaseWhere('idcontacto', $this->contact->idcontacto),
            new DataBaseWhere('idteam', $issue->idteam),
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
        return $this->contactCanEdit();
    }

    /**
     * Return the gravatar url to show email avatar.
     *
     * @param string $email
     * @param int    $size
     *
     * @return string
     */
    public function getGravatar(string $email, int $size = 80): string
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?s=' . $size;
    }

    /**
     * Return the related issue.
     *
     * @return Issue
     */
    public function getMainModel()
    {
        if (isset($this->issue)) {
            return $this->issue;
        }

        $issue = new Issue();
        $uri = explode('/', $this->uri);
        $issue->loadFromCode(end($uri));
        return $issue;
    }

    /**
     * Add a new comment to this issue.
     *
     * @return bool
     */
    protected function addNewComment(): bool
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->warning($this->i18n->trans('login-to-continue'));
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
        $issue->save();

        $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
        $this->notifyComment($issue, $comment);
        return true;
    }

    protected function createSectionComments($name = 'ListIssueComment')
    {
        $this->addListSection($name, 'IssueComment', 'comments', 'fas fa-comments');
        $this->sections[$name]->template = 'Section/IssueComments.html.twig';
        $this->addOrderOption($name, ['creationdate'], 'date');
        $this->addOrderOption($name, ['idcontacto'], 'user');
    }

    protected function createSectionEditComments($name = 'EditIssueComment')
    {
        $this->addEditListSection($name, 'IssueComment', 'comments', 'fas fa-edit', 'edit');
    }

    protected function createSectionEditIssue($name = 'EditIssue')
    {
        $this->addEditSection($name, 'Issue', 'issue', 'fas fa-edit', 'edit');
    }

    protected function createSectionRelatedIssues($name = 'ListIssue')
    {
        $this->addListSection($name, 'Issue', 'related', 'fas fa-question-circle');
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

        if ($this->user) {
            $this->createSectionEditIssue();
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
            return false;
        }

        $idComment = $this->request->request->get('idcomment', '');
        $issueComment = new IssueComment();
        if ($issueComment->loadFromCode($idComment) && $issueComment->delete()) {
            $this->miniLog->notice($this->i18n->trans('comment-deleted-correctly'));
            return true;
        }

        $this->miniLog->alert($this->i18n->trans('delete-comment-error'));
        return false;
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
        $this->issue = $this->getMainModel();
        if (!$this->issue->exists()) {
            $this->miniLog->alert($this->i18n->trans('no-data'));
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->webPage->noindex = true;
            $this->setTemplate('Master/Portal404');
            return;
        }

        if (!$this->contactCanSee()) {
            $this->miniLog->alert($this->i18n->trans('access-denied'));
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->webPage->noindex = true;

            $template = empty($this->contact) ? 'Master/LoginToContinue' : 'Master/AccessDenied';
            $this->setTemplate($template);
            return;
        }

        $this->title = $this->issue->title();
        $this->description = $this->issue->description();
        $this->issue->increaseVisitCount($this->request->getClientIp());
    }

    /**
     * Notify a new comment on an existing issue.
     *
     * @param Issue        $issue
     * @param IssueComment $comment
     */
    protected function notifyComment($issue, $comment)
    {
        if ($issue->idcontacto === $comment->idcontacto) {
            return;
        }

        $contact = $issue->getContact();
        $link = AppSettings::get('webportal', 'url', '') . $issue->url('public');
        $title = 'Issue #' . $issue->idissue . ': comentario de ' . $issue->getLastCommentContact()->fullName();
        $txt = '<a href="' . $link . '">Issue #' . $issue->idissue . '</a><br/>' . $comment->body;

        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        $mail->addAddress($contact->email, $contact->fullName());
        $mail->Subject = $title;

        $params = [
            'body' => $txt,
            'company' => $title,
            'footer' => $title,
            'title' => $title,
        ];
        $mail->msgHTML($emailTools->getTemplateHtml($params));

        if ($mail->send()) {
            $this->miniLog->notice($this->i18n->trans('email-sent'));
        }
    }

    /**
     * Re-open an existing issue.
     */
    protected function reopenAction()
    {
        if ($this->contactCanEdit()) {
            $issue = $this->getMainModel();
            $issue->closed = false;
            $issue->save();
        }
    }
}
