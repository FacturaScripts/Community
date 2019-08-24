<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Plugins\webportal\Model\WebPage;

/**
 * Description of PointsMethodsTrait
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait PointsMethodsTrait
{

    abstract public function toolBox();

    /**
     * 
     * @return int
     */
    public function pointCost()
    {
        return 0;
    }

    /**
     * 
     * @param int $needed
     *
     * @return bool
     */
    protected function contactHasPoints($needed)
    {
        if ($needed === 0) {
            return true;
        }

        $minPoints = (int) $this->toolBox()->appSettings()->get('community', 'minpoints');
        return $this->contact->puntos - $needed >= $minPoints;
    }

    protected function redirToYouNeedMorePointsPage()
    {
        $webPage = new WebPage();
        $code = $this->toolBox()->appSettings()->get('community', 'morepointspage');
        if ($webPage->loadFromCode($code)) {
            $this->redirect($webPage->url('public'));
            return;
        }

        $this->toolBox()->i18nLog()->warning('you-need-more-points');
    }

    protected function subtractPoints()
    {
        $this->contact->puntos -= $this->pointCost();
        $this->contact->save();
    }
}
