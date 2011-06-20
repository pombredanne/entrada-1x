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
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: " . ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create")) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

	echo display_error();

	application_log("error", "Group [" . $_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["group"] . "] and role [" . $_SESSION["permissions"][$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["proxy_id"]]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/configuration/organisations?section=add", "title" => "Add Organisation");


	switch ($STEP) {
		case 2 :
			if (isset($_POST["organisation_title"]) && ($tmp_input = clean_input($_POST["organisation_title"], array("trim", "notags")))) {
				$PROCESSED["organisation_title"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a name for this organisation.";
			}

			if ((isset($_POST["countries_id"])) && ($tmp_input = clean_input($_POST["countries_id"], "int"))) {
				$query = "SELECT * FROM `global_lu_countries` WHERE `countries_id` = " . $db->qstr($tmp_input);
				$result = $db->GetRow($query);
				if ($result) {
					$PROCESSED["countries_id"] = $tmp_input;
					$PROCESSED["organisation_country"] = $result["country"];

					if ((isset($_POST["prov_state"])) && ($tmp_input = clean_input($_POST["prov_state"], array("trim", "notags")))) {
						$PROCESSED["province_id"] = 0;
						$PROCESSED["organisation_province"] = "";
						if (ctype_digit($tmp_input) && ($tmp_input = (int) $tmp_input)) {
							$query = "SELECT * FROM `global_lu_provinces` WHERE `province_id` = " . $db->qstr($tmp_input) . " AND `country_id` = " . $db->qstr($PROCESSED["countries_id"]);
							$result = $db->GetRow($query);
							if ($result) {
								$PROCESSED["province_id"] = $tmp_input;
								$PROCESSED["organisation_province"] = $result["province"];
							} else {
								$ERROR++;
								$ERRORSTR[] = "The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.";
							}
						} else {
							$PROCESSED["organisation_province"] = $tmp_input;
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.";
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "The selected country does not exist in our countries database. Please select a valid country.";
					application_log("error", "Unknown countries_id [" . $tmp_input . "] was selected. Database said: " . $db->ErrorMsg());
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The selected country does not exist in our countries database. Please select a valid country.";
				application_log("error", "Unknown countries_id [" . $tmp_input . "] was selected. Database said: " . $db->ErrorMsg());
			}


			if (isset($_POST["organisation_city"]) && ($tmp_input = clean_input($_POST["organisation_city"], array("trim", "notags")))) {
				$PROCESSED["organisation_city"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a city for this organisation.";
			}

			if (isset($_POST["organisation_postcode"]) && ($tmp_input = clean_input($_POST["organisation_postcode"], array("trim", "notags")))) {
				$PROCESSED["organisation_postcode"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a postal code for this organisation.";
			}

			if (isset($_POST["organisation_address1"]) && ($tmp_input = clean_input($_POST["organisation_address1"], array("trim", "notags")))) {
				$PROCESSED["organisation_address1"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide an address for this organisation.";
			}

			if (isset($_POST["organisation_address2"]) && ($tmp_input = clean_input($_POST["organisation_address2"], array("trim", "notags")))) {
				$PROCESSED["organisation_address2"] = $tmp_input;
			}

			if (isset($_POST["organisation_telephone"]) && ($tmp_input = clean_input($_POST["organisation_telephone"], array("trim", "notags")))) {
				$PROCESSED["organisation_telephone"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a telephone number for this organisation.";
			}

			if (isset($_POST["organisation_fax"]) && ($tmp_input = clean_input($_POST["organisation_fax"], array("trim", "notags")))) {
				$PROCESSED["organisation_fax"] = $tmp_input;
			}

			if (isset($_POST["organisation_email"]) && ($tmp_input = clean_input($_POST["organisation_email"], array("trim", "notags")))) {
				$PROCESSED["organisation_email"] = $tmp_input;
			}

			if (isset($_POST["organisation_url"]) && ($tmp_input = clean_input($_POST["organisation_url"], array("trim", "notags")))) {
				$PROCESSED["organisation_url"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a website for this organisation.";
			}

			if (isset($_POST["organisation_desc"]) && ($tmp_input = clean_input($_POST["organisation_desc"], array("trim", "notags")))) {
				$PROCESSED["organisation_desc"] = $tmp_input;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide a description for this organisation.";
			}

			if (!$ERROR) {
				$PROCESSED["updated_last"] = time();
				$PROCESSED["updated_by"] = $_SESSION["details"]["id"];

				if (($db->AutoExecute(AUTH_DATABASE . ".organisations", $PROCESSED, "INSERT")) && ($organisation_id = $db->Insert_Id())) {
					$SUCCESS++;
					$SUCCESSSTR[] = "You have successfully added <strong>" . html_encode($PROCESSED["organisation_title"]) . "</strong> to the system.<br /><br />You will now be redirected to the organisations index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/configuration/organisations\" style=\"font-weight: bold\">click here</a> to continue.";

					$ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/configuration/organisations/\\'', 5000)";

					application_log("success", "New organisation [" . $organisation_id . "] added to the system.");
				} else {
					$ERROR++;
					$ERRORSTR[] = "We were unable to add this organisation to the system at this time.<br /><br />The system administrator has been notified of this issue, please try again later.";

					application_log("error", "Failed to insert a new organisation into the database. Database said: " . $db->ErrorMsg());
				}
			}



			if ($ERROR) {
				$STEP = 1;
			}

			break;
		case 1 :
		default :
			continue;
	}


	switch ($STEP) {
		case 2 :
			if ($ERROR) {
				echo display_errors();
			}

			if ($NOTICE) {
				echo display_notices();
			}

			if ($SUCCESS) {
				echo display_success();
			}
			break;
		case 1 :
		default :
			$PROCESSED["prov_state"] = ((isset($PROCESSED["province_id"]) && $PROCESSED["province_id"]) ? (int) $PROCESSED["province_id"] : ((isset($PROCESSED["apartment_province"]) && $PROCESSED["apartment_province"]) ? $PROCESSED["apartment_province"] : ""));

			$ONLOAD[] = "provStateFunction(\$F($('addOrganisationForm')['countries_id']))";

			load_rte("advanced");
			?>
			<?php
			if ($ERROR) {
				echo display_error();
			}
			?>




			<script type="text/javascript">


				function provStateFunction(countries_id) {
					var url='<?php echo webservice_url("province"); ?>';
					url = url + '?countries_id=' + countries_id + '&prov_state=<?php echo rawurlencode((isset($_POST["prov_state"]) ? clean_input($_POST["prov_state"], array("notags", "trim")) : $PROCESSED["prov_state"])); ?>';
					new Ajax.Updater($('prov_state_div'), url,
					{
						method:'get',
						onComplete: function () {
							generateAutocomplete();
										
							if ($('prov_state').type == 'select-one') {
								$('prov_state').observe('change', updateAptData);

								$('prov_state_label').removeClassName('form-nrequired');
								$('prov_state_label').addClassName('form-required');
							} else {
								$('prov_state').observe('blur', updateAptData);

								$('prov_state_label').removeClassName('form-required');
								$('prov_state_label').addClassName('form-nrequired');
							}
						}
					});
				}

				function generateAutocomplete() {
					if (updater != null) {
						updater.url = '<?php echo ENTRADA_URL; ?>/api/cities-by-country.api.php?countries_id=' + $F('countries_id');
					} else {
						updater = new Ajax.Autocompleter('city', 'city_auto_complete', '<?php echo ENTRADA_URL; ?>/api/cities-by-country.api.php?countries_id=' + $F('countries_id'), {
							frequency: 0.2,
							minChars: 2,
							afterUpdateElement : getRegionId
						});
					}
				}

				function getRegionId(text, li) {
					if (li.id) {
						$('region_id').setValue(li.id);
					}
				}

				function addPointToMap(lat, lng) {
					if (googleMap && lat != '' && lng != '' && GBrowserIsCompatible()) {
						point = new GLatLng(lat, lng);

						addMarker(point, lat, lng);
					}
				}

				function addAddressToMap(response) {
					if (googleMap && GBrowserIsCompatible()) {
						if (!response || response.Status.code != 200) {
							//						alert("Sorry, we were unable to geocode that address");
						} else {
							place = response.Placemark[0];
							point = new GLatLng(place.Point.coordinates[1], place.Point.coordinates[0]);

							addMarker(point, place.Point.coordinates[1], place.Point.coordinates[0]);
						}
					}
				}

				function addMarker(point, lat, lng) {
					if (googleMap && point && lat && lng) {
						if (!$('mapContainer').visible()) {
							$('mapContainer').show();
						}

						googleMap = new GMap2($('mapData'));
						googleMap.setUIToDefault();
						googleMap.setCenter(point, 15);
						googleMap.clearOverlays();

						var icon = new GIcon();
						icon.image = '<?php echo ENTRADA_URL; ?>/images/icon-apartment.gif';
						icon.shadow = '<?php echo ENTRADA_URL; ?>/images/icon-apartment-shadow.png';
						icon.iconSize = new GSize(25, 34);
						icon.shadowSize = new GSize(35, 34);
						icon.iconAnchor = new GPoint(25, 34);
						icon.infoWindowAnchor = new GPoint(15, 5);

						var marker = new GMarker(point, icon);
						googleMap.addOverlay(marker);

						$('apartment_latitude').setValue(lat);
						$('apartment_longitude').setValue(lng);
					}
				}

				function updateAptData() {
					var address = ($('apartment_address') ? $F('apartment_address') : false);
					var country = ($F('countries_id') ? $('countries_id')[$('countries_id').selectedIndex].text : false);
					var city = ($F('city') ? $F('city') : false);

					if ($('prov_state').type == 'select-one' && ($F('prov_state') > 0)) {
						var province = $('prov_state')[$F('prov_state')].text;
					} else if ($('prov_state').type == 'text' && $F('prov_state') != '') {
						var province = $F('prov_state');
					} else {
						var province = false;
					}

					if (googleMap && address && city && country && GBrowserIsCompatible()) {
						var geocoder = new GClientGeocoder();

						var search = [address, city];
						if (province) {
							search.push(province);
						}
						search.push(country);
									
						searchFor = search.join(', ');

						geocoder.getLocations(searchFor, addAddressToMap);
					}

					if ((address) && ($F('apartment_title') == '')) {
						$('apartment_title').setValue(($F('apartment_number').length > 0 ? $F('apartment_number') + ' - ' : '') + address);
					}

					return false;
				}
			</script>

			<h1>Add Organisation</h1>
			<form id ="addOrganisationForm" action = "<?php echo ENTRADA_URL; ?>/admin/configuration/organisations?section=add&amp;step=2" method = "post">
				<table  cellspacing="0" border="0" cellpadding="2" summary="Add Organisation Form">
					<colgroup>
						<col style="width: 24%" />
						<col style="width: 76%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="2" style="padding-top: 25px;text-align: right;padding-right:45px;">
								<input type="button" class="button" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments'" />
								<input type="submit" class="button" value="Save" />
							</td> 
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td><label for="name_id" class="form-required">Name</label></td>
							<td>
								<input type="text" id="organisation_title" name="organisation_title" value="<?php echo html_encode($PROCESSED["organisation_title"]); ?>" style="width: 250px" />
							</td>
						</tr>

						<tr>
							<td><label for="countries_id" class="form-required">Country</label></td>
							<td>
							<?php
							$countries = fetch_countries();
							if ((is_array($countries)) && (count($countries))) {
								echo "<select id=\"countries_id\" name=\"countries_id\" style=\"width: 256px\" onchange=\"provStateFunction(this.value); updateAptData();\">\n";
								foreach ($countries as $country) {
									echo "<option value=\"" . (int) $country["countries_id"] . "\"" . (($PROCESSED["countries_id"] == $country["countries_id"]) ? " selected=\"selected\"" : (!isset($PROCESSED["countries_id"]) && $country["countries_id"] == DEFAULT_COUNTRY_ID) ? " selected=\"selected\"" : "") . ">" . html_encode($country["country"]) . "</option>\n";
								}
								echo "</select>\n";
							} else {
								echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
								echo "Country information not currently available.\n";
							}
							?>
							</td>
						</tr>
						<tr>
							<td><label for="province_id" class="form-required">Province / State</label></td>
							<td>
								<div id="prov_state_div">Please select a <strong>Country</strong> from above first.</div>
							</td>
						</tr>
						<tr>
							<td><label for="city_id" class="form-required">City</label></td>
							<td>
								<input type="text" id="organisation_city" name="organisation_city" size="100" style="width: 250px; vertical-align: middle" value="<?php echo html_encode($PROCESSED["organisation_city"]); ?>" />
							</td>
						</tr>			
						<tr>
							<td><label for="postal_id" class="form-required">Postal Code</label></td>
							<td>
								<input type="text" id="organisation_postcode" name="organisation_postcode" value="<?php echo html_encode($PROCESSED["organisation_postcode"]); ?>" maxlength="16" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td><label for="address1_id" class="form-required">Address 1</label></td>
							<td>
								<input type="text" id="organisation_address1" name="organisation_address1" value="<?php echo html_encode($PROCESSED["organisation_address1"]); ?>" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td><label for="address2_id">Address 2</label></td>
							<td>
								<input type="text" id="organisation_address2" name="organisation_address2" value="<?php echo html_encode($PROCESSED["organisation_address2"]); ?>" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td><label for="telephone_id" class="form-required">Telephone</label></td>
							<td>
								<input type="text" id="organisation_telephone" name="organisation_telephone" value="<?php echo html_encode($PROCESSED["organisation_telephone"]); ?>" maxlength="32" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td><label for="fax_id">Fax</label></td>
							<td>
								<input type="text" id="organisation_fax" name="organisation_fax" value="<?php echo html_encode($PROCESSED["organisation_fax"]); ?>" maxlength="32" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td><label for="email_id">E-Mail Address</label></td>
							<td>
								<input type="text" id="organisation_email" name="organisation_email" value="<?php echo html_encode($PROCESSED["organisation_email"]); ?>" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td><label for="url_id" class="form-required">Website</label></td>
							<td>
								<input type="text" id="organisation_url" name="organisation_url" value="<?php echo html_encode($PROCESSED["organisation_url"]); ?>" style="width: 250px" />
							</td>
						</tr>
						<tr>
							<td><label for="description_id" class="form-required">Description</label></td>
							<td>
								<input type="text" id="organisation_desc" name="organisation_desc" value="<?php echo html_encode($PROCESSED["organisation_desc"]); ?>" style="width: 250px" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>

		<?php
		break;
	}
}
?>