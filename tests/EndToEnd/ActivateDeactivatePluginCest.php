<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests Plugin activation and deactivation.
 *
 * @since   1.0.0
 */
class ActivateDeactivatePluginCest
{
	/**
	 * Activate and deactivate the Plugin and confirm a success notification
	 * is displayed with no errors.
	 *
	 * @since   1.0.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginActivationAndDeactivation(EndToEndTester $I)
	{
		$I->activateSocialPostFlowPlugin($I);
		$I->deactivateSocialPostFlowPlugin($I);
	}
}
