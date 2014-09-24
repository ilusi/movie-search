<?php

class SolrController extends BaseController {
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
//        $this->delete();
//        $this->add();
        return $this->ping();
    }


    public function delete()
    {
        // get an update query instance
        $update = $this->client->createUpdate();

        // add the delete query and a commit command to the update query
        $update->addDeleteQuery('id:123');
        $update->addCommit();

        // this executes the query and returns the result
        $result = $this->client->update($update);
    }


    public function add()
    {
        $update = $this->client->createUpdate();
        $doc = $update->createDocument();

        $doc->id = 123;
        $doc->title = 'Some Movie';
        $doc->cast = array('Sylvester Stallone', 'Marylin Monroe', 'Macauley Culkin');

        $update->addDocument($doc);
        $update->addCommit();
        $result = $this->client->update($update);
    }


    public function ping()
    {
        // create a ping query
        $ping = $this->client->createPing();

        // execute the ping query
        try {
            $result = $this->client->ping($ping);
            var_dump($result);
            die();
        } catch (Solarium\Exception $e) {
            // the SOLR server is inaccessible, do something
            print_r($e);
            die('Cannot ping SOLR!');
        }
    }
}