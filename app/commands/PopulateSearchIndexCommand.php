<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PopulateSearchIndexCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'search:populate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Populates the search index with some sample movie data.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        $client = new \Solarium\Client(Config::get('solr'));

        // open up the CSV
        $csv_filepath = storage_path() . '/movies.csv';

        $fp = fopen($csv_filepath, 'r');

        // Now let's start importing
        while (($row = fgetcsv($fp, 1000, ";")) !== FALSE) {

            // get an update query instance
            $update = $client->createUpdate();

            // Create a document
            $doc = $update->createDocument();

            // set the ID
            $doc->id = $row[0];

            // ..and the title
            $doc->title = $row[1];

            // The year, rating and runtime columns don't always have data
            if (strlen($row[2])) {
                $doc->year = $row[2];
            }
            if (strlen($row[3])) {
                $doc->rating = $row[3];
            }
            if (strlen($row[4])) {
                $doc->runtime = $row[4];
            }

            // set the synopsis
            $doc->synopsis = $row[5];

            // We need to create an array of cast members
            $cast = array();

            // Rows 6 through 10 contain (or don't contain) cast members' names
            for ($i = 6; $i <= 10; $i++) {
                if ((isset($row[$i])) && (strlen($row[$i]))) {
                    $cast[] = $row[$i];
                }
            }

            // ...then we can assign the cast member array to the document
            $doc->cast = $cast;

            // Let's simply add and commit straight away.
            $update->addDocument($doc);
            $update->addCommit();

            // this executes the query and returns the result
            $result = $client->update($update);

        }

        fclose($fp);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('example', InputArgument::OPTIONAL, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}
