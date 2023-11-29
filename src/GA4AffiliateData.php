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
    public function getViewClickData( $propertyId = '295858603', $date = 'yesterday' ) : array {

        $commonDimensions = [            
            new Dimension([ 'name' => 'eventName' ]),                        
            new Dimension([ 'name' => 'customEvent:data_bmaff_trackingid' ]),
        ];
        
        $viewChunkedDimensions = [

            $commonDimensions, 

            array_merge( $commonDimensions, [           
                new Dimension([ 'name' => 'customEvent:data_bmaff_subject' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_program' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_platform' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_filone' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_author' ]), 
            ]),

            array_merge( $commonDimensions, [                
                
                new Dimension([ 'name' => 'customEvent:data_bmaff_tipologia' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_custom' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_format' ]),
                new Dimension([ 'name' => 'customEvent:data_bmaff_alias' ]),
                //new Dimension([ 'name' => 'customEvent:data_bmaff_postid' ]),
            ])
        ];

        $viewRowsPartials = array_map( fn( $dimensions ) => $this->getData( $propertyId, $date, $dimensions, 'BM View'), $viewChunkedDimensions);

        $viewRows = array_shift($viewRowsPartials);

        $viewRows = array_reduce( 
            $viewRowsPartials, 
            fn( $rows, $partial ) => $this->leftJoin( $rows, $partial, [ 'tracking_id', 'bm_views' ], [ 'format' => '', 'custom' => '' ] ),
            $viewRows 
        );

        $clickRows = $this->getData( $propertyId, $date, $commonDimensions, 'BM Click' );

        $blend = $this->leftJoin( $viewRows, $clickRows, [ 'tracking_id' ], ['bm_clicks' => 0] );

        return $blend;
    }
    


    protected function printrows( $rows, $num) {

        echo "\n".implode(' ', array_keys($rows[0] ))."\n";

        foreach( array_splice($rows,0,$num) as $row ) {

            echo "\n".implode(' ', $row )."\n";

        }
    }
}