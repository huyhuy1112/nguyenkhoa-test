{strip}
<div class="listViewPageDiv activities-page activities-detail-wrapper">
    <div class="contentsDiv">
        <div class="detailViewContainer">
            <div class="panel panel-default activities-card">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h4 class="panel-title">Activity #{$RECORD.activityid}</h4>
                    </div>
                    <div class="pull-right">
                        <a href="index.php?module=Activities&view=Edit&record={$RECORD.activityid}&app=SUPPORT" class="btn btn-default btn-sm">Edit</a>
                        {if $RECORD.ticketid|default:'' neq ''}
                            <a href="index.php?module=HelpDesk&view=TicketDetail&record={$RECORD.ticketid}&app=SUPPORT" class="btn btn-primary btn-sm">Back to Ticket</a>
                        {else}
                            <a href="index.php?module=Activities&view=List&app=SUPPORT" class="btn btn-primary btn-sm">Back to List</a>
                        {/if}
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr><th style="width:220px;">Type</th><td>{$RECORD.activity_type|default:'-'|escape}</td></tr>
                        <tr><th>Content</th><td>{$RECORD.content|default:'-'|escape|nl2br}</td></tr>
                        <tr><th>Organization</th><td>{$RECORD.org_name|default:'-'|escape}</td></tr>
                        <tr><th>Project</th><td>{$RECORD.project_name|default:'-'|escape}</td></tr>
                        <tr><th>Ticket ID</th><td>{$RECORD.ticketid|default:'-'|escape}</td></tr>
                        <tr><th>Assigned To</th><td>{$RECORD.first_name|escape} {$RECORD.last_name|escape}</td></tr>
                        <tr><th>Date</th><td>{$RECORD.activity_date|default:'-'|escape}</td></tr>
                        <tr><th>Status</th><td><span class="activities-status activities-status-{$RECORD.status|lower|replace:' ':'_'|escape}">{$RECORD.status|default:'-'|escape}</span></td></tr>
                        <tr><th>Note Before</th><td>{$RECORD.note_before|default:'-'|escape|nl2br}</td></tr>
                        <tr><th>Note After</th><td>{$RECORD.note_after|default:'-'|escape|nl2br}</td></tr>
                        <tr><th>Created</th><td>{$RECORD.createdtime|default:'-'|escape}</td></tr>
                        <tr><th>Modified</th><td>{$RECORD.modifiedtime|default:'-'|escape}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
{/strip}
