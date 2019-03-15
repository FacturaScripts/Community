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
    }

    public function update()
    {
        $defaultPages = [
            'ContactForm' => '/contact',
            'WebDocumentation' => '/doc*',
            'PluginList' => '/plugins',
            'TeamList' => '/teams',
            'EditWebTeam' => '/teams/*',
            'EditPublication' => '/publications/*',
            'TranslationList' => '/translations',
            'ViewProfile' => '/profiles/*',
        ];

        $webPage = new WebPage();
        foreach ($defaultPages as $controller => $permalink) {
            $where = [new DataBaseWhere('customcontroller', $controller)];
            if ($webPage->loadFromCode('', $where)) {
                continue;
            }

            $webPage->customcontroller = $controller;
            $webPage->description = $controller;
            $webPage->ordernum = 101;
            $webPage->permalink = $permalink;
            $webPage->shorttitle = $controller;
            $webPage->title = $controller;
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
