<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Class to do some things with a CSV.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@quensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

ini_set('auto_detect_line_endings', true);

class CsvImporter {
	
	private $errors, $success, $draft_id, $updater, $valid_rows, $last_parent;
	
	function __construct($draft_id, $proxy_id) {
		$this->draft_id = $draft_id;
		$this->updater = $proxy_id;
	}
	
	/**
	 * Returns the errors
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}
	
	/**
	 * Returns the successfully imported row numbers
	 * @return array
	 */
	public function getSuccess() {
		return $this->success;
	}
	
	private function validateRow($row = array()) {

		global $db;

		if (!is_array($row)) {
			return false;
		}

		$output = array();
		$skip_row = false;

		/*
		* 0		Event ID
		* 1		Parent Event ID
		* 2		Term
		* 3		Course Code
		* 4		Course Name
		* 5		Date
		* 6		Start Time
		* 7		Event Type Durations
		* 8		Total Duration
		* 9		Event Types
		* 10	Event Title
		* 11	Location
		* 12	Audience (Cohorts)
		* 13	Audience (Groups)
		* 14	Audience (Student numbers)
		* 15	Teacher Numbers
		* 16	Teacher Names
		*/

		$event_id				= ((isset($row[0]) ? clean_input($row[0], "int") : 0 ));
		$parent_event			= ((isset($row[1]) ? clean_input($row[1], "int") : 0 ));
		$term					= ((isset($row[2]) ? clean_input($row[2], array("trim","striptags")) : 0 ));
		$course_code			= ((isset($row[3]) ? clean_input($row[3], array("trim","striptags")) : 0 ));
		$course_name			= ((isset($row[4]) ? clean_input($row[4], array("trim","striptags")) : 0 ));
		$date					= ((isset($row[5]) ? clean_input($row[5], array("trim","striptags")) : 0 ));
		$start_time				= ((isset($row[6]) ? clean_input($row[6], array("trim","striptags")) : 0 ));
		$total_duration			= ((isset($row[7]) ? clean_input($row[7], "int") : 0 ));
		$eventtype_durations	= ((isset($row[8]) ? explode(";", $row[8]) : 0 ));
		$eventtypes				= ((isset($row[9]) ? explode(";", $row[9]) : 0 ));
		$event_title			= ((isset($row[10]) ? clean_input($row[10], array("trim","striptags")) : 0 ));
		$event_location			= ((isset($row[11]) ? clean_input($row[11], array("trim","striptags")) : 0 ));
		$event_audiences_cohort = ((isset($row[12]) && !empty($row[12]) ? explode(";", $row[12]) : 0 ));
		$event_audiences_groups = ((isset($row[13]) && !empty($row[13]) ? explode(";", $row[13]) : 0 ));
		$event_audiences_students = ((isset($row[14]) && !empty($row[14]) ? explode(";", clean_input($row[14], array("nows", "striptags"))) : 0 ));
		$event_teachers			= ((isset($row[15]) && !empty($row[15]) ? explode(";", clean_input($row[15], array("nows", "striptags"))) : 0 ));
		$teacher_names			= ((isset($row[16]) && !empty($row[16]) ? explode(";", $row[16]) : 0 ));
		$event_duration			= 0;

		// check draft for existing event_id and get the devent_id if found
		if ($event_id != 0) {
			
			$query = "	SELECT `devent_id`
						FROM `draft_events`
						WHERE `event_id` = ".$db->qstr($event_id);
			if ($result = $db->GetRow($query)) {
				$output[$event_id]["devent_id"] = $result["devent_id"];	
			}
		}
		
		// set the output event_id
		$output[$event_id]["event_id"] = $event_id;
		
		// check the parent_id column
		if ($parent_event == 1) {
			$output[$event_id]["parent_event"] = 0;
			$this->last_parent = $event_id;
		} else if ($parent_event == 0) {
			$output[$event_id]["parent_event"] = $this->last_parent;
		} else {
			$err["errors"][] = "Parent ID field must be 1 or 0.";
			$skip_row = true;
		}
		
		// term - not required
		if ($term != 0) {
			$output[$event_id]["term"] = $event_id;
		}
		
		// verify the course code
		$query = "	SELECT `course_id`, `course_name`
					FROM `courses`
					WHERE `course_code` = ".$db->qstr($course_code);
		if ($result = $db->getRow($query)) {
			$output[$event_id]["course_id"]	  = $result["course_id"];
		} else {
			// a course code wasn't found check against the course name
			$query = "	SELECT `course_id`, `course_code` 
						FROM `courses`
						WHERE LCASE(`course_name`) = ".$db->qstr(strtolower($course_name));
			if ($result = $db->getRow($query)) {
				$output[$event_id]["course_id"]	  = $result["course_id"]; 
			} else {
				// required information missed - no course code and no match on course name
				$err["errors"][] = "Course code missing, course name not found.";
				$skip_row = true;
			}
		}
		
		// validate required date and time
		if ($event_start = strtotime($date." ".$start_time)) {
			$output[$event_id]["event_start"] = $event_start;
		} else {
			$err["errors"][] = "Event start time could not be validated.";
			$skip_row = true;
		}
		
		// number of eventtype durations must match number of eventtypes
		if (count($eventtype_durations) == count($eventtypes)) {
			$i = 0;
			foreach ($eventtype_durations as $duration) {
				$query = "	SELECT `eventtype_id`
							FROM `events_lu_eventtypes`
							WHERE LCASE(`eventtype_title`) = ".$db->qstr(strtolower(clean_input($eventtypes[$i], array("striptags", "trim"))));
				$results = $db->GetRow($query);
				if ($results) {
					$output[$event_id]["eventtypes"][$i]["type"] = $results["eventtype_id"];
					$output[$event_id]["eventtypes"][$i]["duration"] = $duration;
					$output[$event_id]["total_duration"] += $duration;
				} else {
					$err["errors"][] = "Could not find event type: ".$eventtype[$i];
					$skip_row = true;
				}
				$i++;
			}
		} else {
			$err["errors"][] = "Number of event types did not match required durations.";
			$skip_row = true;
		}
		
		// required event title
		if (!empty($event_title)) {
			$output[$event_id]["event_title"] = $event_title;
		} else {
			$err["errors"][] = "Required event title not set.";
			$skip_row = true;
		}
		
		// event location, not required
		if ($event_location !== 0) {
			$output[$event_id]["event_location"] = $event_location;
		}
		
		// event audience, not required	but needs to be verified
		if (!empty($event_audiences_cohort)) {
			foreach ($event_audiences_cohort as $i => $cohort) {
				$event_audiences_cohort[$i] = $db->qstr(strtolower(clean_input($cohort, array("trim", "striptags"))));
			}
			$query = "	SELECT `group_id`, `group_name`
						FROM `groups`
						WHERE LCASE(`group_name`) IN (".implode(", ", $event_audiences_cohort).")
						GROUP BY `group_name`";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$event_id]["audiences"]["cohorts"][] = $result["group_id"];
				}
			}
		}
		
		if (!empty($event_audiences_groups)) {
			foreach ($event_audiences_groups as $i => $group) {
				$event_audiences_groups[$i] = $db->qstr(strtolower(clean_input($group, array("trim", "striptags"))));
			}
			
			$query = "	SELECT `cgroup_id`, `course_id`, `group_name`
						FROM `course_groups`
						WHERE LCASE(`group_name`) IN (".implode(", ", $event_audiences_groups).")
						GROUP BY `group_name`";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$event_id]["audiences"]["groups"][] = $result["cgroup_id"];
				}
			} else {
				$err["errors"][] = "Event audience group not found.";
				$skip_row = true;
			}
		}
		
		if (!empty($event_audiences_students)) {
			foreach ($event_audiences_students as $i => $student) {
				$event_audiences_students[$i] = $db->qstr((int) $student);
			}
			$query = "	SELECT `id`
						FROM `".AUTH_DATABASE."`.`user_data` 
						WHERE `number` IN ('".implode(", ", $event_audiences_students)."')";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$event_id]["audiences"]["students"][] = $result["id"];
				}
			}
		}
		
		if (!empty($event_teachers)) {
			foreach ($event_teachers as $teacher) {
				$event_teachers[$teacher] = $db->qstr((int) $teacher);
			}
			$query = "	SELECT `id`
						FROM `".AUTH_DATABASE."`.`user_data` 
						WHERE `number` IN ('".implode(", ", $event_teachers)."')";
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					$output[$event_id]["teachers"][] = $result["id"];
				}
			}
		}
		
		if (!$skip_row) {
			return $output;
		} else {
			return $err;
		}
		
	}
	
	private function importRow($valid_row) {
		
		global $db;
		
		foreach ($valid_row as $row) {
			
			if (isset($row["devent_id"])) {
				$mode = "UPDATE";
				$where = "WHERE `devent_id` = ".$db->qstr($row["devent_id"]);
			} else {
				$mode = "INSERT INTO";
				$where = "";
			}
			
			$query =	$mode." `draft_events` (`draft_id`, `event_id`, `parent_id`, `course_id`, `event_title`, `event_start`, `event_finish`, `event_duration`, `event_location`) 
						VALUES (".$this->draft_id.", ".$db->qstr($row["event_id"]).", ".$db->qstr($row["parent_event"]).", ".$db->qstr($row["course_id"]).", ".$db->qstr($row["event_title"]).", ".$db->qstr($row["event_start"]).", ".$db->qstr($row["event_start"] + ($row["total_duration"] * 60)).", ".$db->qstr($row["total_duration"]).", ".$db->qstr($row["event_location"]).")".
						$where;
			$result = $db->Execute($query);
			
			$devent_id = (isset($row["devent_id"]) ? $row["devent_id"] : $db->Insert_ID()."\n");
			
			foreach ($row["eventtypes"] as $eventtype) {
				$query =	$mode." `draft_eventtypes` (`devent_id`, `event_id`, `eventtype_id`, `duration`) 
							VALUES (".$db->qstr($devent_id).", ".$db->qstr($row["event_id"]).", ".$db->qstr($eventtype["type"]).", ".$db->qstr($eventtype["duration"]).")".
							$where;
				$result = $db->Execute($query);
			}
			if (isset($row["audiences"]["cohorts"])) {
				foreach ($row["audiences"]["cohorts"] as $cohort) {
					$query =	$mode." `draft_audience` (`devent_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
								VALUES (".$db->qstr($devent_id).", 'cohort', ".$db->qstr($cohort).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")".
								$where;
					$result = $db->Execute($query);
				}
			}
			if (isset($row["audiences"]["groups"])) {
				foreach ($row["audiences"]["groups"] as $group) {
					$query =	$mode." `draft_audience` (`devent_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
								VALUES (".$db->qstr($devent_id).", 'group_id', ".$db->qstr($group).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")".
								$where;
					$result = $db->Execute($query);
				}
			}
			if (isset($row["audiences"]["students"])) {
				foreach ($row["audiences"]["students"] as $student) {
					$query =	$mode." `draft_audience` (`devent_id`, `audience_type`, `audience_value`, `updated_date`, `updated_by`)
								VALUES (".$db->qstr($devent_id).", 'proxy_id', ".$db->qstr($student).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")".
								$where;
					$result = $db->Execute($query);
				}
			}
			if (isset($row["teachers"])) {
				$i = 0;
				foreach ($row["teachers"] as $teacher) {
					$query =	$mode." `draft_contacts` (`devent_id`, `proxy_id`, `contact_role`, `contact_order`, `updated_date`, `updated_by`)
								VALUES (".$db->qstr($devent_id).", ".$db->qstr($teacher).", 'teacher', ".$db->qstr($i).", ".$db->qstr(time()).", ".$db->qstr($this->updater).")".
								$where;
					$result = $db->Execute($query);
					$i++;
				}
			}
		}
	}
	
	public function importCsv($file) {
		
		$handle = fopen($file["tmp_name"], "r");
		if ($handle) {
			$row_count = 0;
			while (($row = fgetcsv($handle)) !== false) {
				if ($row_count >= 1) {
					$results = $this->validateRow($row);
					if (isset($results["errors"])) {
						$this->errors[$row_count] = $results["errors"];
					} else {
						$this->valid_rows[] = $results;
					}
				}
				$row_count++;
			}
			if (count($this->errors) <= 0) {
				foreach ($this->valid_rows as $valid_row) {
					$this->importRow($valid_row);
					$this->success[] = $row_count;
				}
			}
		}
		fclose($handle);
		
		return $row_count;
	}
	
}

?>
