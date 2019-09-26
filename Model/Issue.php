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
namespace FacturaScripts\Plugins\Community\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Lib\IssueNotification;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\webportal\Model\Base\WebPageClass;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of Issue model.
 *
 * @author Carlos García Gómez
 */
class Issue extends WebPageClass
{

    use Base\ModelTrait;
    use Common\ContactTrait;

    /**
     * Page text.
     *
     * @var string
     */
    public $body;

    /**
     *
     * @var bool
     */
    public $closed;

    /**
     *
     * @var string
     */
    public $creationroute;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idissue;

    /**
     * Related project key.
     *
     * @var int
     */
    public $idproject;

    /**
     * Identifier of the team assigned to solve this issue.
     *
     * @var int
     */
    public $idteam;

    /**
     * Related contact form tree key.
     *
     * @var int
     */
    public $idtree;

    /**
     *
     * @var int
     */
    protected $lastcommid;

    /**
     *
     * @var int
     */
    public $lastcommidcontacto;

    /**
     *
     * @var int
     */
    public $priority;

    /**
     *
     * @var array
     */
    private static $urls = [];

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->closed = false;
        $this->priority = 0;
    }

    /**
     * Returns a maximun legth of $legth form the body property of this block.
     *
     * @param int $length
     *
     * @return string
     */
    public function description(int $length = 300): string
    {
        return $this->toolBox()->utils()->trueTextBreak($this->body, $length);
    }

    /**
     * 
     * @return IssueComment[]
     */
    public function getComments()
    {
        $issueComment = new IssueComment();
        $where = [new DataBaseWhere('idissue', $this->idissue)];
        return $issueComment->all($where, ['creationdate' => 'ASC'], 0, 0);
    }

    /**
     * 
     * @return IssueComment
     */
    public function getLastComment()
    {
        $comment = new IssueComment();
        if ($comment->loadFromCode($this->lastcommid)) {
            return $comment;
        }

        foreach (array_reverse($this->getComments()) as $comm) {
            return $comm;
        }

        return $comment;
    }

    /**
     * Returns contact model from last comment.
     *
     * @return Contacto
     */
    public function getLastCommentContact()
    {
        return $this->getCustomContact($this->lastcommidcontacto);
    }

    /**
     * 
     * @return WebProject
     */
    public function getProject()
    {
        $project = new WebProject();
        $project->loadFromCode($this->idproject);
        return $project;
    }

    /**
     * 
     * @return WebTeam
     */
    public function getTeam()
    {
        $team = new WebTeam();
        $team->loadFromCode($this->idteam);
        return $team;
    }

    /**
     * 
     * @return string
     */
    public function html()
    {
        $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
        $html = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $this->body);
        return nl2br($html);
    }

    /**
     * 
     * @param int    $idcontacto
     * @param string $text
     * @param bool   $close
     *
     * @return bool
     */
    public function newComment($idcontacto, $text, $close = false)
    {
        $comment = new IssueComment();
        $comment->body = $text;
        $comment->idcontacto = $idcontacto;
        $comment->idissue = $this->idissue;
        if ($comment->save()) {
            $this->lastcommid = $comment->primaryColumnValue();

            /// update issue
            $this->lastcommidcontacto = $idcontacto;
            $this->closed = $close;
            $this->save();

            $this->evaluateSolution();
            return true;
        }

        return false;
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idissue';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'issues';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->body = $this->toolBox()->utils()->noHtml($this->body);
        $this->priority = $this->closed ? 0 : $this->priority;
        return parent::test();
    }

    /**
     * Returns title of the issue.
     *
     * @return string
     */
    public function title(): string
    {
        return $this->toolBox()->i18n()->trans('issue') . ' #' . $this->idissue;
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
        if ($type === 'public') {
            return $this->getCustomUrl($type) . $this->idissue;
        }

        return parent::url($type, $list);
    }

    /**
     * 
     * @return bool
     */
    protected function evaluateSolution()
    {
        /// issue must be closed and last comment from author to continue
        if (!$this->closed || $this->lastcommidcontacto != $this->idcontacto) {
            return false;
        }

        $idcontacts = [];
        foreach ($this->getComments() as $comm) {
            if (empty($comm->idcontacto) || $comm->idcontacto == $this->idcontacto) {
                continue;
            }

            $idcontacts[] = $comm->idcontacto;
        }

        if (empty($idcontacts)) {
            return false;
        }

        shuffle($idcontacts);

        /// add log message
        $teamLog = new WebTeamLog();
        $where = [new DataBaseWhere('link', $this->url())];
        if ($teamLog->loadFromCode('', $where)) {
            return;
        }

        $teamLog->description = $this->toolBox()->i18n()->trans('issue-solved', ['%title%' => $this->title()]);
        $teamLog->idcontacto = $idcontacts[0];
        $teamLog->idteam = $this->toolBox()->appSettings()->get('community', 'idteamsup');
        $teamLog->link = $this->url();
        return $teamLog->save();
    }

    /**
     * Return the public url from custom controller.
     *
     * @param string $type
     *
     * @return string
     */
    protected function getCustomUrl(string $type): string
    {
        if (isset(self::$urls[$type])) {
            return self::$urls[$type];
        }

        $controller = 'EditIssue';
        $webPage = new WebPage();
        foreach ($webPage->all([new DataBaseWhere('customcontroller', $controller)]) as $wpage) {
            self::$urls[$type] = $wpage->url('public');
            return self::$urls[$type];
        }

        return $controller . '?code=';
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (parent::saveInsert($values)) {
            IssueNotification::notify($this);
            return true;
        }

        return false;
    }
}
