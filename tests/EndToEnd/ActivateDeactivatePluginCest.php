<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests Plugin activation and deactivation.
 *
 * @since   3.8.4
 */
class ActivateDeactivatePluginCest
{
	/**
	 * Activate and deactivate the Plugin and confirm a success notification
	 * is displayed with no errors.
	 *
	 * @since   3.8.4
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginActivationAndDeactivation(EndToEndTester $I)
	{
		$I->activateSocialPostFlowPlugin($I);
		$I->deactivateSocialPostFlowPlugin($I);
	}
}
