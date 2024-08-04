<?php
namespace SRF\Tests\Unit\Formats;

use SRF\Graph\GraphPrinter;
use SMW\Query\QueryResult;
use SMW\Query\PrintRequest;

/**
 * @covers \SRF\Graph\GraphPrinter
 * @group semantic-result-formats
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author Sebastian Schmid < sebastian.schmid@gesinn.it >
 * @author Alexander Mashin
 */
class GraphPrinterTest extends \PHPUnit\Framework\TestCase {
	/** @const array [] CASES */
	private const CASES = [];
	/** @const array DATASETS for testing */
	private const DATASETS = [];
	/** @const array PARAMS Options combinations for testing */
	private const PARAMS = [];

	/** @var GraphPrinter $printer Graph result printer for testing */
	private $printer;

	const ROWS = [
		[
			'2:::component|filled|yellow' => [
				[ 'id' => 'Semantic MediaWiki', 'caption' => 'Semantic MediaWiki', 'long' => 'Semantic MediaWiki', 'is file' => false ]
			],
			'1:Abbreviation:Has_abbreviation::' => [
				[ 'id' => 'SMW', 'caption' => 'SMW', 'long' => 'SMW', 'is file' => false ]
			],
			'1:Author:Has_author::signature|filled|lightblue' => [
				[ 'id' => 'Jeroen De Dauw', 'caption' => 'Jeroen De Dauw', 'long' => 'Jeroen De Dauw', 'is file' => false ],
				[ 'id' => 'James Hong Kong', 'caption' => 'James Hong Kong', 'long' => 'James Hong Kong', 'is file' => false ],
				[ 'id' => 'Yaron Koren', 'caption' => 'Yaron Koren', 'long' => 'Yaron Koren', 'is file' => false ],
				[ 'id' => 'Markus Krötzsch', 'caption' => 'Markus Krötzsch', 'long' => 'Markus Krötzsch', 'is file' => false ],
				[ 'id' => 'Denny Vrandečić', 'caption' => 'Denny Vrandečić', 'long' => 'Denny Vrandečić', 'is file' => false ],
				[ 'id' => 'Klaus Lassleben', 'caption' => 'Klaus Lassleben', 'long' => 'Klaus Lassleben', 'is file' => false ],
			],
			'1:Version:Has_version::field' => [
				[ 'id' => '4.1.3', 'caption' => '4.1.3', 'long' => '4.1.3', 'is file' => false ],
			],
			'4::Has author.Attachment link::field|120px' => [
				[ 'id' => 'Файл:Duck.jpg', 'caption' => 'Duck.jpg', 'long' => 'Файл:Duck.jpg', 'is file' => true ],
				[ 'id' => 'Файл:Markus-Kroetzsch-2014.jpg', 'caption' => 'Markus-Kroetzsch-2014.jpg', 'long' => 'Файл:Markus-Kroetzsch-2014.jpg', 'is file' => true ],
			],
			'1:Requires:Requires::' => [],
		], [
			'2:::component|filled|yellow' => [
				[ 'id' => 'Semantic Result Formats', 'caption' => 'Semantic Result Formats', 'long' => 'Semantic Result Formats', 'is file' => false ]
			],
			'1:Abbreviation:Has_abbreviation::' => [
				[ 'id' => 'SRF', 'caption' => 'SRF', 'long' => 'SRF', 'is file' => false ]
			],
			'1:Author:Has_author::signature|filled|lightblue' => [
				[ 'id' => 'Jeroen De Dauw', 'caption' => 'Jeroen De Dauw', 'long' => 'Jeroen De Dauw', 'is file' => false ],
				[ 'id' => 'James Hong Kong', 'caption' => 'James Hong Kong', 'long' => 'James Hong Kong', 'is file' => false ],
				[ 'id' => 'Stephan Gambke', 'caption' => 'Stephan Gambke', 'long' => 'Stephan Gambke', 'is file' => false ],
				[ 'id' => 'Yaron Koren', 'caption' => 'Yaron Koren', 'long' => 'Yaron Koren', 'is file' => false ],
			],
			'1:Version:Has_version::field' => [
				[ 'id' => '4.2.1', 'caption' => '4.2.1', 'long' => '4.2.1', 'is file' => false ],
			],
			'4::Has author.Attachment link::field|120px' => [
				[ 'id' => 'Файл:Duck.jpg', 'caption' => 'Duck.jpg', 'long' => 'Файл:Duck.jpg', 'is file' => true ],
			],
			'1:Requires:Requires::' => [
				[ 'id' => 'Semantic MediaWiki', 'caption' => 'Semantic MediaWiki', 'long' => 'Semantic MediaWiki', 'is file' => false ],
			]
		]
	];

	/*
	 * Initialise the test
	 * @param ?string $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
		$this->printer = new class () extends GraphPrinter {
			// Expose two protected methods
			public function resultText( SMWQueryResult $res, string $outputMode ): string {
				return $this->getResultText( $res, $outputMode );
			}
			public function setParams( array $params ): void {
				$this->handleParameters( $params, null );
			}
		};
	}

	/**
	 * @return array Test cases.
	 */
	public function provideGetResultText(): array {
		$cases = [];
		foreach ( self::CASES as $name => $case ) {
			$cases[$name] = [ self::PARAMS[$case['params']], self::DATASETS[$case['data']], $case['expected'] ];
		}
		return $cases;
	}

	/**
	 * @covers ::GetResultText()
	 * @dataProvider provideGetResultText
	 * @param array $params
	 * @param array $data
	 * @param string $expected
	 * @return void
	 */
	public function testGetResultText( array $params, array $data, string $expected ): void {
		$this->printer->setParams( $params );
		$requests = array_map( static function ( string $request ): PrintRequest {
			return PrintRequest::newFromText( $request );
		}, array_keys( $data ) );
		$query = $this->getMockBuilder( '\SMWQuery' )->disableOriginalConstructor()->getMock();
		$store = $this->getMockBuilder( '\SMW\Store' )->disableOriginalConstructor()->getMockForAbstractClass();
		$result = new QueryResult( $requests, $query, $results, $store );

		$actual = $this->printer->resultText( $result, 0 );
		$this->assertEquals( $expected, $actual );
	}
}
