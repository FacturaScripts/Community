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
namespace FacturaScripts\Plugins\Community;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\EventManager;
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Plugins\Community\Lib\IssueNotification;
use FacturaScripts\Plugins\Community\Lib\WebTeamNotifications;
use FacturaScripts\Plugins\Community\Model\Language;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Init extends InitClass
{

    public function init()
    {
        EventManager::attach('Model:Issue:saveInsert', function($model) {
            IssueNotification::notify($model);
        });

        EventManager::attach('Model:IssueComment:saveInsert', function($model) {
            IssueNotification::notifyComment($model);
        });

        EventManager::attach('Model:WebTeamMember:acceptedBy', function($model) {
            WebTeamNotifications::notifyAccept($model);
        });

        EventManager::attach('Model:WebTeamMember:expel', function($model) {
            WebTeamNotifications::notifyExpel($model);
        });
    }

    public function update()
    {
        $defaultPages = [
            ['controller' => 'ContactForm', 'permalink' => '/contact', 'index' => true],
            ['controller' => 'WebDocumentation', 'permalink' => '/doc*', 'index' => true],
            ['controller' => 'PluginList', 'permalink' => '/plugins', 'index' => true],
            ['controller' => 'TeamList', 'permalink' => '/teams', 'index' => true],
            ['controller' => 'EditWebTeam', 'permalink' => '/teams/*', 'index' => false],
            ['controller' => 'EditPublication', 'permalink' => '/publications/*', 'index' => false],
            ['controller' => 'TranslationList', 'permalink' => '/translations', 'index' => true],
            ['controller' => 'ViewProfile', 'permalink' => '/profiles/*', 'index' => false],
        ];

        $webPage = new WebPage();
        foreach ($defaultPages as $data) {
            $where = [new DataBaseWhere('customcontroller', $data['controller'])];
            if ($webPage->loadFromCode('', $where)) {
                continue;
            }

            $webPage->customcontroller = $data['controller'];
            $webPage->description = $data['controller'];
            $webPage->noindex = !$data['index'];
            $webPage->ordernum++;
            $webPage->permalink = $data['permalink'];
            $webPage->shorttitle = $data['controller'];
            $webPage->showonfooter = $data['index'];
            $webPage->showonmenu = $data['index'];
            $webPage->title = $data['controller'];
            $webPage->save();
        }

        $this->initModels();
    }

    private function initModels()
    {
        new Language();
        new WebTeam();
        new WebProject();
    }
}
