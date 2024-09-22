<?php
namespace SRF\Graph;

use ExtensionRegistry;
use Html;
use MediaWiki\MediaWikiServices;
use SMW\Query\PrintRequest;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\ResultPrinter;
use SMWDataItem;
use SMW\Query\QueryResult;
use SMW\Localizer\Localizer;

/**
 * SMW result printer for graphs using graphViz.
 * In order to use this printer you need to have both
 * the graphViz library installed on your system and
 * have the graphViz, Diagrams or ExternalData MediaWiki extension installed.
 *
 * @file SRF_Graph.php
 * @ingroup SemanticResultFormats
 *
 * @license GPL-2.0-or-later
 * @author Frank Dengler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Sebastian Schmid
 */
class GraphPrinter extends ResultPrinter {

	// @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/4273
	// Implement `ResultPrinterDependency` once SMW 3.1 becomes mandatory

	/** @const string TAG The graphViz tag. */
	private const TAG = 'graphviz';

	/** @const string[] PAGETYPES SMW types that represent SMW pages and should always be displayed as nodes. */
	private const PAGETYPES = [ '_wpg', '_wpp', '_wps', '_wpu', '__sup', '__sin', '__suc', '__con' ];

	/** @const string [] A special "color scheme" for this extension (a subset of SVG). */
	private const PALETTE = [
		'black', 'red', 'green', 'blue', 'darkviolet', 'gold', 'deeppink', 'brown', 'bisque', 'darkgreen', 'yellow',
		'darkblue', 'magenta','steelblue2'
	];

	/** @var array[] $allowedAttrs Attributes allowed for the graph, nodes and edges. */
	private static $allowedAttrs = [ 'graph' => [], 'cluster' => [], 'node' => [], 'edge' => [] ];

	/** @const string[] RIGHT_ALIGNED Printout types that should be right-aligned in on-node fields. */
	private const RIGHT_ALIGNED = [ '_num', '_qty', '_dat', '_tem' ];

	/** @var GraphOptions $options Graph options. */
	private $options;

	/**
	 * @var array[] $printouts An array of relevant printout options indexed by printout hashes,
	 * containing their nodes and edges.
	 */
	private $printouts = [];

	/** @var array $map Map property chains to printout request hashes to distribute fields over owner nodes. */
	private $map = [];

	/** @var array[] $rows Intermediate storage of result rows. Needed to make the class testable. */
	private $rows = [];

	/** @var array[] $nodes A common pool of nodes used to merge nodes created by different printouts. */
	private $nodes = [];

	/**
	 * @see SMWResultPrinter::handleParameters()
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );
		$this->options = new GraphOptions( $params );
	}

	/**
	 * @see ResultPrinterDependency::hasMissingDependency
	 *
	 * {@inheritDoc}
	 */
	public function hasMissingDependency(): bool {
		$registry = ExtensionRegistry::getInstance();
		return (
				// <graphviz> can be provided by Diagrams.
				!$registry->isLoaded( 'Diagrams' ) &&
				// or GraphViz.
				!class_exists( 'GraphViz' ) && !class_exists( '\\MediaWiki\\Extension\\GraphViz\\GraphViz' )
			) && !(
				// <graphviz can also be added by External Data in Tag emulation mode.
				$registry->isLoaded( 'External Data' ) &&
				in_array( self::TAG, MediaWikiServices::getInstance()->getParser()->getTags(), true )
			);
	}

	/**
	 * @see ResultPrinterDependency::getDependencyError
	 *
	 * {@inheritDoc}
	 */
	public function getDependencyError() {
		return Html::rawElement(
			'div',
			[ 'class' => 'smw-callout smw-callout-error' ],
			'The SRF Graph printer requires the GraphViz, Diagrams or External Data ' .
			'(with &lt;graphviz&gt; tag defined in Tag emulation mode) extension to be installed.'
		);
	}

	/**
	 * Merge two nodes representing the same page.
	 * @param array $node1
	 * @param array $node2
	 * @return array
	 */
	private static function mergeNodes( array $node1, array $node2 ): array {
		$merged = $node1 + $node2;
		$merged['fields'] = $node1['fields'] ?? [] + $node2['fields'] ?? [];
		return $merged;
	}

	/**
	 * @param QueryResult $res
	 * @param int $outputMode
	 * @return string
	 */
	protected function getResultText( QueryResult $res, $outputMode ): string {
		// Remove this once SRF requires 3.1+.
		if ( $this->hasMissingDependency() ) {
			return $this->getDependencyError();
		}

		// Analyse the printout requests.
		$this->processPrintouts( $res );

		// Get query result as a 2D-array. Its usage will make the class mockable and testable.
		while ( $row = $res->getNext() ) {
			$this->rows[] = self::resultRow( $row );
		}
		// Regroup rows of nodes and edges into printouts (future subgraphs).
		foreach ( $this->rows as $row ) {
			[ $nodes, $edges ] = $this->rowToNodesAndEdges( $row );
			foreach ( $nodes as $hash => $group ) {
				foreach ( $group as $id => $node ) {
					// Guarantee nodes' uniqueness.
					$this->nodes[$id] = self::mergeNodes( $this->nodes[$id] ?? [], $node );
					$this->printouts[$hash]['nodes'][$id] = $this->nodes[$id];
				}
			}
			foreach ( $edges as $hash => $group ) {
				$this->printouts[$hash]['edges'] = array_merge( $this->printouts[$hash]['edges'], $group );
			}
		}

		// Use GraphFormatter to build the graph.
		$graphFormatter = new GraphFormatter( $this->options );
		$graphFormatter->buildGraph( $this->printouts );

		// GraphViz is not working for version >= 1.33, so we need to use the Diagrams or External Data extension
		// and formatting is a little different from the GraphViz extension
		$open = '<' . self::TAG . ' layout="' . $this->options->getLayout() . '">';
		$close = '</' . self::TAG . '>';
		$result = $open . $graphFormatter->getGraph() . $close;
		// Add .dot legend, if required.
		if ( $this->options->isDotLegend() ) {
			$result .= '<br />' . $open . $graphFormatter->getDotLegend( $this->printouts ) . $close;
		}
		// If using Diagrams extension, no further processing.
		global $wgVersion;
		if ( !(
			$outputMode === SMW_OUTPUT_HTML &&
			version_compare( $wgVersion, '1.33', '>=' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'Diagrams' )
		) ) {
			// Calls graphvizParserHook function from MediaWiki GraphViz or External Data extension.
			$result = MediaWikiServices::getInstance()->getParser()->recursiveTagParse( $result );
		}

		// Append HTML legend, if required.
		if ( $this->options->isGraphLegend() && $this->options->isGraphColor() ) {
			$result .= $graphFormatter->getHtmlLegend( $this->printouts );
		}

		return $result;
	}

	/**
	 * Get an array of relevant node or page attributes from a print request.
	 * @param PrintRequest $request The print request.
	 * @param string $prefix 'edge' or 'node'.
	 * @return string[] An associative array of parameters.
	 */
	private function getOverrides( PrintRequest $request, string $prefix ): array {
		$stripState = MediaWikiServices::getInstance()->getParser()->getStripState();
		$attrs = [];
		foreach ( $request->getParameters() as $passed => $value ) {
			if ( $value === false ) {
				continue;
			}
			$attr = preg_replace( "/^{$prefix}[_-]?/", '', $passed );
			if (
				$attr === 'imagewidth' || $attr === 'imageheight' || // non-GraphViz parameter for images.
				in_array( $attr, self::$allowedAttrs[$prefix], true )
			) {
				if ( !$this->options->isGraphColor() && strpos( $attr, 'color' ) !== false ) {
					// Skip all colour-related parameters if colours are not required.
					continue;
				}
				$attrs[$attr] = $stripState->unstripNoWiki( $value );
			}
		}
		return $attrs;
	}

	/**
	 * Convert printout attributes to node/field/edge ones.
	 * @param PrintRequest $request
	 * @return array
	 */
	private function printoutAttributes( PrintRequest $request ): array {
		$canonical = $request->getCanonicalLabel();
		$properties = explode( '.', $canonical );
		$last = array_pop( $properties );
		$prefix = implode( '.', $properties );
		$label = $request->getLabel();
		$labels = $this->options->getNodeLabels();
		$role = $request->getParameter( 'role' );
		$url = $this->options->isGraphLink()
			? '[[' . Localizer::getInstance()->createTextWithNamespacePrefix( SMW_NS_PROPERTY, $last ) . ']]'
			: null;
		$attrs = [
			'main column' => !$request->getData(),
			'type' => $request->getTypeID(),
			'is page' => ( in_array( $request->getTypeID(), self::PAGETYPES, true ) ),
			// This printout shall relabel the main node.
			'label for' => in_array( $label, $labels, true ) || in_array( $canonical, $labels, true ) ? $prefix : null,
			'chain' => $canonical,
			'hash' => str_replace( '|', ':', $request->getHash() ),
			'prefix' => $prefix,
			'node attrs' => $this->getOverrides( $request, 'node' ),
			'edge attrs' => $this->getOverrides( $request, 'edge' ) + [ 'label' => $label, 'URL' => $url ],
			'nodes' => [],
			'edges' => []
		];
		$attrs['chain'] = $attrs['main column'] ? '' : $attrs['chain'];
		$attrs['is node'] = ( $attrs['is page'] && $role !== 'field' )
			|| $role === 'node'
			|| !$this->options->showGraphFields();
		// Get colors from GraphViz colour scheme or SRF palette, if it is required and not set.
		if ( $this->options->isGraphColor() ) {
			static $counter = 0;
			$used = false;
			foreach ( [ 'node', 'edge' ] as $context ) {
				$scheme = $this->options->getColorScheme( $context );
				$color = $scheme === 'SVG' || $scheme === 'X11' ? self::PALETTE[$counter] : $counter + 1;
				foreach ( [ 'color', 'fontcolor' ] as $attr ) {
					if ( !isset( $attrs["$context attrs"][$attr] ) ) {
						$attrs["$context attrs"][$attr] = $color;
						$used = true;
					}
				}
			}
			if ( $used ) {
				$counter = ($counter + 1) % count ( self::PALETTE );
			}
		}
		return $attrs;
	}

	/**
	 * @param QueryResult $res All printouts in SMW query
	 * @return void
	 */
	private function processPrintouts( QueryResult $res ): void {
		$printouts = [];
		$main = '';
		foreach ( $res->getPrintRequests() as $request ) {
			$printout = $this->printoutAttributes( $request );
			$hash = $printout['hash'];
			$printouts[$hash] = $printout;
			$this->map[$printout['chain']] = $hash;
			if ( $printout['main column'] ) {
				$main = $printout['chain'];
			}
		}
		// Find parent properties.
		foreach ( $printouts as &$printout ) {
			if ( isset( $this->map[$printout['prefix']] ) && $this->options->isOblique() ) {
				$printout['parent'] = $printout['prefix'];
			} else {
				$printout['parent'] = $main;
			}
			unset( $printout['prefix'] );
		}
		$this->printouts = $printouts;
	}

	/**
	 * Convert an SMW result row into an array with the bare minimum of data.
	 * @param ResultArray[] $row
	 * @return array
	 */
	private static function resultRow( array $row ): array {
		$result = [];
		// Loop over the printouts.
		foreach ( $row as /* ResultArray */ $values ) {
			$hash = str_replace( '|', ':', $values->getPrintRequest()->getHash() );
			$result[$hash] = [];
			$values->reset();
			while ( ( /* SMWWikiPageValue */ $object = $values->getNextDataValue() ) !== false ) {
				$result[$hash][] = [
					'id' => $object->getShortWikiText(),
					'caption' => $object->getPreferredCaption() ?: $object->getLongWikiText(),
					'long' => $object->getLongWikiText(),
					'is file' => $object->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE
						&& $object->getDataItem()->getTitle()->getNamespace() === NS_FILE
				];
			}
		}
		return $result;
	}

	/**
	 * Convert value to an image node.
	 * @param array $value
	 * @param array $attrs
	 * @return string
	 */
	private static function valueToImage( array $value, array $attrs ): string {
		$width = (int)($attrs['node attrs']['imagewidth'] ?? 0);
		$height = (int)($attrs['node attrs']['imageheight'] ?? 0);
		$options = ($width ? '|width=' . $width : '') . ($height ? '|width=' . $height : '');
		return '[[' . $value['long'] . $options . ']]';
	}

	/**
	 * Convert a row of data to nodes and edges.
	 * @param array[] $row
	 * @return array[] [ $nodes, $edges ]
	 */
	private function rowToNodesAndEdges( array $row ): array {
		$targets = [];
		$nodes = [];
		$edges = [];

		// Nodes first.
		foreach ( $row as $hash => $values ) {
			$attrs = $this->printouts[$hash];
			$nodes[$hash] = [];
			$edges[$hash] = [];
			if ( !$attrs['is node'] ) {
				continue; // we will deal with on-node fields later.
			}
			foreach ( $values as $value ) {
				$node = [ 'label' => $value['caption'], 'fields' => [] ];
				if ( $this->options->isGraphLink() ) {
					$node['URL'] = $value['long'];
				}
				if ( $value['is file'] ) {
					$node['image'] = self::valueToImage( $value, $attrs );
				}
				$nodes[$hash][$value['id']] = $node;
				if ( !$attrs['main column'] ) {
					$targets[] = [ $this->map[$attrs['parent']], $hash, $value['id'] ];
				}
			}
		}

		// Resolve edges' sources.
		foreach ( $targets as [ $source, $hash, $target ] ) {
			$edges[$hash][] = [ array_rand( $nodes[$source] ), $target ];
		}

		// On-node fields.
		foreach ( $row as $hash => $values ) {
			$attrs = $this->printouts[$hash];
			if ( $attrs['is node'] ) {
				continue; // nodes have already been processed.
			}
			if ( count( $values ) === 0 ) {
				continue; // no values.
			}
			$hash = $this->map[$attrs['parent']];
			if ( $attrs['label for'] !== null ) {
				// Use the first value to relabel its parent node.
				foreach ( $nodes[$hash] as &$node ) {
					$node['label'] = $values[0]['caption'];
				}
				continue;
			}
			$field = $attrs['edge attrs'] + [
				'type' => $attrs['type'],
				'align' => in_array( $attrs['type'], self::RIGHT_ALIGNED, true ) ? 'right' : 'left',
				'values' => []
			];
			if ( $this->options->isGraphLink() ) {
				$field['href'] = $attrs['edge attrs']['URL'];
			}
			foreach ( $values as $value ) {
				$field_value = [];
				if ( $attrs['is page'] && $this->options->isGraphLink() ) {
					$field_value['href'] = '[[' . $value['long'] . ']]';
				}
				if ( $value['is file'] ) {
					$field_value['image'] = self::valueToImage( $value, $attrs );
				}
				$field_value['text'] = $value['caption'];
				$field['values'][] = $field_value;
			}
			// Place the field on the appropriate node.
			$id = array_rand( $nodes[$hash] );
			$nodes[$hash][$id]['fields'] = array_merge_recursive(
				$nodes[$hash][$id]['fields'],
				[ $attrs['chain'] => $field ]
			);
		}
		return [ $nodes, $edges ];
	}

	/**
	 * @param $definitions array of IParamDefinition
	 * @return array of IParamDefinition|array
	 * @throws \JsonException
	 * @since 1.8
	 * @see SMWResultPrinter::getParamDefinitions
	 */
	public function getParamDefinitions( array $definitions ):array {
		$params = parent::getParamDefinitions( $definitions );

		$desc = 'srf-paramdesc';

		$addParams = [
			'graphcolor' => [ 'type' => 'boolean', 'default' => false, 'trim' => true ],
			'graphname' => [ 'default' => 'QueryResult', 'trim' => true ],
			'graphlegend' => [ 'type' => 'boolean', 'default' => false, 'trim' => true ],
			'dotlegend' => [ 'type' => 'boolean', 'default' => false, 'trim' => true ],
			'graphlabel' => [ 'type' => 'boolean', 'default' => false, 'trim' => true ],
			'graphlink' => [ 'type' => 'boolean', 'default' => false, 'trim' => true ],
			'relation' => [
				'default' => 'child',
				'message' => "$desc-graph-relation",
				'manipulatedefault' => false,
				'values' => [ 'parent', 'child', 'none' ],
				'trim' => true
			],
			'wordwraplimit' => [
				'type' => 'integer',
				'default' => 25,
				'message' => "$desc-graph-wwl",
				'manipulatedefault' => false,
				'trim' => true
			],
			'labelproperty' => [ 'default' => [], 'islist' => true, 'trim' => true ],
			'graphfields' => [
				'default' => false,
				'manipulatedefault' => false,
				'type' => 'boolean',
				'trim' => true
			],
			'graphoblique' => [
				'default' => false,
				'manipulatedefault' => false,
				'type' => 'boolean',
				'trim' => true
			]
		];

		// Add GraphViz attributes.
		$addParams += GraphOptions::getGraphVizAttributes();

		foreach ( $addParams as $param => &$settings ) {
			$settings['message'] = $settings['message'] ?? "$desc-$param";
			$params[$param] = $settings;
		}

		return $params;
	}

	/**
	 * Add a GraphViz attribute that can be used to customize printouts with '|+param=...' syntax.
	 * @param string $context 'node' or 'edge'.
	 * @param string $attr Parameter name.
	 * @return void
	 */
	public static function addAttribute( string $context, string $attr ): void {
		self::$allowedAttrs[$context][] = $attr;
	}

	/**
	 * Return all attributes allowed for graph, cluster, node or edge.
	 * @return array[]
	 */
	public static function allowedAttributes(): array {
		return self::$allowedAttrs;
	}
}
