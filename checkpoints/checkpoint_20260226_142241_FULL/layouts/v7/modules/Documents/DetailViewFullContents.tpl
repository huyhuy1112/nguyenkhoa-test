{* Documents: nội dung detail + block Lịch sử *}
{strip}
<form id="detailView" method="POST">
    {include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}
</form>
{if isset($DOCUMENT_HISTORY) && $DOCUMENT_HISTORY|@count > 0}
<div class="block block-document-history marginTop15px">
    <div class="blockHeader">
        <strong>Lịch sử</strong>
        <span class="pull-right">Chỉnh sửa / di chuyển / xóa / upload / download</span>
    </div>
    <div class="blockContents table-responsive">
        <table class="table table-bordered listViewEntriesTable">
            <thead>
                <tr>
                    <th>Thao tác</th>
                    <th>Người thực hiện</th>
                    <th>Thời gian</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                {foreach item=row from=$DOCUMENT_HISTORY}
                <tr>
                    <td>{$row.action_label}</td>
                    <td>{$row.first_name} {$row.last_name} ({$row.user_name})</td>
                    <td>{$row.created_at}</td>
                    <td>{$row.extra}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
{/if}
{/strip}
