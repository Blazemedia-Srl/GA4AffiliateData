<?php

namespace Blazemedia\Ga4AffiliateData;

use MongoDB\Client;

/**
 * Consente di effettuare leftJoin tra semplici array associativi,
 * con complessità lineare
 */
trait DataJoin {

    /**
     * Effettua una left join tra due array,
     * inserisce campi vuoti se non trova corrispondenze
     *
     * @param array $left
     * @param array $right
     * @param array $onFields   - array dei campi sui queli effettuare la join
     * @param array $defaultRight - campi vuoti per le corrispondenze mancanti
     * @return array
     */
    function leftJoin( array $left, array $right, array $onFields, $defaultRight = [] ) : array {

        $indexedLeft  = $this->indexOn( $left,  $onFields );
        $indexedRight = $this->indexOn( $right, $onFields );

        $db = new Client("mongodb://localhost:27017");

        $collection = $db->selectCollection('datalayer','datajoin');

        $pipeline = [
            [
                '$lookup' => [
                    'from' => 'datajoin',
                    'localField' => json_encode($indexedLeft),
                    'foreignField' => json_encode($indexedRight),
                    'as' => 'joinedData'
                ]
            ],
            // Altri stage di aggregazione se necessario
        ];
        
        $result = $collection->aggregate($pipeline);
        
        return $result;

    }


    /**
     * Crea una versione indicizzata dell'array in base ai campi stabiliti
     *
     * @param array $data   - dati da indicizzare
     * @param array $fields - campi usati per creare l'indice
     * @return array 
     */
    function indexOn( array $data, array $fields ) : array {

        return array_reduce( $data, function( $indexedData, $row ) use ( $fields ) {

            /// crea l'indice come combinazione dei valori dei campi nella riga
            $index = array_reduce( $fields, fn( $index, $field ) => $index . $row[$field], '' );
            
            $indexedData[ $index ] = $row;            
            
            return $indexedData;

        }, [] );

    }
}
