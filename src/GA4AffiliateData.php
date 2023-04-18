<?php

namespace Blazemedia\Ga4AffiliateData;

use Google\Analytics\Data\V1beta\Dimension;

class GA4AffiliateData extends GA4Client {

    use DataJoin;
    
    protected function getDimensionsMap( $dimensionHeaders ) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[ str_replace(
                [
                    'customEvent:data_bmaff_trackingid',
                    'customEvent:data_bmaff_'
                ],[ 
                    'tracking_id',
                    ''
                ], $dimensionHeader->getName() ) ] = $idx;    
        }

        return $dimensions;
    }

    protected function getEventMap( $event ) {

        return str_replace(
                [
                    'BM View',
                    'BM Click'
                ],[ 
                    'bm_views',
                    'bm_clicks'
                ], 
                $event);
    }


    /**
     * Prende i dati di view e click di una property 
     * per un giorno specifico
     *
     * @param string $date
     * @param string $propertyId
     * @return array
     */
    public function getViewClickData(  $date = 'yesterday' , $propertyId = '295858603' ) : array {

        $viewPrimaryDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_domain' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_author' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_subject' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_program' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_platform' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_filone' ]),
        ];

        $viewSecondaryDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'pagePath' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_format' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_tipologia' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_custom' ]),
        ];

        $clickDimensions =  [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),            
        ];

        $viewPrimaryRows   = $this->getData( $propertyId, 'BM View',  $viewPrimaryDimensions,   $date ) ;
        $viewSecondaryRows = $this->getData( $propertyId, 'BM View',  $viewSecondaryDimensions, $date ) ;

        $viewRows = $this->leftJoin( $viewPrimaryRows, $viewSecondaryRows, [ 'Date', 'tracking_id' ], [ 'format' => '', 'custom' => '' ] );
        
        $clickRows = $this->getData( $propertyId, 'BM Click', $clickDimensions, $date, $date );

        return $this->leftJoin( $viewRows, $clickRows, [ 'Date', 'tracking_id' ], ['bm_clicks' => 0] );        
    }

}