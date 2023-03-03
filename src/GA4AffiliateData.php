<?php

namespace Blazemedia\Ga4AffiliateData;

use Google\Analytics\Data\V1beta\Dimension;
       

class GA4AffiliateData extends GA4Client {

    use DataJoin;
    
    protected function getDimensionsMap( $dimensionHeaders ) {

        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[ str_replace('customEvent:data_bmaff_', '', $dimensionHeader->getName() ) ] = $idx;    
        }

        return $dimensions;
    }


    /**
     * Prende i dati di view e click di una property 
     * per un giorno specifico
     *
     * @param string $date
     * @param string $propertyId
     * @return array
     */
    public function getViewClickData(  $date = 'yesterday' , $propertyId = '295858603' ) {

        $viewPrimaryDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_domain' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_author' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_tipologia' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_subject' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_program' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_platform' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_filone' ]),
        ];

        $viewSecondaryDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_format' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_custom' ]),
        ];

        $clickDimensions =  [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),            
        ];

        $viewPrimaryRows   = $this->getData( $propertyId, 'bm_views',  $viewPrimaryDimensions,   $date ) ;
        $viewSecondaryRows = $this->getData( $propertyId, 'bm_views',  $viewSecondaryDimensions, $date ) ;

        // var_dump(count($viewPrimaryRows), count($viewSecondaryRows) ); die;

        $viewRows = $this->leftJoin( $viewPrimaryRows, $viewSecondaryRows, [ 'Date', 'trackingid' ], [ 'format' => '', 'custom' => '' ] );
        
        $clickRows = $this->getData( $propertyId, 'bm_clicks', $clickDimensions, $date, $date );

        return $this->leftJoin( $viewRows, $clickRows, [ 'Date', 'trackingid' ], ['bm_clicks' => 0] );
        
    }

    
}