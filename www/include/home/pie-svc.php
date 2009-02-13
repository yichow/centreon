<?php
/*
 * Centreon is developped with GPL Licence 2.0 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Developped by : Julien Mathis - Romain Le Merlus 
 * 
 * The Software is provided to you AS IS and WITH ALL FAULTS.
 * Centreon makes no representation and gives no warranty whatsoever,
 * whether express or implied, and without limitation, with regard to the quality,
 * any particular or intended purpose of the Software found on the Centreon web site.
 * In no event will Centreon be liable for any direct, indirect, punitive, special,
 * incidental or consequential damages however they may arise and even if Centreon has
 * been previously advised of the possibility of such damages.
 * 
 * For information : contact@centreon.com
 */
 
	require_once ("../../class/Session.class.php");
	require_once ("../../class/Oreon.class.php");
	
	Session::start();
	$oreon =& $_SESSION["oreon"];
	
	include_once "@CENTREON_ETC@/centreon.conf.php";	
	include_once $centreon_path . "www/class/centreonDB.class.php";
		
	$pearDB = new CentreonDB();
	
	include_once $centreon_path . "www/include/common/common-Func.php";

	$ndo_base_prefix = getNDOPrefix();
			
	$pearDBndo = new CentreonDB("ndo");

	/*
	 *  calcul stat for resume
	 */
	$statistic = array(0 => "OK", 1 => "WARNING", 2 => "CRITICAL", 3 => "UNKNOWN", 4 => "PENDING");
	
    /*
	 * LCA
	 */
	$sid = $_GET['sid'];
	$res1 =& $pearDB->query("SELECT user_id FROM session WHERE session_id = '".$sid."'");
	$user =& $res1->fetchRow();
	$user_id = $user["user_id"];

	global $is_admin;
	
	$is_admin =  $oreon->user->admin;
	$grouplistStr = $oreon->user->access->getAccessGroupsString();
			
	/* 
	 * Get Service NDO status 
	 */
	if (!$is_admin)
		$rq2 = 	" SELECT count(nss.current_state), nss.current_state" .
				" FROM ".$ndo_base_prefix."servicestatus nss, ".$ndo_base_prefix."objects no, centreon_acl " .
				" WHERE no.object_id = nss.service_object_id".				
				" AND no.name1 NOT LIKE '_Module_%' ".
				" AND no.name1 = centreon_acl.host_name ".
				" AND no.name2 = centreon_acl.service_description " .
				" AND centreon_acl.group_id IN (".$grouplistStr.") ".
				" AND no.is_active = 1 GROUP BY nss.current_state ORDER by nss.current_state";
	else
		$rq2 = 	" SELECT count(nss.current_state), nss.current_state" .
				" FROM ".$ndo_base_prefix."servicestatus nss, ".$ndo_base_prefix."objects no" .
				" WHERE no.object_id = nss.service_object_id".
				" AND no.name1 NOT LIKE '_Module_%' ".
				" AND no.is_active = 1 GROUP BY nss.current_state ORDER by nss.current_state";			
	$DBRESULT_NDO2 =& $pearDBndo->query($rq2);
	if (PEAR::isError($DBRESULT_NDO2))
		print "DB Error : ".$DBRESULT_NDO2->getDebugInfo()."<br />";

	$svc_stat = array(0=>0, 1=>0, 2=>0, 3=>0, 4=>0);
	$counter = 0;
	while ($ndo =& $DBRESULT_NDO2->fetchRow()){
		$data[] = $ndo["count(nss.current_state)"];
		$legend[] = $statistic[$ndo["current_state"]];
		$color[] = $oreon->optGen["color_".strtolower($statistic[$ndo["current_state"]])];
		$counter += $ndo["count(nss.current_state)"];
	}
	$DBRESULT_NDO2->free();
	
	/*
	 *  create the dataset
	 */
	
	foreach ($data as $key => $value)
		$data[$key] = round($value / $counter * 100, 2);
	
	include_once($centreon_path.'/www/lib/ofc-library/open-flash-chart.php' );
	$g = new graph();
	$g->bg_colour = '#F3F6F6';
	//
	// PIE chart, 60% alpha
	//
	$g->pie(60,'#505050','#000000');
	//
	// pass in two arrays, one of data, the other data labels
	//
	
	$g->pie_values($data, $legend);
	//
	// Colours for each slice, in this case some of the colours
	// will be re-used (3 colurs for 5 slices means the last two
	// slices will have colours colour[0] and colour[1]):
	//
	$g->set_tool_tip( '#key# : #val# %' );
	
	$g->pie_slice_colours($color);

	$g->set_tool_tip( '#val#%' );
	$g->title( _('Services'), '{font-size:18px; color: #424242}' );
	echo $g->render();
?>