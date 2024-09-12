<?php

namespace Blazemedia\Ga4AffiliateData;

use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\Filter\InListFilter;
use Google\Analytics\Data\V1beta\FilterExpressionList;

class GA4Client
{

    protected $client;

    function __construct(protected string $keyPath)
    {
        $this->client = $this->getClient();
    }


    /**
     * Make an API call.
     * reference : https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema?hl=en#dimensions
     * 
     * @param array $args
     */
    public function runReport(array $args)
    {
        $default_args =  [

            'property' => 'properties/295858603', // TEST OMNIA GA4

            'dateRanges' => [new DateRange(['start_date' => 'yesterday', 'end_date' => 'yesterday'])],

            'metrics'   => [new Metric(['name' => 'eventCount'])],

            'dimensionFilter' => new FilterExpression([
                'filter' => new Filter([
                    'field_name'    => 'eventName',
                    'string_filter' => new StringFilter(['value' => 'Page View'])
                ])
            ]),

            'limit' => 100000

        ];

        return $this->client->runReport(array_merge($default_args, $args));
    }

    /**
     * Make a generic API call.
     * 
     * @param string $jsonArgs
     * 
     * eg. {
     *  "dimensions":[{"name":"pagePath"}],
     *  "metrics":[{"name":"screenPageViews"}],
     *  "dateRanges":[{"startDate":"yesterday","endDate":"today"}],
     *  "dimensionFilter":{
     *       "filter":{
     *           "fieldName":"pagePath",
     *           "inListFilter":{
     *               "values":
     *                   ["/post-id-1","/post-id-2","/post-id-3","/post-id-4","/post-id-5","/post-id-6","/post-id-7"]
     *           }
     *      }
     *  }
     * }
     */
    public function runGenericReport(string $jsonArgs)
    {
        $args = $this->jsonParamToGA4($jsonArgs);
        return $this->client->runReport($args);
    }

    /**
     * Convert json params to GA4 params
     * 
     * @param string $jsonArgs
     * @return array
     * 
     * TODO: Complete the conversion cases
     */
    private function jsonParamToGA4(string $jsonArgs)
    {
        /*
        $params = [

            'property' => "properties/{$propertyId}",
            
            'dateRanges' => [ new DateRange([ 'start_date' => $date, 'end_date' => $date ]) ],

            'metrics'    => [ new Metric([ 'name' => 'eventCount' ]) ],
            
            'dimensions' => $dimensions,
            
            'limit' => 100000,

            'dimensionFilter' => new FilterExpression([
                'and_group' => new FilterExpressionList([
                    'expressions' => [
                        new FilterExpression([
                            'filter' => new Filter([
                                'field_name'    => 'eventName',
                                'string_filter' => new StringFilter( [ 'value' => $eventName, 'match_type' => MatchType::EXACT ] )
                            ])
                        ]),

                        new FilterExpression([
                            'filter' => new Filter([
                                'field_name'    => 'sessionDefaultChannelGroup',
                                'string_filter' => new StringFilter( [ 'value' => 'paid search' , 'match_type' =>  MatchType::EXACT ])
                            ])
                        ]),

                        new FilterExpression([
                            'filter' => new Filter([
                                'field_name'    => 'pagepath',
                                'string_filter' => new StringFilter( [ 'value' => $path , 'match_type' => MatchType::CONTAINS ])
                            ])
                        ])
                    ]
                ])
            ])
        ];
        */

        $parsedArgs = json_decode($jsonArgs, true);
        $params = [];

        if ($parsedArgs['property']) {
            $params['property'] = $parsedArgs['property'];
        }

        if ($parsedArgs['dateRanges']) {
            foreach ($parsedArgs['dateRanges'] as $dateRange) {
                $params['dateRanges'][] = new DateRange(['start_date' => $dateRange['startDate'], 'end_date' => $dateRange['endDate']]);
            }
        }

        if ($parsedArgs['metrics']) {
            foreach ($parsedArgs['metrics'] as $metric) {
                $params['metrics'][] = new Metric(['name' => $metric['name']]);
            }
        }

        if ($parsedArgs['dimensions']) {
            foreach ($parsedArgs['dimensions'] as $dimension) {
                $params['dimensions'][] = new Dimension(['name' => $dimension['name']]);
            }
        }

        if ($parsedArgs['dimensionFilter']) {

            if ($parsedArgs['dimensionFilter']['filter']) {

                if ($parsedArgs['dimensionFilter']['filter']['inListFilter']) {
                    $params['dimensionFilter'] = new FilterExpression([
                        'filter' => new Filter([
                            'field_name'    => $parsedArgs['dimensionFilter']['filter']['fieldName'],
                            'in_list_filter' => new InListFilter(['values' => $parsedArgs['dimensionFilter']['filter']['inListFilter']['values']])
                        ])
                    ]);
                }

                if ($parsedArgs['dimensionFilter']['filter']['stringFilter']) {
                    $params['dimensionFilter'] = new FilterExpression([
                        'filter' => new Filter([
                            'field_name'    => $parsedArgs['dimensionFilter']['filter']['fieldName'],
                            'string_filter' => new StringFilter(['value' => $parsedArgs['dimensionFilter']['filter']['value'], 'match_type' => $parsedArgs['dimensionFilter']['filter']['match_type']])
                        ])
                    ]);
                }
            }

            if ($parsedArgs['dimensionFilter']['and_group']) {
                $params['dimensionFilter'] = new FilterExpression([
                    'and_group' => new FilterExpressionList([
                        'expressions' => []
                    ])
                ]);

                foreach ($parsedArgs['dimensionFilter']['and_group']['expressions'] as $expression) {
                    $params['dimensionFilter']['and_group']['expressions'][] = new FilterExpression([
                        'filter' => new Filter([
                            'field_name'    => $expression['filter']['fieldName'],
                            'string_filter' => new StringFilter(['value' => $expression['filter']['value'], 'match_type' => $expression['filter']['match_type']])
                        ])
                    ]);
                }
            }
        }
    }


    /**
     * Istanzia un client per GA4
     *
     * @return BetaAnalyticsDataClient client
     */
    protected function getClient()
    {
        $credentials = file_get_contents($this->keyPath);

        if (empty($credentials)) throw new \ErrorException('Credentials file not found');

        return new BetaAnalyticsDataClient([
            'credentials' => json_decode($credentials, true)
        ]);
    }

    /**
     * Ritorna i dati di un certo evento in un intervallo di tempo
     *
     * @param string $propertyId    - stream GA4 da cui estrarre i dati
     * @param string $eventName     - nome dell'evento
     * @param array  $dimensions    - campi da estrarre ( array di max 9 string )
     * @param string $startDate     - data di inizio intervallo
     * @param string $endDate       - data di fine intervallo
     * @return array
     */
    public function getIntervalData(string $propertyId, $startDate = 'yesterday', $endDate = 'today', array $dimensions = [], string $eventName = '')
    {
        $params = [
            'property' => "properties/{$propertyId}",
            'dateRanges' => [new DateRange(['start_date' => $startDate, 'end_date' => $endDate])],
            'metrics'    => [new Metric(['name' => 'eventCount'])],
            'dimensions' => $dimensions,
            'limit' => 100000
        ];

        if ($eventName != '') {
            $params['dimensionFilter'] = new FilterExpression([
                'filter' => new Filter([
                    'field_name'    => 'eventName',
                    'string_filter' => new StringFilter(['value' => $eventName])
                ])
            ]);
        }

        /// prende i dati da GA4
        $response = $this->client->runReport($params);

        /// prende i nomi delle colonne ( le ripulisce da customEvent:data_bmaff_ ) e li associa agli indici
        /// è un elenco di  [ nome_colonna => indice ]
        $dimensions = $this->getDimensionsMap($response->getDimensionHeaders());

        /// cicla le righe
        $rows = [];
        foreach ($response->getRows() as $row) {

            $rows[] = array_reduce(array_keys($dimensions), function ($dataRow, $dimension) use ($row, $dimensions) {

                /// assengna ogni valore alla dimensione corrispondente
                $dataRow[$dimension] = $row->getDimensionValues()[$dimensions[$dimension]]->getValue();

                /// e restituisce la riga
                return $dataRow;
            }, [$this->getEventMap($eventName) => $row->getMetricValues()[0]->getValue()]);
        }

        return $rows;
    }

    /**
     * Ritorna i dati di un certo evento
     *
     * @param string $propertyId    - stream GA4 da cui estrarre i dati
     * @param string $eventName     - nome dell'evento
     * @param array  $dimensions    - campi da estrarre ( array di max 9 string )
     * @param string $date          - data da considerare
     * @return array
     */
    public function getData(string $propertyId, $date = 'yesterday', array $dimensions = [], string $eventName = '')
    {
        return $this->getIntervalData($propertyId, $date, $date, $dimensions, $eventName);
    }


    protected function getDimensionsMap($dimensionHeaders)
    {
        $dimensions = [];

        foreach ($dimensionHeaders as $idx => $dimensionHeader) {

            $dimensions[$dimensionHeader->getName()] = $idx;
        }

        return $dimensions;
    }

    protected function getEventMap($event)
    {
        return str_replace([], [], $event);
    }
}
