<?php

class FixMalformedHTMLTest extends WikiaBaseTest {

	/**
	 * Testing if wfFixMalformedHTML does not modify valid HTML snippet
	 * we use at Wikia.
	 *
	 * @param string $testCaseName description of the testcase data
	 * @param string $html valid html snippet
	 * @dataProvider validWikiaHTMLDataProvider
	 */
	function testValidHTMLIsNotChanged($testCaseName, $html) {
		$this->assertEquals( $html, str_replace( "\n", "", wfFixMalformedHTML( $html ) ), $testCaseName );
	}

	function validWikiaHTMLDataProvider() {
		return [
			[
				'simplest text',
				'<p>simplest</p>'
			],
			[
				'paragraph with some formatting',
				'<p><b>some formatting</b> and a <a class="external text" href="http://www.wikia.com">Wikia</a> link</p>'
			],
			[
				'sevaral paragraphs',
				'<p>several</p><p>paragraphs</p><p><br></p><p>test</p>'
			],
			[
				'image markup',
				'<figure class="article-thumb tright show-info-icon" style="width: 216px"><a href="http://static.liz.wikia-dev.com/__cb20140306202704/lizlux/images/9/96/Tiger-Wallpaper-tigers-16120028-1024-768.jpg" class="image image-thumbnail"><img src="http://static.liz.wikia-dev.com/__cb20140306202704/lizlux/images/thumb/9/96/Tiger-Wallpaper-tigers-16120028-1024-768.jpg/216px-Tiger-Wallpaper-tigers-16120028-1024-768.jpg" alt="Tiger-Wallpaper-tigers-16120028-1024-768" class="thumbimage" data-image-key="Tiger-Wallpaper-tigers-16120028-1024-768.jpg" data-image-name="Tiger-Wallpaper-tigers-16120028-1024-768.jpg" width="216" height="162"></a><figcaption><a href="/wiki/File:Tiger-Wallpaper-tigers-16120028-1024-768.jpg" class="sprite info-icon"></a><p class="caption">here is a nice caption</p></figcaption></figure>'
			],
			[
				'video markup',
				'<figure class="article-thumb tright show-info-icon" style="width: 330px"><a href="http://lizlux.liz.wikia-dev.com/wiki/File:Injustice_-_Explaining_Blackest_Night" class="video video-thumbnail medium image lightbox " itemprop="video" itemscope="" itemtype="http://schema.org/VideoObject"><img src="http://static.liz.wikia-dev.com/__cb20130401202327/video151/images/thumb/f/f5/Injustice_-_Explaining_Blackest_Night/330px-Injustice_-_Explaining_Blackest_Night.jpg" data-video-key="Injustice_-_Explaining_Blackest_Night" data-video-name="Injustice - Explaining Blackest Night" width="330" height="185" alt="Injustice - Explaining Blackest Night" itemprop="thumbnail"><span class="duration" itemprop="duration">02:28</span><span class="play-circle"></span><meta itemprop="duration" content="PT02M28S"></a><figcaption><a href="/wiki/File:Injustice_-_Explaining_Blackest_Night" class="sprite info-icon"></a><p class="title">Injustice - Explaining Blackest Night</p></figcaption></figure>'
			],
		];
	}

	/**
	 * Checking if wfFixMalformedHTML can deal with unclosed tags, so we don't break
	 * the whole page inserting an HTML snippet (like an article comment)
	 *
	 * @param string $testCaseName description of the testcase data
	 * @param string $html HTML snippet with some unmatched tags
	 * @param string $expected fixed HTML markup we're expecting to get
	 * @dataProvider malformedHTMLDataProvider
	 */
	function testMalformedHTML($testCaseName, $html, $expected) {
		$this->assertEquals( $expected, str_replace( "\n", "", wfFixMalformedHTML( $html ) ), $testCaseName );

	}

	function malformedHTMLDataProvider() {
		return [
			[
				'html snippet shouldn\'t close tags it did not open',
				'<table><tr><td></td></tr></table></div>',
				'<table><tr><td></td></tr></table>'
			],
			[
				'html snippet should close wrapping tags it opened',
				'<div><table><tr><td></td></tr></table>',
				'<div><table><tr><td></td></tr></table></div>'
			],
			[
				'auto-closing nested tags',
				'<table><tr><td><div>Test</td></tr></table>',
				'<table><tr><td><div>Test</div></td></tr></table>'
			],
			[
				'html5 fugure, figurecaption tags',
				'<figure><img src="http://example.com/1.jpg"><figcaption>Added</figcaption></figure> asdasd',
				'<figure><img src="http://example.com/1.jpg"><figcaption>Added</figcaption></figure> asdasd'
			]
		];
	}

	/**
	 * Checking if wfFixMalformedHTML can deal with Russian Text
	 *
	 * @param string $testCaseName description of the testcase data
	 * @param string $html HTML snippet with some Russian text, or Russian text with unmatched tags
	 * @param string $expected fixed HTML markup we're expecting to get
	 * @dataProvider russianTextDataProvider
	 */
	function testRussianText($testCaseName, $html, $expected) {
		$this->assertEquals( $expected, str_replace( "\n", "", wfFixMalformedHTML( $html ) ), $testCaseName );

	}

	function russianTextDataProvider() {
		return [
			[
				'Russian text should be handled properly (ie, not returned as garbage)',
				'Ед дуо малйж факилиз. Йн лорэм видырэр жкрибэнтур ыюм. Мэль альяквюам пырикюлёз ан, аугюэ аккюсам номинави ед жят. Вэл эи унюм емпэтюсъ инзтруктеор, эи модо конгуы дикырыт дуо',
                '<p>Ед дуо малйж факилиз. Йн лорэм видырэр жкрибэнтур ыюм. Мэль альяквюам пырикюлёз ан, аугюэ аккюсам номинави ед жят. Вэл эи унюм емпэтюсъ инзтруктеор, эи модо конгуы дикырыт дуо</p>'
			],
			[
				'html snippet should close wrapping tags it opened, even with Russian text',
				'<div><table><tr><td>Йн векж дэтракто эррорибуз продыжщэт, дуо ут мёнём дэфянятйоныс. Квюандо луптатум чадипжкёнг но вяш, путынт вэрыар зэнтынтиаэ эю вяш, ючю ат дольорэ апэриам. Экз еюж ельлюд ёудёкабет форынчйбюж, прё тальэ квюандо апэриам эю, ат квуй лаудым аюдирэ жкряпшэрит. Эрюдитя льаборэж ку дуо. Эю пэр путынт фиэрэнт оффекйяж, ыт конжюль омниюм долорюм квуй. Шэа квуым выльёт майыжтатйж эи, ут партйэндо лаборамюз квюо.</td><td>Црял аликвюип дежпютатионй еюж йн, эи видишчы констятюам мэль. Аюдирэ промпта льабятюр вим ад, мовэт вэрыар дыфяниэбаж эа кюм. Нам эюрйпйдяч хонэзтатёз ку, мацим плььатонэм ыкжпэтэндяз экз квуй, прима дэчырюёжжэ мэя йн. Ёудико хомэро экз мэль, ку вяш нонюмэш фачтидёе.</td></tr></table>',
                '<div><table><tr><td>Йн векж дэтракто эррорибуз продыжщэт, дуо ут мёнём дэфянятйоныс. Квюандо луптатум чадипжкёнг но вяш, путынт вэрыар зэнтынтиаэ эю вяш, ючю ат дольорэ апэриам. Экз еюж ельлюд ёудёкабет форынчйбюж, прё тальэ квюандо апэриам эю, ат квуй лаудым аюдирэ жкряпшэрит. Эрюдитя льаборэж ку дуо. Эю пэр путынт фиэрэнт оффекйяж, ыт конжюль омниюм долорюм квуй. Шэа квуым выльёт майыжтатйж эи, ут партйэндо лаборамюз квюо.</td><td>Црял аликвюип дежпютатионй еюж йн, эи видишчы констятюам мэль. Аюдирэ промпта льабятюр вим ад, мовэт вэрыар дыфяниэбаж эа кюм. Нам эюрйпйдяч хонэзтатёз ку, мацим плььатонэм ыкжпэтэндяз экз квуй, прима дэчырюёжжэ мэя йн. Ёудико хомэро экз мэль, ку вяш нонюмэш фачтидёе.</td></tr></table></div>'
			]

		];
	}

}
