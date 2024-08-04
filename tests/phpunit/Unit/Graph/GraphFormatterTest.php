<?php

namespace SRF\Tests\Unit\Formats;

use SRF\Graph\GraphFormatter;
use SRF\Graph\GraphOptions;

// php ../../tests/phpunit/phpunit.php tests/phpunit/Unit/Graph/GraphFormatterTest.php

/**
 * @covers \SRF\Graph\GraphFormatter
 * @group semantic-result-formats
 *
 * @license GPL-2.0-or-later
 *
 * @author Sebastian Schmid (gesinn.it)
 */
class GraphFormatterTest extends \PHPUnit\Framework\TestCase {
	const PRINTOUTS = [
		'2:::component|filled|yellow' =>
			[
				'main column' => true,
				'type' => '_wpg',
				'is page' => true,
				'label for' => null,
				'chain' => '',
				'node attrs' =>
					[
						'shape' => 'component',
						'style' => 'filled',
						'fillcolor' => 'yellow',
						'color' => 'black',
						'fontcolor' => 'black',
					],
				'edge attrs' =>
					[
						'style' => 'filled',
						'fillcolor' => 'yellow',
						'label' => '',
						'URL' => '[[Свойство:]]',
						'color' => 'black',
						'fontcolor' => 'black',
					],
				'nodes' =>
					[
						'Semantic MediaWiki' =>
							[
								'label' => 'Semantic MediaWiki',
								'fields' =>
									[
										'Has abbreviation' =>
											[
												'label' => 'Abbreviation',
												'type' => '_txt',
												'align' => 'left',
												'values' =>
													[
														0 =>
															[
																'text' => 'SMW',
															],
													],
												'href' => '[[Свойство:Has abbreviation]]',
											],
										'Has version' =>
											[
												'label' => 'Version',
												'type' => '_txt',
												'align' => 'left',
												'values' =>
													[
														0 =>
															[
																'text' => '4.1.3',
															],
													],
												'href' => '[[Свойство:Has version]]',
											],
									],
							],
						'Semantic Result Formats' =>
							[
								'label' => 'Semantic Result Formats',
								'fields' =>
									[
										'Has abbreviation' =>
											[
												'label' => 'Abbreviation',
												'type' => '_txt',
												'align' => 'left',
												'values' =>
													[
														0 =>
															[
																'text' => 'SRF',
															],
													],
												'href' => '[[Свойство:Has abbreviation]]',
											],
										'Has version' =>
											[
												'label' => 'Version',
												'type' => '_txt',
												'align' => 'left',
												'values' =>
													[
														0 =>
															[
																'text' => '4.2.1',
															],
													],
												'href' => '[[Свойство:Has version]]',
											],
									],
							],
					],
				'edges' =>
					[
					],
				'is node' => true,
				'parent' => '',
			],
		'1:Abbreviation:Has_abbreviation::' =>
			[
				'main column' => false,
				'type' => '_txt',
				'is page' => false,
				'label for' => null,
				'chain' => 'Has abbreviation',
				'node attrs' =>
					[
						'color' => 'red',
						'fontcolor' => 'red',
					],
				'edge attrs' =>
					[
						'label' => 'Abbreviation',
						'URL' => '[[Свойство:Has abbreviation]]',
						'color' => 'red',
						'fontcolor' => 'red',
					],
				'nodes' =>
					[
					],
				'edges' =>
					[
					],
				'is node' => false,
				'parent' => '',
			],
		'1:Author:Has_author::signature|filled|lightblue' =>
			[
				'main column' => false,
				'type' => '_wpg',
				'is page' => true,
				'label for' => null,
				'chain' => 'Has author',
				'node attrs' =>
					[
						'shape' => 'signature',
						'style' => 'filled',
						'fillcolor' => 'lightblue',
						'color' => 'green',
						'fontcolor' => 'green',
					],
				'edge attrs' =>
					[
						'style' => 'filled',
						'label' => 'Author',
						'URL' => '[[Свойство:Has author]]',
						'color' => 'green',
						'fontcolor' => 'green',
					],
				'nodes' =>
					[
						'Jeroen De Dauw' =>
							[
								'label' => 'Jeroen De Dauw',
								'fields' =>
									[
										'Has author.Attachment link' =>
											[
												'label' => '',
												'type' => '_wpg',
												'align' => 'left',
												'values' =>
													[
														0 =>
															[
																'url' => '[[Файл:Duck.jpg]]',
																'image' => '[[Файл:Duck.jpg|width=120]]',
																'text' => 'Файл:Duck.jpg',
															],
														1 =>
															[
																'url' => '[[Файл:Markus-Kroetzsch-2014.jpg]]',
																'image' => '[[Файл:Markus-Kroetzsch-2014.jpg|width=120]]',
																'text' => 'Файл:Markus-Kroetzsch-2014.jpg',
															],
													],
												'href' => '[[Свойство:Attachment link]]',
											],
									],
							],
						'James Hong Kong' =>
							[
								'label' => 'James Hong Kong',
								'fields' =>
									[
									],
							],
						'Yaron Koren' =>
							[
								'label' => 'Yaron Koren',
								'fields' =>
									[
									],
							],
						'Markus Krötzsch' =>
							[
								'label' => 'Markus Krötzsch',
								'fields' =>
									[
									],
							],
						'Denny Vrandečić' =>
							[
								'label' => 'Denny Vrandečić',
								'fields' =>
									[
									],
							],
						'Klaus Lassleben' =>
							[
								'label' => 'Klaus Lassleben',
								'fields' =>
									[
									],
							],
						'Stephan Gambke' =>
							[
								'label' => 'Stephan Gambke',
								'fields' =>
									[
									],
							],
					],
				'edges' =>
					[
						0 =>
							[
								0 => 'Semantic MediaWiki',
								1 => 'Jeroen De Dauw',
							],
						1 =>
							[
								0 => 'Semantic MediaWiki',
								1 => 'James Hong Kong',
							],
						2 =>
							[
								0 => 'Semantic MediaWiki',
								1 => 'Yaron Koren',
							],
						3 =>
							[
								0 => 'Semantic MediaWiki',
								1 => 'Markus Krötzsch',
							],
						4 =>
							[
								0 => 'Semantic MediaWiki',
								1 => 'Denny Vrandečić',
							],
						5 =>
							[
								0 => 'Semantic MediaWiki',
								1 => 'Klaus Lassleben',
							],
						6 =>
							[
								0 => 'Semantic Result Formats',
								1 => 'Jeroen De Dauw',
							],
						7 =>
							[
								0 => 'Semantic Result Formats',
								1 => 'James Hong Kong',
							],
						8 =>
							[
								0 => 'Semantic Result Formats',
								1 => 'Stephan Gambke',
							],
						9 =>
							[
								0 => 'Semantic Result Formats',
								1 => 'Yaron Koren',
							],
					],
				'is node' => true,
				'parent' => '',
			],
		'1:Version:Has_version::field' =>
			[
				'main column' => false,
				'type' => '_txt',
				'is page' => false,
				'label for' => null,
				'chain' => 'Has version',
				'node attrs' =>
					[
						'color' => 'blue',
						'fontcolor' => 'blue',
					],
				'edge attrs' =>
					[
						'label' => 'Version',
						'URL' => '[[Свойство:Has version]]',
						'color' => 'blue',
						'fontcolor' => 'blue',
					],
				'nodes' =>
					[
					],
				'edges' =>
					[
					],
				'is node' => false,
				'parent' => '',
			],
		'4::Has author.Attachment link::field|120px' =>
			[
				'main column' => false,
				'type' => '_wpg',
				'is page' => false,
				'label for' => null,
				'chain' => 'Has author.Attachment link',
				'node attrs' =>
					[
						'imagewidth' => '120px',
						'color' => 'darkviolet',
						'fontcolor' => 'darkviolet',
					],
				'edge attrs' =>
					[
						'imagewidth' => '120px',
						'label' => '',
						'URL' => '[[Свойство:Attachment link]]',
						'color' => 'darkviolet',
						'fontcolor' => 'darkviolet',
					],
				'nodes' =>
					[
					],
				'edges' =>
					[
					],
				'is node' => false,
				'parent' => 'Has author',
			],
		'1:Requires:Requires::' =>
			[
				'main column' => false,
				'type' => '_wpg',
				'is page' => true,
				'label for' => null,
				'chain' => 'Requires',
				'node attrs' =>
					[
						'color' => 'gold',
						'fontcolor' => 'gold',
					],
				'edge attrs' =>
					[
						'label' => 'Requires',
						'URL' => '[[Свойство:Requires]]',
						'color' => 'gold',
						'fontcolor' => 'gold',
					],
				'nodes' =>
					[
					],
				'edges' =>
					[
						0 =>
							[
								0 => 'Semantic Result Formats',
								1 => 'Semantic MediaWiki',
							],
					],
				'is node' => true,
				'parent' => '',
			],
	];

	/** @var array $cases An array of test cases. */
	private $cases = [
		'Simple' => [
			'params' => [ 'graphfields' => false ], // @see https://www.semantic-mediawiki.org/wiki/Help:Graph_format
			'nodes' => [
				[ 'name' => 'Team:Alpha', 'label' => 'Alpha', 'parents' => [
					[ 'predicate' => 'Casted', 'object' => 'Person:Alexander Gesinn' ]
				] ],
				[ 'name' => 'Team:Beta', 'label' => 'Beta', 'parents' => [
					[ 'predicate' => 'Casted', 'object' => 'Person:Sebastian Schmid' ],
					[ 'predicate' => 'Casted', 'object' => 'Person:Alexander Gesinn' ],
					[ 'predicate' => 'Part of Team', 'object' => 'Team:Alpha' ],
				] ]
			],
			'legend' => '<div class="graphlegend">' .
				'<div class="graphlegenditem" style="color: black">black: Casted</div>' .
				'<div class="graphlegenditem" style="color: red">red: Part of Team</div>' .
				'</div>',
			'dot' => <<<'SIMPLE'
digraph "Unit Test" {graph [fontsize=10, fontname="Verdana"]
node [fontsize=10, fontname="Verdana"];
edge [fontsize=10, fontname="Verdana"];
size="100";node [shape=rect];rankdir=LR;
"Team:Alpha" [URL = "[[Team:Alpha]]", label = "Alpha"];
"Team:Beta" [URL = "[[Team:Beta]]", label = "Beta"];
"Person:Alexander Gesinn" -> "Team:Alpha" [label="Casted",fontcolor=black,arrowhead=diamond,color=black];
"Person:Sebastian Schmid" -> "Team:Beta" [label="Casted",fontcolor=black,arrowhead=diamond,color=black];
"Person:Alexander Gesinn" -> "Team:Beta" [label="Casted",fontcolor=black,arrowhead=diamond,color=black];
"Team:Alpha" -> "Team:Beta" [label="Part of Team",fontcolor=red,arrowhead=diamond,color=red];
}
SIMPLE
		],
		'With fields' => [
			'params' => [ 'graphfields' => true ], // @see https://www.semantic-mediawiki.org/wiki/Help:Graph_format
			'nodes' => [
				[ 'name' => 'Team:Alpha', 'label' => 'Alpha', 'parents' => [
					[ 'predicate' => 'Casted', 'object' => 'Person:Alexander Gesinn' ]
				], 'fields' => [
					[ 'name' => 'Rated as', 'value' => 10, 'type' => '_num', 'page' => 'Rating' ]
				] ],
				[ 'name' => 'Team:Beta', 'label' => 'Beta', 'parents' => [
					[ 'predicate' => 'Casted', 'object' => 'Person:Sebastian Schmid' ],
					[ 'predicate' => 'Casted', 'object' => 'Person:Alexander Gesinn' ],
					[ 'predicate' => 'Part of Team', 'object' => 'Team:Alpha' ],
				], 'fields' => [
					[ 'name' => 'Rated as', 'value' => 20, 'type' => '_num', 'page' => 'Rating' ]
				] ]
			],
			'legend' => '<div class="graphlegend">' .
				'<div class="graphlegenditem" style="color: black">black: Casted</div>' .
				'<div class="graphlegenditem" style="color: red">red: Part of Team</div>' .
				'</div>',
			'dot' => <<<'FIELDS'
digraph "Unit Test" {graph [fontsize=10, fontname="Verdana"]
node [fontsize=10, fontname="Verdana"];
edge [fontsize=10, fontname="Verdana"];
size="100";node [shape=rect];rankdir=LR;
"Team:Alpha" [label = <
<table border="0" cellborder="0" cellspacing="1" columns="*" rows="*">
<tr><td colspan="2" href="[[Team:Alpha]]">Alpha</td></tr><hr/>
<tr><td align="left" href="[[Property:Rating]]">Rated as</td><td align="right">10</td></tr>
</table>
>, tooltip = "Alpha"];
"Team:Beta" [label = <
<table border="0" cellborder="0" cellspacing="1" columns="*" rows="*">
<tr><td colspan="2" href="[[Team:Beta]]">Beta</td></tr><hr/>
<tr><td align="left" href="[[Property:Rating]]">Rated as</td><td align="right">20</td></tr>
</table>
>, tooltip = "Beta"];
"Person:Alexander Gesinn" -> "Team:Alpha" [label="Casted",fontcolor=black,arrowhead=diamond,color=black];
"Person:Sebastian Schmid" -> "Team:Beta" [label="Casted",fontcolor=black,arrowhead=diamond,color=black];
"Person:Alexander Gesinn" -> "Team:Beta" [label="Casted",fontcolor=black,arrowhead=diamond,color=black];
"Team:Alpha" -> "Team:Beta" [label="Part of Team",fontcolor=red,arrowhead=diamond,color=red];
}
FIELDS
		]
	];

	/** @const array BASE_PARAMS A non-changing subset of parameters. */
	/** @see https://www.semantic-mediawiki.org/wiki/Help:Graph_format */
	private const BASE_PARAMS = [
		'graphname' => 'Unit Test',
		'graphsize' => '100',
		'graphfontsize' => 10,
		'nodeshape' => 'rect',
		'nodelabel' => 'displaytitle',
		'arrowdirection' => 'LR',
		'arrowhead' => 'diamond',
		'wordwraplimit' => 20,
		'relation' => 'parent',
		'graphlink' => true,
		'graphlabel' => true,
		'graphcolor' => true,
		'graphlegend' => true,
	];

	/**
	 * Create a complete graph for the test case.
	 *
	 * @return GraphFormatter
	 * @var array $case
	 */
	private static function graph( array $case ): GraphFormatter {
		$graph = new GraphFormatter( new GraphOptions( GraphFormatterTest::BASE_PARAMS + $case['params'] ) );
		$nodes = [];
		foreach ( $case['nodes'] as $node ) {
			$graph_node = new GraphNode( $node['name'] );
			$graph_node->setLabel( $node['label'] );
			if ( isset( $node['parents'] ) ) {
				foreach ( $node['parents'] as $parent ) {
					//$graph_node->addParentNode( $parent['predicate'], $parent['object'] );
				}
			}
			if ( isset( $node['fields'] ) ) {
				foreach ( $node['fields'] as $field ) {
					$graph_node->addField( $field['name'], $field['value'], $field['type'], $field['page'] );
				}
			}
			$nodes[] = $graph_node;
		}
		$graph->buildGraph( $nodes );
		return $graph;
	}

	/**
	 * @return array Test cases.
	 */
	public function provideCanConstruct(): array {
		$cases = [];
		foreach ( $this->cases as $name => $case ) {
			$cases[$name] = [ self::BASE_PARAMS + $case['params'] ];
		}
		return $cases;
	}

	/**
	 * @covers       GraphFormatter::__construct()
	 * @dataProvider provideCanConstruct
	 * @param array $params
	 * @return void
	 */
	public function testCanConstruct( array $params ) {
		$this->assertInstanceOf( GraphFormatter::class, new GraphFormatter( new GraphOptions( $params ) ) );
	}

	/**
	 * @return array
	 */
	public function provideGetWordWrappedText(): array {
		return [
			'Simple wrap' => [
				'Lorem ipsum dolor sit amet',
				<<<'WRAPPED0'
Lorem
ipsum
dolor sit
amet
WRAPPED0
			],
			'Unwrappable' => [ 'Supercalifragilisticexpialidocious', 'Supercalifragilisticexpialidocious' ],
			'One line' => [ 'One line', 'One line' ],
			'Empty' => [ '', '' ]
		];
	}

	/**
	 * @covers       GraphFormatter::getWordWrappedText()
	 * @dataProvider provideGetWordWrappedText
	 * @param string $unwrapped
	 * @param string $wrapped
	 * @return void
	 */
	public function testGetWordWrappedText( $unwrapped, $wrapped ) {
		$formatter = new GraphFormatter(
			new GraphOptions( GraphFormatterTest::BASE_PARAMS + [ 'graphfields' => false ] )
		);
		$this->assertEquals( $wrapped, $formatter->getWordWrappedText( $unwrapped, 10 ) );
	}

	/**
	 * @return array
	 */
	public function provideGetGraphLegend(): array {
		$cases = [];
		foreach ( $this->cases as $name => $case ) {
			$cases[$name] = [ $case, $case['legend'] ];
		}
		return $cases;
	}

	/**
	 * @covers       GraphFormatter::getGraphLegend()
	 * @dataProvider provideGetGraphLegend
	 * @param array $params
	 * @param string $expected The expected legend.
	 * @return void
	 */
	public function testGetGraphLegend( array $params, $expected ) {
		$this->assertEquals( $expected, self::graph( $params )->getGraphLegend() );
	}

	/**
	 * @return array
	 */
	public function provideBuildGraph(): array {
		$cases = [];
		foreach ( $this->cases as $name => $case ) {
			$cases[$name] = [ $case, $case['dot'] ];
		}
		return $cases;
	}

	/**
	 * @covers       GraphFormatter::buildGraph()
	 * @dataProvider provideBuildGraph
	 * @param array $params
	 * @param string $expected The expected DOT code.
	 * @return void
	 */
	public function testBuildGraph( array $params, $expected ) {
		$this->assertEquals( $expected, self::graph( $params )->getGraph() );
	}
}
