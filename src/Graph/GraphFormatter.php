<?php
namespace SRF\Graph;

use ExtensionRegistry;
use Html;

/**
 *
 * @see https://www.semantic-mediawiki.org/wiki/Help:Graph_format
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author Sebastian Schmid (gesinn.it)
 *
 */
class GraphFormatter {
	// Modes for serialising attribute arrays.
	/** @const int ATTRS For graph, node or edge properties; in [] */
	private const ATTRS = 0;
	/** @const int HTML For pseudo-HTML tag properties */
	private const HTML = 1;
	/** @const int ROOT For instructions in graph or subgraph roots. */
	private const ROOT = 2;
	/** @const array ATTR_WRAPPERS Which characters to use to wrap attributes in different modes. */
	private const ATTR_WRAPPERS = [
		self::ATTRS => [ 'start' => '[', 'separator' => ', ', 'end' => ']' ],
		self::HTML => [ 'start' => '', 'separator' => ' ', 'end' => '' ],
		self::ROOT => [ 'start' => "\n", 'separator' => "\n", 'end' => "\n" ]
	];

	/** @var string $dot Graph definition in dot language. */
	private $dot = '';

	/** @var GraphOptions $options Graph options. */
	private $options;
	/** @var string $lineSeparator Line separator for line wrapped long text. */
	private $lineSeparator;

	public function __construct( GraphOptions $options ) {
		$this->options = $options;
		// GraphViz is not working for version >= 1.33, so we need to use the Diagrams or External Data
		// and formatting is a little different from the GraphViz extension
		global $wgVersion;
		$this->lineSeparator
			= version_compare( $wgVersion, '1.33', '>=' ) && ExtensionRegistry::getInstance()->isLoaded( 'Diagrams' )
			? '<br />'
			: PHP_EOL;
	}

	/**
	 * @return string
	 */
	public function getGraph(): string {
		return $this->dot;
	}

	/**
	 * Add a single string to graph
	 * @param string $line
	 * @return void
	 */
	private function add( string $line ): void {
		$this->dot .= $line;
	}

	/**
	 * Return a (sub)graph header.
	 * @param string $title
	 * @param array[] $attributes
	 * @param string $prefix 'di', 'sub' or ''
	 * @return string
	 */
	private static function graphHeader( string $title, array $attributes, string $prefix ): string {
		$header = $prefix . 'graph "' . $title . '" {';
		foreach ( $attributes as $class => $attrs ) {
			$serialised = self::serialiseAttrs( $attrs );
			if ( $serialised ) {
				$header .= "\n$class$serialised";
			}
		}
		return "$header\n";
	}

	/*
	* Creates the DOT (graph description language),
	*  which can be processed by the Diagrams, GraphViz or External Data extension
	*
	* @see https://www.graphviz.org/ for documentation about the DOT language
	* @since 3.2
	*
	* @param array[] $printouts
	* @return void
	*/
	public function buildGraph( array $printouts ): void {
		$this->add( self::graphHeader(
			$this->options->getGraphName(),
			$this->options->getAllAttributes(),
			$this->options->getParentRelation() === GraphOptions::DIR_NONE ? '' : 'di'
		) );

		// Add nodes to the graph.
		foreach ( $printouts as $hash => $printout ) {
			if ( count( $printout['nodes'] ) > 0 ) {
				$this->add( self::graphHeader(
					htmlspecialchars( $hash ) . '_nodes',
					[ 'node' => $printout['node attrs'] ],
					'sub'
				) );
				foreach ( $printout['nodes'] as $id => $node ) {
					$this->addNode( $id, $node );
				}
				$this->add( "}\n" );
			}
		}

		// Add edges to the graph.
		foreach ( $printouts as $hash => $printout ) {
			if ( count( $printout['edges'] ) > 0 ) {
				$this->add( self::graphHeader(
					htmlspecialchars( $hash ) . '_edges',
					[ 'edge' => $printout['edge attrs'] ],
					'sub'
				) );
				foreach ( $printout['edges'] as [ $from, $to ] ) {
					$this->addEdge( $from, $to );
				}
				$this->add( "}\n" );
			}
		}

		// Finish the digraph.
		$this->add( "}" );
	}

	/**
	 * Convert an associative array of attributes into .dot syntax.
	 * @param array $attributes An associative array of node or edge attributes, quoted, if necessary.
	 * @param int $mode self::ATTRS, self::HTML or self::ROOT.
	 * @return string Attributes in .dot syntax
	 */
	private static function serialiseAttrs( array $attributes, int $mode = self::ATTRS ): string {
		$attributes = array_filter( $attributes, static function ( $item ): bool {
			return !is_null( $item );
		});
		if ( count( $attributes ) === 0 ) {
			return '';
		}
		$lines = [];
		foreach ( $attributes as $attr => $value ) {
			$quote = preg_match( '/^(".*"|<.*>)$/sD', $value ) ? '' : '"';
			if ( $quote === '"' ) {
				$value = htmlspecialchars( $value );
			}
			$lines[] = "$attr=$quote$value$quote";
		}
		return ' ' . self::ATTR_WRAPPERS[$mode]['start']
			. implode( self::ATTR_WRAPPERS[$mode]['separator'], $lines )
			. self::ATTR_WRAPPERS[$mode]['end'];
	}

	/**
	 * Add node.
	 * @param string $id
	 * @param array $node
	 * @return void
	 */
	private function addNode( string $id, array $node ): void {
		// We need hyperlinks and this node represents a wiki page and there are no fields.
		$url = $this->options->isGraphLink() && strpos( $id, '=' ) === false ? "[[$id]]" : null;
		$label = '';
		if ( isset( $node['label'] ) && !isset( $node['image'] ) ) {
			$label = htmlspecialchars( $node['label'] );
		}
		if ( count( $node['fields'] ?? [] ) === 0 ) {
			// A simple unstructured node.
			$node['URL'] = $url;
			$node['label'] = $this->getWordWrappedText( $label );
		} else {
			// Display fields, if any, with an HTML-like label.
			$node['tooltip'] = $label;
			$label = isset( $node['image'] )
				? '<img src="' . $node['image'] . '" scale="true" />'
				: $this->getWordWrappedText( $label, '<br />' );
			$node['label'] = $this->htmlLikeLabel( $label, $url, $node['fields'] );
		}
		unset( $node['fields'] ); // graphviz will not handle it.

		// Actually add the node.
		$this->add( '"' . htmlspecialchars( $id ) . '"' . self::serialiseAttrs( $node ) . ";\n" );
	}

	/**
	 * Convert a field value to <TD> with coloring and hyperlink.
	 * @param array $value
	 * @param array $attrs
	 * @return string
	 */
	private static function wrapWithTd( array $value, array $attrs ): string {
		if ( isset( $value['image'] ) ) {
			$content = '<img src="' . $value['image'] . '" scale="true" />';
		} else {
			$content = $value['text'];
			if ( $attrs['color'] ?? null ) {
				// Need to duplicate color attribute, for it is ignored in <td>.
				$content = '<font color="' . $attrs['color'] . '">' . $content . '</font>';
			}
		}
		if ( $value['href'] ?? null ) {
			$attrs['href'] = $value['href'];
		}
		return '<td' . self::serialiseAttrs( $attrs, self::HTML ) . '>' . $content . '</td>';
	}

	/**
	 * Show node label and fields in an HTML table.
	 *
	 * @param string $label Node label,
	 * @param ?string $url Node URL,
	 * @param string[] $fields Node fields.
	 * @return string Fields table in HTML-like form
	 */
	private function htmlLikeLabel( string $label, ?string $url, array $fields ): string {
		// Label in HTML form enclosed with <>.
		return "<\n"
			. '<table border="0" cellborder="0" cellspacing="1" columns="*" rows="*">' . "\n"
			. '<tr><td colspan="2"' . ( $url ? ' href="' . htmlspecialchars( $url ) . '"' : '' ) . '>'
			.  $label . "</td></tr><hr/>\n"
			. implode( "\n", array_map( function ( array $field ): string {
				$label = $this->getWordWrappedText( htmlspecialchars( $field['label'] ), '<br />' );
				$values = count( $field['values'] );
				if ( $values === 0 ) {
					return '';
				}
				$valueAttrs = [
					'align' => $field['align'] ?? null,
					'color' => $field['fontcolor'] ?? null,
					'href' => $field['href'] ?? null,
					'colspan' => $label ? null : '2'
				];
				$labelTd = $label ? self::wrapWithTd( [ 'text' => $label ], [
					'align' => 'left',
					'color' => $field['fontcolor'] ?? null,
					'href' => $field['href'] ?? null,
					'rowspan' => $values
				] ) : '';
				$rows = [];
				foreach ( $field['values'] as $value ) {
					$rows[] = "<tr>$labelTd" . self::wrapWithTd( $value, $valueAttrs ) . '</tr>';
					$labelTd = ''; // property label is set only at the first value.
				}
				return implode( "\n", $rows );
			}, $fields ) ) . "\n</table>\n>";
	}

	/**
	 * Choose arrow direction.
	 * @param string $from
	 * @param string $to
	 * @param bool $cardinal Add "e" or "w"
	 * @return string
	 */
	private function arrowDir( string $from, string $to, bool $cardinal = false ): string {
		$e = $cardinal ? ':e' : '';
		$w = $cardinal ? ':w' : '';
		return [
			GraphOptions::DIR_NONE => '"' . $to . '" -- "' . $from . '"',
			GraphOptions::DIR_PARENT => '"' . $to . '"' . $e . '-> "' . $from . '"' . $w,
			GraphOptions::DIR_CHILD => '"' . $from . '"' . $e . ' -> "' . $to . '"' . $w
		][$this->options->getParentRelation()];
	}

	/**
	 * Add an edge to the graph.
	 * @param string $from Edge source.
	 * @param string $to Edge target.
	 * @return void
	 */
	private function addEdge( string $from, string $to ): void {
		$this->add( $this->arrowDir( htmlspecialchars( $from ), htmlspecialchars( $to ) ) . "\n" );
	}

	/**
	 * Add dot legend to graph.
	 * @see https://stackoverflow.com/a/15707752/6632736
	 * @param array[] $printouts Printout definitions.
	 * @return string
	 */
	public function getDotLegend( array $printouts ): string {
		$from = [];
		$to = [];
		$arrows = [];
		foreach ( $printouts as $printout ) {
			if ( count( $printout['edges'] ) === 0 ) {
				continue;
			}
			$attrs = $printout['edge attrs'];
			$label = $attrs['label'];
			$from[] = '<tr><td port="' . $label . '">&nbsp;</td></tr>';
			$to[] = '<tr><td port="' . $label . '">&nbsp;</td></tr>';
			$arrows[] = $this->arrowDir( 'key2":"' . $label, 'key":"' . $label, true )
				. self::serialiseAttrs( $attrs );
		}
		$type = $this->options->getParentRelation() === GraphOptions::DIR_NONE ? 'graph' : 'digraph';
		return $type . " legend {\nrankdir=LR;\n"
			. 'key [label=<<table border="0" cellpadding="2" cellspacing="0" cellborder="0" width="0">'
			. implode( "\n", $from )
			. "\n</table>>, shape=plaintext]\n"
			. 'key2 [label=<<table border="0" cellpadding="2" cellspacing="0" cellborder="0">' . "\n"
			. implode( "\n", $to )
			. "\n</table>>, shape=plaintext]\n"
			. implode( "\n", $arrows )
			. "\n}";
	}

	/**
	 * Creates the graph legend
	 * @param array[] $printouts Printout definitions.
	 * @return string Html::rawElement
	 */
	public function getHtmlLegend( array $printouts ): string {
		$itemsHtml = '';
		foreach ( $printouts as $printout ) {
			if ( count( $printout['edges'] ) === 0 ) {
				continue;
			}
			$attrs = $printout['edge attrs'];
			$label = $attrs['label'];
			if ( isset( $attrs['URL'] ) ) {
				$label = preg_replace( '/(?=]]$)/', "|$label", $attrs['URL'] );
			}
			$inner = HTML::element( 'strong', [], '⟶' ) . ": $label";
			$properties = [ 'class' => 'graphlegenditem' ];
			if ( isset( $attrs['color'] ) ) {
				$properties['style'] = "color: {$attrs['color']}";
			}
			$itemsHtml .= Html::rawElement( 'div', $properties, $inner );
		}
		return Html::rawElement( 'div', [ 'class' => 'graphlegend' ], $itemsHtml );
	}

	/**
	 * Returns the word wrapped version of the provided text.
	 *
	 * @param string $text Text to word-wrap.
	 * @param ?string $br Override line separator.
	 * @return string
	 */
	private function getWordWrappedText( string $text, ?string $br = null ): string {
		$charLimit = $this->options->getWordWrapLimit();
		preg_match_all(
			'/\S{' . $charLimit . ',}|\S.{1,' . ($charLimit - 1) . '}(?=\s+|$)/u',
			$text,
			$matches,
			PREG_PATTERN_ORDER
		);
		return implode( $br ?? $this->lineSeparator, $matches[0] );
	}


}
