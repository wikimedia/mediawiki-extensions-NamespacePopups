<?php

use MediaWiki\MediaWikiServices;

class NamespacePopupsHooks {

	/**
	 * @param \MediaWiki\Linker\LinkRenderer $renderer
	 * @param \MediaWiki\Linker\LinkTarget $target
	 * @param bool $isKnown
	 * @param string|HtmlArmor &$text
	 * @param array &$attribs
	 * @param string &$ret HTML output
	 *
	 * @return bool
	 */
	public static function onHtmlPageLinkRendererEnd( $renderer, $target, $isKnown, &$text,
		&$attribs, &$ret
	) {
		global $wgNamespacePopupsNamespaceMap, $wgNamespacePopupsAnchor;

		if ( !$wgNamespacePopupsNamespaceMap ) {
			return true;
		}

		// It does not work with $target instanceof TitleValue (as in "search for this page title")
		// $linkNS = $target->getSubjectNsText();
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		$linkNS = $contLang->getNsText( MWNamespace::getSubject( $target->getNamespace() ) );

		if ( !$linkNS ) {
			// TODO: Is this really needed?
			$linkNS = '';
		}

		$popupNS = isset( $wgNamespacePopupsNamespaceMap[$linkNS] )
			? $wgNamespacePopupsNamespaceMap[$linkNS] : null;
		if ( !$popupNS ) {
			$popupNS = isset( $wgNamespacePopupsNamespaceMap['*'] )
				? $wgNamespacePopupsNamespaceMap['*'] : null;
		}

		if ( !$popupNS ) {
			return true;
		}

		if ( $popupNS === '*' ) {
			$popupNS = $linkNS;
		}

		$remains = $target->getDBkey();
		$anchor = $wgNamespacePopupsAnchor ? $wgNamespacePopupsAnchor : '&uarr;';
		$page = $popupNS === '' ? $remains : "$popupNS:$remains";
		$title = Title::newFromText( $page );

		if ( !$title ) {
			return true;
		}

		$url = $title->getLocalUrl();

		$html = HtmlArmor::getHtml( $text );
		$html = Html::rawElement( 'a', $attribs, $html );

		if ( $title->isKnown() ) {
			$html .= Html::rawElement( 'a', [ 'class' => 'mw-pagepopup', 'href' => $url ], $anchor );
		} else {
			$query = [];
			$query['action'] = 'edit';
			$query['redlink'] = '1';
			$edit_url = $title->getLinkURL( $query );
			$html .= Html::rawElement( 'a',
				[ 'class' => 'mw-pagepopup new', 'href' => $edit_url ], $anchor );
		}

		$ret = $html;

		return false;
	}

	/**
	 * @param Parser $parser
	 * @param string &$text
	 */
	public static function onParserAfterTidy( $parser, &$text ) {
		global $wgNamespacePopupsNamespaceMap;

		if ( !$wgNamespacePopupsNamespaceMap ) {
			return;
		}

		$parserOutput = $parser->getOutput();

		$oldLinks = [];
		// The below algorithm enumerates all links. This may be a little inefficient
		foreach ( $parserOutput->getLinks() as $linkNSid => $linksArray ) {
			$linkNS = MWNamespace::getCanonicalName( $linkNSid );
			foreach ( array_keys( $linksArray ) as $remains ) {
				$oldLinks[] = [ $linkNS, $remains ];
			}
		}

		foreach ( $oldLinks as $linkInfo ) {
			list( $linkNS, $remains ) = $linkInfo;

			if ( isset( $wgNamespacePopupsNamespaceMap[$linkNS] ) ) {
				$popupNS = $wgNamespacePopupsNamespaceMap[$linkNS];
			} elseif ( isset( $wgNamespacePopupsNamespaceMap['*'] ) ) {
				$popupNS = $wgNamespacePopupsNamespaceMap['*'];
			} else {
				$popupNS = null;
			}
			if ( !$popupNS ) {
				continue;
			}
			if ( $popupNS === '*' ) {
				$popupNS = $linkNS;
			}
			$popupPage = $popupNS === '' ? $remains : "$popupNS:$remains";

			$parserOutput->addLink( Title::newFromDBkey( $popupPage ) );
		}
	}
}
