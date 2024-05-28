<?php

namespace Blazemedia\Ga4AffiliateData;

use Blazemedia\Ga4AffiliateData\Utils\DataJoin;
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
    public function getViewClickData( $propertyId = '295858603', $date = 'yesterday' ) : array {

        $viewDimensions = [
            new Dimension([ 'name' => 'Date' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_postid' ]),
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),            
        ];

        $viewChunkedDimensions = [

            array_merge( $viewDimensions, [                
                new Dimension([ 'name' => 'customEvent:data_bmaff_subject' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_program' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_platform' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_filone' ])
            ]),

            array_merge( $viewDimensions, [
                new Dimension([ 'name' => 'customEvent:data_bmaff_domain' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_author' ]), 
                new Dimension([ 'name' => 'customEvent:data_bmaff_tipologia' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_custom' ])
            ]),

            array_merge( $viewDimensions, [                                                
                new Dimension([ 'name' => 'customEvent:data_bmaff_format' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_alias' ]), 
                                            
            ])
        ];


        $clickDimensions =  [
            new Dimension([ 'name' => 'Date' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_postid' ]),            
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),        
        ];

        
        $viewRowsPartials = array_map( fn( $dimensions ) => $this->getData( $propertyId, $date, $dimensions, 'BM View'), $viewChunkedDimensions);

        $viewRows = array_shift($viewRowsPartials);

        $viewRows = array_reduce( 
            $viewRowsPartials, 
            fn( $rows, $partial ) => $this->leftJoin( $rows, $partial, [ 'Date', 'tracking_id', 'bm_views', 'postid' ], [ 'format' => '', 'custom' => '' ] ),
            $viewRows 
        );
        
        /// questa parte si potrà anche rimuovere una volta stabilizzati i postid
        $clickRows = $this->getData( $propertyId, $date, $clickDimensions, 'BM Click' );

        $blend = $this->leftJoin( $viewRows, $clickRows, [ 'Date', 'tracking_id', 'postid' ], ['bm_clicks' => 0]);

        return $blend;
    }
    

    protected function printrows( $rows, $num) {

        echo "\n".implode(' ', array_keys($rows[0] ))."\n";

        foreach( array_splice($rows,0,$num) as $row ) {

            echo "\n".implode(' ', $row )."\n";

        }
    }
}