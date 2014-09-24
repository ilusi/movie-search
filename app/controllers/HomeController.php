<?php

class HomeController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/
    /**
     * @var The SOLR client.
     */
    protected $client;

    /**
     * Constructor
     **/
    public function __construct()
    {
        // create a client instance
        $this->client = new \Solarium\Client(Config::get('solr'));
    }


	public function showWelcome()
	{
		return View::make('hello');
	}


    public function getIndex0()
    {
        if (Input::has('q')) {
            // Create a search query
            $query = $this->client->createSelect();

            // Set the query string
            $query->setQuery('%P1%', array(Input::get('q')));

            // Add Disjunction Max (multiple queries).
            $dismax = $query->getDisMax();
            $dismax->setQueryFields('title^3 cast^2 synopsis^1'); // priority

            // Execute the query and return the result
            $resultset = $this->client->select($query);

            // Pass the resultset to the view and return.
            return View::make('home.index', array(
                'q'         => Input::get('q'),
                'resultset' => $resultset,
            ));
        }

        return View::make('home.index');
    }

    /**
     * Display the search form / run the search.
     */
    public function getIndex()
    {

        if (Input::has('q')) {

            $select = array(
                'query'         => Input::get('q'),
                'query_fields' => array('title', 'cast', 'synopsis'),
                'start'         => 0,
                'rows'          => 100,
                //'fields'        => array('*', 'id','title','synopsis','cast', 'score'),
                'fields'        => array('*', 'year', 'score', 'cast'),
                //'sort'          => array('year' => 'asc'),
                /**
                'filterquery' => array(
                'maxprice' => array(
                'query' => 'price:[1 TO 300]'
                ),
                ),
                 **/

                'component' => array(
                    'facetset' => array(
                        'facet' => array(
                            // notice this config uses an inline key value, instead of array key like the filterquery
                            array('type' => 'field', 'key' => 'rating', 'field' => 'rating'),
                        )
                    ),
                ),

            );

            // Create a search query
            $query = $this->client->createSelect();

            // Geta  query helper instance
            $helper = $query->getHelper();

            // Set the query string
            //$query->setQuery(Input::get('q'));
            $query->setQuery('%P1%', array(Input::get('q')));

            // Set the fields we wish to return
            $query->clearFields()
                ->addFields(
                    array(
                        'id',
                        'title',
                        'year',
                        'cast',
                        'synopsis'
                    )
                );

            // Set the start point, and number of rows to retrieve
            $query->setStart(0);
            $query->setRows(200);

            //$query->addSort('score', 'desc');
            //$query->addSort('year', 'asc');

            // Create a DisMax query
            $dismax = $query->getDisMax();

            // Set the fields to query, and their relative weights
            $dismax->setQueryFields('title^3 cast^2 synopsis^1');

            // Get the Facet Set
            $facetSet = $query->getFacetSet();

            // Facet on the MPGG rating field.
            $facetSet->createFacetField('rating')
                ->setField('rating');

            // Optionally filter on the MPGG rating
            if (Input::has('rating')) {
                //$query->createFilterQuery('rating')->setQuery(sprintf('rating:%s', Input::get('rating')));
                $query->createFilterQuery('rating')->setQuery('rating:%T1%', array(Input::get('rating')));

            }

            // Optionally filter to a particular decade
            if (Input::has('decade')) {
                //$query->createFilterQuery('years')->setQuery(sprintf('year:[%d TO %d]', Input::get('decade'), (Input::get('decade') + 9)));
                $query->createFilterQuery('years')->setQuery($helper->rangeQuery('year', Input::get('decade'), (Input::get('decade') + 9)));
            }

            // Create a facet based on the movie's decade
            $facet = $facetSet->createFacetRange('years')
                ->setField('year')
                ->setStart(1900)
                ->setGap(10)
                ->setEnd(2020);

            // Get highlighting component, and apply settings
            $hl = $query->getHighlighting();
            $hl->setSnippets(5);
            $hl->setFields(array('title', 'synopsis'));

            $hl->setSimplePrefix('<span style="background:yellow;">');
            $hl->setSimplePostfix('</span>');



            // Execute the query and return the result
            $resultset = $this->client->select($query);

            //krumo($resultset->getFacetSet()->getFacet('rating')->getValue());

            //krumo($resultset->getStats());

            // Get the highlighting component
            $highlighting = $resultset->getHighlighting();

            // Pass the resultset and highlighting component to the view and return.
            return View::make('home.index', array(
                'q'                 =>  Input::get('q'),
                'resultset'         =>  $resultset,
                'highlighting'      =>  $highlighting,
            ));

        }

        // No query to execute, just return the search form.
        return View::make('home.index');

    }


    public function getIndex2()
    {
        $facetSet = $query->getFacetSet();

        $facetSet->createFacetField('rating')
                 ->setField('rating');

        $facet = $resultset->getFacetSet()->getFacet('rating');
        foreach($facet as $value => $count) {
            echo $value . ' [' . $count . ']<br/>';
        }

        $facet = $facetSet->createFacetRange('years')
                            ->setField('year')
                            ->setStart(1900)
                            ->setGap(10)
                            ->setEnd(2020);

        $facet = $resultset->getFacetSet()->getFacet('years');
        foreach($facet as $range => $count) {
            if ($count) {
                printf('%d"s (%d)<br />', $range, $count);
            }
        }

        if (Input::has('rating')) {
            $query->createFilterQuery('rating')->setQuery(sprintf('rating:%s', Input::get('rating')));
        }

        $helper = $query->getHelper();

        if (Input::has('decade')) {
            $query->createFilterQuery('years')->setQuery($helper->rangeQuery('year', Input::get('decade'), (Input::get('decade') + 9)));
        }
    }
}
