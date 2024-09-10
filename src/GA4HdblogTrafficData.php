<?php

namespace Blazemedia\Ga4AffiliateData;

use Blazemedia\Ga4AffiliateData\Contract\AbstractTrafficData;
use Google\Analytics\Data\V1beta\Dimension;

class GA4HdblogTrafficData extends AbstractTrafficData
{
    /**
     * Prende i dati di view e click di una property 
     * per un intervallo di tempo specifico
     *
     * @param string $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getPageViewsinInterval($propertyId = '295858603', $startDate = 'yesterday', $endDate = 'today')
    {
        $pageViewDimensions = [
            new Dimension(['name' => 'Date']),
            new Dimension(['name' => 'pagepath']),
        ];

        $viewChunkedDimensions = [

            array_merge($pageViewDimensions, [
                new Dimension(['name' => 'customEvent:bmaff_page_alias']),
                new Dimension(['name' => 'customEvent:bmaff_page_author']),
                new Dimension(['name' => 'customEvent:bmaff_page_custom']),
            ]),

            array_merge($pageViewDimensions, [
                new Dimension(['name' => 'customEvent:bmaff_page_programs']),
                new Dimension(['name' => 'customEvent:bmaff_page_subjects']),
                new Dimension(['name' => 'customEvent:bmaff_page_type']),
                new Dimension(['name' => 'customEvent:bmaff_page_revenuestreams']),
            ]),

        ];

        $viewRowsPartials = array_map(fn($dimensions) => $this->getIntervalData($propertyId, $startDate, $endDate, $dimensions, 'page_view'), $viewChunkedDimensions);

        $viewRows = $this->leftJoin($viewRowsPartials[0], $viewRowsPartials[1], ['Date', 'pagepath', 'page_view'], $this->defaultFields);

        return $viewRows;
    }

    /**
     * Prende i dati di view e click di una property 
     * per un giorno specifico
     *
     * @param string $date
     * @param string $propertyId
     * @return array
     */
    public function getPageViews($propertyId = '295858603', $date = 'yesterday')
    {
        return $this->getPageViewsinInterval($propertyId, $date, $date);
    }
}
