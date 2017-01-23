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
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_TRACKS"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\">

        jQuery(function($) {
            jQuery('#curriculumtracks').dataTable(
                {
                    'sPaginationType': 'full_numbers',
                    'bInfo': false,
                    'bAutoWidth': false,
                    'sAjaxSource': '?org=".$ORGANISATION_ID."&section=api-list',
                    'bServerSide': true,
                    'bProcessing': true,
                    'aoColumns': [
                        { 'mDataProp': 'checkbox', 'bSortable': false },
                        { 'mDataProp': 'curriculum_track_name' }
                    ],
                    'oLanguage': {
                        'sEmptyTable': 'There are currently no currculum tracks in the system.',
                        'sZeroRecords': 'No curriculum track found to display.'
                    }
                }
            );
        });
    </script>";
    ?>
    <h1>Curriculum Tracks</h1>

    <div class="row-fluid">
        <span class="pull-right">
            <a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/curriculumtracks?section=add&amp;org=<?php echo $ORGANISATION_ID; ?>"><i class="icon-plus-sign icon-white"></i> Add Curriculum Track</a>
        </span>
    </div>
    <br />

    <form action="<?php echo ENTRADA_URL."/admin/settings/manage/curriculumtracks?org=".$ORGANISATION_ID; ?>&section=delete" method="POST">
        <table id="curriculumtracks" class="table table-striped">
            <thead>
                <tr>
                    <th width="3%"></th>
                    <th>Curriculum Tracks</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <br />
        <input type="submit" value="Delete Selected" class="btn btn-danger" />
    </form>
<?php

}