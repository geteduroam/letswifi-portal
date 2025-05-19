<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy 802.1x device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Whitespace\IndentRule;
use TwigCsFixer\Ruleset\Ruleset;

return ( new Config() )
	->setRuleset( ( new Ruleset() )
		->addStandard( new TwigCsFixer\Standard\TwigCsFixer() )
		->overrideRule( new IndentRule( useTab: true ) ),
	)
	->setFinder( Finder::create()
		->in( 'template/' ),
	)
;
