#!/usr/bin/env php
<?php
ini_set('memory_limit', '2048M');
define('LIB_DIR', dirname(__FILE__) . '/lib/');
define('MORIARTY_DIR', LIB_DIR.'moriarty/');
define('MORIARTY_ARC_DIR', LIB_DIR.'arc/');
include_once MORIARTY_DIR.'moriarty.inc.php';
include_once MORIARTY_DIR.'simplegraph.class.php';
include_once 'inc/sequencegraph.class.php';
include_once 'inc/csvreader.class.php';
include_once 'inc/vertere.class.php';
include_once 'inc/diagnostics.php';

define('NS_CONV', 'http://example.com/schema/data_conversion#');
define('NS_RDF', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');

//Load spec and create new Vertere converter
$spec_file_name = $argv[1];
$spec_file = file_get_contents($spec_file_name);
$spec = new SimpleGraph();
$spec->from_turtle($spec_file);

//Find the spec in the graph
$specs = $spec->get_subjects_of_type(NS_CONV.'Spec');
if (count($specs) != 1) { throw new Exception('spec document must contain exactly one conversion spec'); }
$spec_uri = $specs[0];

//Find format and create reader for it
$format_uri = $spec->get_first_resource($spec_uri, NS_CONV.'format');
$reader = $format_uri == NS_CONV.'CSV' ? new CsvReader(STDIN) : null;
if ($reader == null) { abort("Format ${format_uri} is not supported."); }

$vertere = new Vertere($spec, $spec_uri);

//Read off header rows
$header_rows = $spec->get_first_literal($spec_uri, NS_CONV.'header_rows');
for ($i = 0; $i < $header_rows; $i++) { $reader->next_record(); }
//Create graph from records
while ($record = $reader->next_record()) {
	$output_graph = $vertere->convert_array_to_graph($record);
	echo $output_graph->to_ntriples();
	$output_graph->remove_all_triples();
}
