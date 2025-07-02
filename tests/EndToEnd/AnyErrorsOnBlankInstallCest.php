<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests for errors on a blank installation
 *
 * @since   1.0.0
 */
class AnyErrorsOnBlankInstallCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.0.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		$I->activateSocialPostFlowPlugin($I);
	}

	/**
	 * Check that no errors are displayed on Pages > Add New, when the Plugin is activated
	 * and not configured.
	 *
	 * @since   1.0.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAddNewPage(EndToEndTester $I)
	{
		// Navigate to Pages > Add New.
		$I->amOnAdminPage('post-new.php?post_type=page');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Check that no errors are displayed on Posts > Add New, when the Plugin is activated
	 * and not configured.
	 *
	 * @since   1.0.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testAddNewPost(EndToEndTester $I)
	{
		// Navigate to Pages > Add New.
		$I->amOnAdminPage('post-new.php');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.0.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateSocialPostFlowPlugin($I);
	}
}
