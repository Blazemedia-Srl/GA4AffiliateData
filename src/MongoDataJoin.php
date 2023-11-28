<?php

namespace Blazemedia\Ga4AffiliateData;

use MongoDB\Client;
use Traversable;

/**
 * Consente di effettuare leftJoin tra semplici array associativi,
 * con complessità lineare
 */
trait MongoDataJoin {

    /**
     * Effettua una left join tra due array,
     * inserisce campi vuoti se non trova corrispondenze
     *
     * @param array $left
     * @param array $right
     * @param array $onFields   - array dei campi sui quali effettuare la join
     * @param array $defaultRight - campi vuoti per le corrispondenze mancanti
     * @return array
     */
    function leftJoin(array $left, array $right, array $onFields, $defaultRight = []): array {
        $indexedLeft = $this->indexOn($left, $onFields);
        $indexedRight = $this->indexOn($right, $onFields);
        
        return array_map( fn( $index ) => isset( $indexedRight[$index] ) ? 
        array_merge( $indexedLeft[ $index ], $indexedRight[$index] ) : 
        array_merge( $indexedLeft[ $index ], $defaultRight ),

array_keys( $indexedLeft )
);
        // Inserisci gli array indicizzati nelle collezioni MongoDB
        $this->insertArrayIntoMongoDBCollection($indexedLeft, 'indexedLeft');
        $this->insertArrayIntoMongoDBCollection($indexedRight, 'indexedRight');

        // Esegui l'operazione di left join utilizzando MongoDB Aggregation Framework
        $result = $this->performMongoDBLeftJoin('indexedLeft', 'indexedRight', $onFields);

        // Svuota le collezioni dopo il merge
        $this->clearMongoDBCollections(['indexedLeft', 'indexedRight']);

        return iterator_to_array($result);
    }

    /**
     * Inserisce un array in una collezione MongoDB
     *
     * @param array $data
     * @param string $collectionName
     */
    function insertArrayIntoMongoDBCollection(array $data, string $collectionName): void {
        $client = new Client("mongodb://localhost:27017");
        $db = $client->selectDatabase("datalayer");
        $collection = $db->selectCollection($collectionName);
        $collection->insertMany($data);
    }

    /**
     * Esegue l'operazione di left join tra due collezioni MongoDB
     *
     * @param string $leftCollectionName
     * @param string $rightCollectionName
     * @param array $onFields
     * @return \MongoDB\Driver\Cursor
     */
    function performMongoDBLeftJoin(string $leftCollectionName, string $rightCollectionName, array $onFields): \MongoDB\Driver\Cursor {
        $client = new Client("mongodb://localhost:27017");
        $db = $client->selectDatabase("datalayer");
        $leftCollection = $db->selectCollection($leftCollectionName);

        $pipeline = [
            [
                '$lookup' => [
                    'from' => $rightCollectionName,
                    'localField' => '_id',
                    'foreignField' => '_id',
                    'as' => 'joinedData',
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$joinedData',
                    'preserveNullAndEmptyArrays' => true,
                ],
            ],
            [
                '$replaceRoot' => [
                    'newRoot' => [
                        '$mergeObjects' => ['$joinedData', '$$ROOT'],
                    ],
                ],
            ],
        ];

        return $leftCollection->aggregate($pipeline);
    }

    /**
     * Svuota le collezioni MongoDB specificate
     *
     * @param array $collectionNames
     */
    function clearMongoDBCollections(array $collectionNames): void {
        $client = new Client("mongodb://localhost:27017");
        $db = $client->selectDatabase("datalayer");

        foreach ($collectionNames as $collectionName) {
            $collection = $db->selectCollection($collectionName);
            $collection->deleteMany([]);
        }
    }

    /**
     * Crea una versione indicizzata dell'array in base ai campi stabiliti
     *
     * @param array $data   - dati da indicizzare
     * @param array $fields - campi usati per creare l'indice
     * @return array 
     */
    function indexOn(array $data, array $fields): array {
        return array_reduce($data, function ($indexedData, $row) use ($fields) {
            $index = implode('_', array_map(function ($field) use ($row) {
                return $row[$field];
            }, $fields));

            // Aggiungi l'_id come campo aggiuntivo al documento
            $row['_id'] = $index;

            $indexedData[] = $row;

            return $indexedData;
        }, []);
    }
}
