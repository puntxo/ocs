<?php
/**
 * @file classes/security/authorization/OcsPluginAccessPolicy.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OcsPluginAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to OCS's plugins.
 */

import('lib.pkp.classes.security.authorization.PolicySet');
import('classes.security.authorization.internal.PluginLevelRequiredPolicy');
import('lib.pkp.classes.security.authorization.internal.PluginRequiredPolicy');

define('ACCESS_MODE_MANAGE', 0x01);
define('ACCESS_MODE_ADMIN', 0x02);

class OcsPluginAccessPolicy extends PolicySet {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request arguments
	 * @param $roleAssignments array
	 * @param $accessMode int
	 */
	function OcsPluginAccessPolicy(&$request, &$args, $roleAssignments, $accessMode = ACCESS_MODE_ADMIN) {
		parent::PolicySet();

		// A valid plugin is required.
		$this->addPolicy(new PluginRequiredPolicy($request));

		// Press managers and site admin have
		// access to plugins. We'll have to define
		// differentiated policies for those roles in a policy set.
		$pluginAccessPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		$pluginAccessPolicy->setEffectIfNoPolicyApplies(AUTHORIZATION_DENY);

		//
		// Managerial role
		//
		if (isset($roleAssignments[ROLE_ID_CONFERENCE_MANAGER])) {
			if ($accessMode & ACCESS_MODE_MANAGE) {
				// Press managers have edit settings access mode...
				$conferenceManagerPluginAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
				$conferenceManagerPluginAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_CONFERENCE_MANAGER, $roleAssignments[ROLE_ID_CONFERENCE_MANAGER]));

				// ...only to conference level plugins.
				$conferenceManagerPluginAccessPolicy->addPolicy(new PluginLevelRequiredPolicy($request, CONTEXT_CONFERENCE));

				$pluginAccessPolicy->addPolicy($conferenceManagerPluginAccessPolicy);
			}
		}

		//
		// Site administrator role
		//
		if (isset($roleAssignments[ROLE_ID_SITE_ADMIN])) {
			// Site admin have access to all plugins...
			$siteAdminPluginAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$siteAdminPluginAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SITE_ADMIN, $roleAssignments[ROLE_ID_SITE_ADMIN]));

			if ($accessMode & ACCESS_MODE_MANAGE) {
				// ...of site level only.
				$siteAdminPluginAccessPolicy->addPolicy(new PluginLevelRequiredPolicy($request, CONTEXT_SITE));
			}

			$pluginAccessPolicy->addPolicy($siteAdminPluginAccessPolicy);
		}

		$this->addPolicy($pluginAccessPolicy);
	}
}

?>
