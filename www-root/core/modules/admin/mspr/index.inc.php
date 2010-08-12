<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Allows administrators to edit users from the entrada_auth.user_data table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MSPR_ADMIN"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"]."] and role [".$_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	require_once("Models/MSPRs.class.php");
	//$msprs = MSPRs::get();
	//var_dump($msprs);
	
	if (isset($_GET['year'])) {
		$year = $_GET['year'];
		if (!is_numeric($year)) {
			unset($year);
		}
	}
	
	
	if (isset($_GET['all']))
		$mode = "all";
	elseif($year) {
		$mode = "year";
	}
	
	switch($mode) {
		case "all" :
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/mspr?all", "title" => "Attention Required" );
			//XXX this could end up quite slow... relies on numerous queries. Will get slower over time. Should try to replace with a single query, at least for getting user ids
			$msprs = MSPRs::getAll();
			
			//no need to create mspr records. Only listing ones requiring attention... records obviously already created.
			?>
			<table id="mspr-class-list" class="tableList">
				<thead>
					<tr>
						<td class="general">
							Student Name
						</td>
						<td class="general">
							Status
						</td>
						<td class="general">
							Submission Deadline
						</td>
						<td class="general">
							Documents
						</td>
						
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach($msprs as $mspr) {
						if ($mspr->isAttentionRequired()) {
							$status = "attention-required";
					?>
					<tr class="<?php echo $status; ?>">
						<td>
							<?php
								$user = $mspr->getUser();
								echo "<a href=\"".ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$user->getID()."\">".$user->getFullname() ."</a>";
							?>
						</td>
						<td>
							<?php echo $mspr->isClosed() ? "closed" : "open"; ?>
						</td>
						<td>
							<?php 
							$cts = $mspr->getClosedTimestamp();
							if ($cts) {
								echo date("Y-m-d @ H:i",$cts); 
							}
							?>
						</td>
						<td>
							None
						</td>
					</tr>
					<?php } 
					} ?>
				</tbody>
			</table>
			<?php
			
			break;
		case "year":
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/mspr?year=".$year, "title" => "Class of ".$year );
			
			$mspr_meta = MSPRClassData::get($year);
			
			add_mspr_admin_sidebar($year);
			
			if (!$mspr_meta) {
				//no class data set up.. create it now
				MSPRClassData::create($year,null);
				$mspr_meta = MSPRClassData::get($year);
			}
			
			$class_close = $mspr_meta->getClosedTimestamp();
			
			if (!$class_close) {
				$opt_notice = "<div class=\"display-notice\">MSPR submission deadline has not been set. It is strongly recommended that you <a href=\"".ENTRADA_URL."/admin/mspr?section=mspr-options&year=".$year."\" >set the deadline</a> in the options now.</div>";	
			}
			
			//cannot assume that the class list hasn't changed.
			
			$query = "INSERT IGNORE into `student_mspr` (`user_id`) select a.id from `".AUTH_DATABASE."`.`user_data` a 
						where a.`grad_year`=".$db->qstr($year)." and 
						a.`id` NOT IN (SELECT b.`user_id` from `student_mspr` b)";

			if(!$db->Execute($query)) {
				$ERROR++;
				$ERRORSTR[] = "Failed to update MSPR Clas List";
				application_log("error", "Unable to update student_mspr records. Database said: ".$db->ErrorMsg());
			}
			
			$msprs = MSPRs::getYear($year);

			?>
			<script>
			function showAll() {
				$("mspr-class-list").addClassName("show-all");
				createCookie("mspr-class-list","show-all",365);
			}

			function hideAttentionNotRequired() {
				$("mspr-class-list").removeClassName("show-all");
				eraseCookie("mspr-class-list");
			}

			document.observe("dom:loaded",function() { if (readCookie("mspr-class-list") == "show-all") showAll() });
			</script>
			
			<h1>Manage MSPRs: Class of <?php echo $year;?></h1>
			<?php echo display_status_messages(); echo $opt_notice;?>
			<div class="instructions">
				
			</div>
			<p><strong>Submission deadline:</strong> <?php echo ($class_close ? date("F j, Y \a\\t g:i a",$class_close) : "Unset"); ?> &nbsp;&nbsp;(<a href="<?php echo ENTRADA_URL; ?>/admin/mspr?section=mspr-options&year=<?php echo $year; ?>">change</a>)</p>
			
			
			<a href="#" onclick='showAll();'>Show All</a> / <a href="#" onclick='hideAttentionNotRequired();'>Show only those requiring attention</a>
			
			<table id="mspr-class-list" class="tableList">
				<col width="40%" />
				<col width="10%" />
				<col width="25%" />
				<col width="25%" />
				<thead>
					<tr>
						<td class="general">
							Student Name
						</td>
						<td class="general">
							Status
						</td>
						<td class="general">
							Submission Deadline
						</td>
						<td class="general">
							Documents
						</td>
						
					</tr>
				</thead>
				<tbody>
					<?php foreach($msprs as $mspr) {
						$status = ($mspr->isAttentionRequired() ? "attention-required" : "attention-not-required");
					?>
					<tr class="<?php echo $status; ?>">
						<td>
							<?php
								$user = $mspr->getUser();
								echo "<a href=\"".ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$user->getID()."\">".$user->getFullname() ."</a>";
							?>
						</td>
						<td>
							<?php echo $mspr->isClosed() ? "closed" : "open"; ?>
						</td>
						<td>
							<?php 
							$cts = $mspr->getClosedTimestamp();
							if ($cts) {
								echo date("Y-m-d @ H:i",$cts); 
							}
							?>
						</td>
						<td>
							None
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php
			
			break;
		default:
			$query		= "select distinct(`grad_year`) from `". AUTH_DATABASE . "`.`user_data` where `grad_year` is not null  
							order by grad_year";
			
			$results	= $db->GetAll($query);
			
			?>
			
			<h1>Manage MSPRs</h1>
			<div class="instructions">
			From the options below, either choose a class to manage and press "Go" to view options specific to the selected class; or, click "Manage All MSPRs requiring attention" to view those awaiting staff approval.
			</div><br />
			<div style="display:inline-block; width:35%; margin:1em 0 1em 1em; background-color:WhiteSmoke; padding:2ex 2em;vertical-align:top;height:16ex;">
				<form method="get">
					Choose Class to manage: 
					<br /><br/>
					<div style="margin-left:2em;">
						<select name="year">
						<?php 
						
						//because we ned the current school year, we have to rig it a bit. 
						$cur_year = (int) date("Y");
						if (date("n") > 8) $cur_year += 1;
						foreach ($results as $result) {
							$year = $result['grad_year'];
							echo build_option($year, $year, $year == $cur_year);	
						}
						?>
						</select><br/><br/>
						<input type="submit"  value="Go"></input>
					</div>
				</form>
			</div><div style="display:inline-block;width:2em;line-height:16ex;vertical-align:middle; margin:1em;">OR</div><div style="display:inline-block; width:35%; margin:1em 1em 1em 0; background-color:WhiteSmoke;padding:2ex 2em;vertical-align:top;height:16ex;">
			<a href="?all">Manage All MSPRs requiring attention</a></div>
		<?php
	}
}