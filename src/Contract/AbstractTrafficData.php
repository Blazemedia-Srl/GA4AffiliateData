<?php

namespace Blazemedia\Ga4AffiliateData\Contract;

use Blazemedia\Ga4AffiliateData\GA4Client;
use Blazemedia\Ga4AffiliateData\Utils\DataJoin;

abstract class AbstractTrafficData extends GA4Client {

    protected array $defaultFields = ['programs' => '', 'subjects' => '', 'type' => '', 'revenuestreams' => '', 'alias' => '', 'author' => '', 'custom' => ''];

    use DataJoin;

    protected function getDimensionsMap($dimensionHeaders) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[str_replace('customEvent:bmaff_page_', '', $dimensionHeader->getName())] = $idx;
        }

        return $dimensions;
    }


    abstract public function getPageViews($propertyId = '295858603', $date = 'yesterday');
}
