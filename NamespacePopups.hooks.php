<?php

// use MediaWiki\Title;

class NamespacePopupsHooks {
	public static function onHtmlPageLinkRendererEnd( $renderer, $target, $isKnown, &$text, &$attribs, &$ret ) {
                global $wgNamespacePopupsNamespaceMap, $wgNamespacePopupsAnchor, $wgArticlePath;

                if(!$wgArticlePath || !$wgNamespacePopupsNamespaceMap) return true;

                // It does not work with $target instanceof TitleValue (as in "search for this page title")
//                 $linkNS = $target->getSubjectNsText();
		global $wgContLang;
		$linkNS = $wgContLang->getNsText( MWNamespace::getSubject( $target->getNamespace() ) );

                if(!$linkNS) $linkNS = ''; // needed?

                $popupNS = isset($wgNamespacePopupsNamespaceMap[$linkNS]) ? $wgNamespacePopupsNamespaceMap[$linkNS] : null;
                if(!$popupNS) $popupNS = isset($wgNamespacePopupsNamespaceMap['*']) ? $wgNamespacePopupsNamespaceMap['*'] : null;
                if(!$popupNS) return true;
                if($popupNS === '*') $popupNS = $linkNS;

		$html = HtmlArmor::getHtml( $text );
		$html = Html::rawElement( 'a', $attribs, $html );
		$remains = $target->getDBkey();
                $anchor = $wgNamespacePopupsAnchor ? $wgNamespacePopupsAnchor : '&uarr;';
                $page = $popupNS === '' ? $remains : "$popupNS:$remains";
//                 $url = wfExpandUrl( $url, $proto = PROTO_RELATIVE ); // $proto
                $url = preg_replace( '/\$1/', $page, $wgArticlePath );

		$title = Title::newFromLinkTarget( Title::newFromText( $page ) );
		if ( $title->isKnown() ) {
			$html .= "<a class='mw-pagepopup' href='$url'>$anchor</a>";
		} else {
			$html .= "<a class='mw-pagepopup new' href='$url'>$anchor</a>";
		}

		$ret = $html;
                return false;
	}
}
