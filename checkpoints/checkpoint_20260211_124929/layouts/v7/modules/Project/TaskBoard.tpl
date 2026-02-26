{* Task Board view for Project *}
{strip}
<div class="project-task-board">
    <div class="board-toolbar">
        <div class="btn-group">
            <a class="btn btn-default btn-sm board-add-main" href="{$CREATE_TASK_URL}">
                <i class="fa fa-plus"></i> {vtranslate('LBL_ADD', $MODULE)}
            </a>
        </div>
        <div class="board-tabs">
            <button class="btn btn-default btn-sm active"><i class="fa fa-th-large"></i> Board</button>
            <button class="btn btn-default btn-sm"><i class="fa fa-table"></i> Table</button>
        </div>
    </div>

    <div class="board-columns">
        {foreach from=$TASK_COLUMNS key=STATUS_LABEL item=TASKS}
            <div class="board-column" data-status="{$STATUS_LABEL}">
                <div class="board-column-header">
                    <span class="status-name">{$STATUS_LABEL}</span>
                    <span class="status-actions">
                        <span class="status-count">{php7_count($TASKS)}</span>
                        <a class="board-add-task"
                           href="{$CREATE_TASK_URL}&projecttaskstatus={$STATUS_MAP[$STATUS_LABEL]|escape:'url'}">
                            <i class="fa fa-plus"></i>
                        </a>
                    </span>
                </div>
                <div class="board-cards" data-status="{$STATUS_LABEL}" data-status-value="{$STATUS_MAP[$STATUS_LABEL]}">
                    {foreach from=$TASKS item=TASK}
                        <div class="board-card" draggable="true" data-task='{Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($TASK))}'>
                            <div class="card-title">{$TASK.name}</div>
                            <div class="card-meta">
                                <span class="meta-item"><i class="fa fa-calendar"></i> Due date {if $TASK.enddate}{$TASK.enddate}{else}--{/if}</span>
                                <span class="meta-item"><i class="fa fa-user"></i> Assignees {$TASK.owner_name|default:'--'} <i class="fa fa-plus card-add-assignee" title="Add assignee"></i></span>
                            </div>
                            <div class="card-footer">
                                <span class="card-post-time">
                                    {if $TASK.createdtime_display}{$TASK.createdtime_display}{/if}
                                    {if $TASK.comment_count > 0}<i class="fa fa-comment-o card-comment-icon"></i> {$TASK.comment_count}{/if}
                                </span>
                                <span class="card-progress-value">{if $TASK.progress}{$TASK.progress}{else}0%{/if}</span>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/foreach}
    </div>

    <div class="task-detail-modal hidden">
        <div class="task-detail-dialog">
            <div class="task-detail-header">
                <div class="header-left">
                    <span class="status-pill detail-status">--</span>
                    <span class="detail-id"></span>
                </div>
                <div class="header-right">
                    <button class="tab-btn active">Comments</button>
                    <button class="tab-btn">Task history</button>
                    <span class="panel-close">&times;</span>
                </div>
            </div>
            <div class="task-detail-content">
                <div class="task-detail-left">
                    <div class="detail-back-wrap hide"><a href="javascript:void(0)" class="detail-back-link">← Back to <span class="detail-back-parent-name"></span></a></div>
                    <div class="detail-breadcrumb">{$PROJECT_NAME|default:''} › {vtranslate('LBL_TASKS', 'Project')}</div>
                    <div class="detail-title"></div>
                    <div class="detail-section">
                        <div class="section-label">Description</div>
                        <textarea class="form-control detail-description" rows="3" placeholder="Write description..."></textarea>
                    </div>
                    <div class="detail-table">
                        <div class="detail-row">
                            <span class="label">Start/Due</span>
                            <span class="value detail-dates">
                                <input type="date" class="detail-start" />
                                <span class="date-sep">→</span>
                                <input type="date" class="detail-end" />
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Labels</span>
                            <span class="value"><input type="text" class="detail-labels" placeholder="Select" /></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Assignees</span>
                            <span class="value">
                                <select class="detail-owner-select">
                                    {foreach from=$TASK_USERS key=USER_ID item=USER_NAME}
                                        <option value="{$USER_ID}">{$USER_NAME|escape:'html'}</option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Time</span>
                            <span class="value"><input type="text" class="detail-time" placeholder="Add logged time / Add estimated time" /></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Progress</span>
                            <span class="value">
                                <input type="number" min="0" max="100" class="detail-progress" />
                                <span class="progress-suffix">%</span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Status</span>
                            <span class="value">
                                <select class="detail-status-select">
                                    {foreach from=$TASK_STATUS item=STATUS}
                                        <option value="{$STATUS|escape:'html'}">{$STATUS|escape:'html'}</option>
                                    {/foreach}
                                </select>
                            </span>
                        </div>
                        <div class="detail-row">
                            <a class="detail-link" href="javascript:void(0)">Add field</a>
                            <span>or</span>
                            <a class="detail-link" href="javascript:void(0)">Manage fields</a>
                        </div>
                    </div>
                    <div class="detail-subtasks board-subtasks-block">
                        <div class="section-label">Subtasks</div>
                        <div class="tasksListToolbar">
                            <input type="text" class="form-control board-subtask-title-input quickAddTaskInput" placeholder="Add task and hit enter/return key" />
                            <button type="button" class="btn btn-primary btn-sm board-subtask-save-btn">{vtranslate('LBL_SAVE', 'ProjectTask')}</button>
                        </div>
                        <div class="task-list-container">
                            <div class="task-list-empty text-muted">{vtranslate('LBL_NO_SUBTASKS', 'ProjectTask')}</div>
                            <ul class="task-list list-unstyled"></ul>
                        </div>
                    </div>
                </div>
                <div class="task-detail-right">
                    <div class="ann-detail-tabs">
                        <button type="button" class="ann-tab task-detail-tab active" data-tab="comments">Comments <span class="badge task-comments-badge">0</span></button>
                        <button type="button" class="ann-tab task-detail-tab" data-tab="history">Task history</button>
                    </div>
                    <div id="task-panel-comments" class="ann-detail-panel task-detail-panel">
                        <ul class="ann-comments-list list-unstyled task-comments-list"></ul>
                        <div class="ann-add-comment">
                            <div class="task-comment-toolbar">
                                <button type="button" class="btn btn-default btn-xs task-comment-emoji-btn" title="Emoji">&#128512;</button>
                                <button type="button" class="btn btn-default btn-xs task-comment-upload-btn" title="Upload from computer"><span class="fa fa-paperclip"></span> Upload</button>
                                <input type="file" class="task-comment-file-input" accept="*" style="display:none" />
                                <span class="task-comment-file-name text-muted small hidden"></span>
                            </div>
                            <div class="task-comment-emoji-picker hidden"></div>
                            <textarea class="form-control task-comment-input" rows="2" placeholder="Write a comment"></textarea>
                            <button type="button" class="btn btn-primary btn-sm task-comment-add">Add</button>
                        </div>
                    </div>
                    <div id="task-panel-history" class="ann-detail-panel task-detail-panel hide">
                        <ul class="task-history-list list-unstyled"></ul>
                        <div class="task-history-empty text-muted small">No history yet.</div>
                    </div>
                </div>
            </div>
            <div class="task-detail-footer">
                <button class="btn btn-primary detail-save">Save</button>
                <button class="btn btn-default detail-cancel">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var board = document.querySelector('.project-task-board');
    if (!board) return;
    var panel = board.querySelector('.task-detail-modal');
    var closeBtn = board.querySelector('.panel-close');
    var titleEl = board.querySelector('.detail-title');
    var descEl = board.querySelector('.detail-description');
    var startEl = board.querySelector('.detail-start');
    var endEl = board.querySelector('.detail-end');
    var ownerSelect = board.querySelector('.detail-owner-select');
    var progressEl = board.querySelector('.detail-progress');
    var statusEl = board.querySelector('.detail-status');
    var idEl = board.querySelector('.detail-id');
    var statusSelect = board.querySelector('.detail-status-select');
    var saveBtn = board.querySelector('.detail-save');
    var commentList = board.querySelector('.task-comments-list');
    var commentInput = board.querySelector('.task-comment-input');
    var commentAddBtn = board.querySelector('.task-comment-add');
    var commentsBadge = board.querySelector('.task-comments-badge');
    var commentEmojiBtn = board.querySelector('.task-comment-emoji-btn');
    var commentUploadBtn = board.querySelector('.task-comment-upload-btn');
    var commentFileInput = board.querySelector('.task-comment-file-input');
    var commentFileName = board.querySelector('.task-comment-file-name');
    var commentEmojiPicker = board.querySelector('.task-comment-emoji-picker');
    var subtaskList = board.querySelector('.board-subtasks-block .task-list');
    var subtaskEmpty = board.querySelector('.board-subtasks-block .task-list-empty');
    var subtaskTitleInput = board.querySelector('.board-subtask-title-input');
    var subtaskSaveBtn = board.querySelector('.board-subtask-save-btn');
    var currentTask = null;
    var commentPendingFile = null;
    var parentStack = [];
    var lastCommentsRequestId = 0;
    var backWrap = board.querySelector('.detail-back-wrap');
    var backParentName = board.querySelector('.detail-back-parent-name');

    (function initCommentImageLightbox() {
        if (document.getElementById('ann-comment-lightbox')) return;
        var lb = document.createElement('div');
        lb.id = 'ann-comment-lightbox';
        lb.className = 'ann-comment-lightbox';
        lb.innerHTML = '<div class="ann-comment-lightbox-backdrop"></div>' +
            '<div class="ann-comment-lightbox-content">' +
            '<button type="button" class="ann-comment-lightbox-close" aria-label="Close">&times;</button>' +
            '<img class="ann-comment-lightbox-img" alt="" />' +
            '<a href="#" class="ann-comment-lightbox-download" target="_blank" download><i class="fa fa-download"></i> Download</a>' +
            '</div>';
        document.body.appendChild(lb);
        var lbImg = lb.querySelector('.ann-comment-lightbox-img');
        var lbDownload = lb.querySelector('.ann-comment-lightbox-download');
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.ann-comment-attachment a');
            if (!link) return;
            var img = link.querySelector('img.ann-comment-img');
            if (!img || !img.src) return;
            e.preventDefault();
            lbImg.src = img.src;
            lbDownload.href = link.getAttribute('href') || img.src;
            lbDownload.download = '';
            lb.style.display = 'block';
        });
        lb.querySelector('.ann-comment-lightbox-close').addEventListener('click', function () { lb.style.display = 'none'; });
        lb.querySelector('.ann-comment-lightbox-backdrop').addEventListener('click', function () { lb.style.display = 'none'; });
    })();

    function getTaskPanelBaseUrl() {
        return (window.location.pathname || '') + (window.location.search || '');
    }
    function updateTaskPanelUrl(type, id) {
        var base = getTaskPanelBaseUrl();
        var hash = type && id ? (type === 'subtask' ? '#subtask=' + id : '#task=' + id) : '';
        history.replaceState(null, '', base + hash);
    }

    function fillTaskDetailPanel(task) {
        if (!task) return;
        currentTask = task;
        if (titleEl) titleEl.textContent = task.name || '';
        if (descEl) descEl.value = task.description || '';
        if (startEl) startEl.value = task.startdate || '';
        if (endEl) endEl.value = task.enddate || '';
        if (ownerSelect && task.smownerid) ownerSelect.value = task.smownerid;
        if (progressEl) progressEl.value = (task.progress != null ? task.progress : (task.projecttaskprogress != null ? task.projecttaskprogress : '0')).toString().replace(/%/g, '');
        if (statusEl) statusEl.textContent = task.projecttaskstatus || '--';
        if (statusSelect) statusSelect.value = task.projecttaskstatus || '';
        if (idEl) idEl.textContent = task.recordid ? ('#' + task.recordid) : '';
        if (backWrap) backWrap.classList.toggle('hide', parentStack.length === 0);
        if (backParentName && parentStack.length > 0) backParentName.textContent = parentStack[parentStack.length - 1].name || 'Task';
        if (commentInput) commentInput.value = '';
        if (commentFileInput) commentFileInput.value = '';
        if (commentFileName) { commentFileName.textContent = ''; commentFileName.classList.add('hidden'); }
        if (commentEmojiPicker) commentEmojiPicker.classList.add('hidden');
    }

    var EMOJI_LIST = ['\uD83D\uDE00','\uD83D\uDE0A','\uD83D\uDC4D','\u2764','\uD83D\uDD25','\u2705','\uD83D\uDCCE','\uD83D\uDE0D','\uD83D\uDE02','\uD83D\uDC4F','\uD83D\uDC4C','\uD83D\uDE4C','\u263A','\uD83D\uDE0E','\uD83D\uDE80','\u2B50'];
    function ensureEmojiPickerContent() {
        if (!commentEmojiPicker || commentEmojiPicker.dataset.filled) return;
        commentEmojiPicker.innerHTML = EMOJI_LIST.map(function(em) {
            return '<span class="task-emoji-item" data-emoji="' + em + '">' + em + '</span>';
        }).join('');
        commentEmojiPicker.dataset.filled = 'true';
        commentEmojiPicker.addEventListener('click', function(e) {
            var item = e.target.closest('.task-emoji-item');
            if (!item || !commentInput) return;
            var em = item.getAttribute('data-emoji');
            var start = commentInput.selectionStart, end = commentInput.selectionEnd;
            var val = commentInput.value || '';
            commentInput.value = val.slice(0, start) + em + val.slice(end);
            commentInput.selectionStart = commentInput.selectionEnd = start + em.length;
            commentInput.focus();
        });
    }

    function loadBoardSubtasks(taskId) {
        if (!taskId || typeof app === 'undefined' || !app.request) return;
        app.request.post({ data: { module: 'ProjectTask', action: 'GetSubtasks', record: taskId } }).then(function (err, data) {
            if (err) return;
            var requestedId = taskId;
            var res = (data && data.result) ? data.result : (data || {});
            var subtasks = res.subtasks || [];
            if (currentTask && currentTask.recordid === requestedId) {
                renderBoardSubtasks(subtasks);
            }
        });
    }

    var SUBTASK_STATUS_OPTIONS = [
        { value: 'Open', label: 'Backlog', icon: 'backlog' },
        { value: 'In Progress', label: 'In progress', icon: 'inprogress' },
        { value: 'Completed', label: 'Complete', icon: 'complete' }
    ];
    function getStatusIconClass(status) {
        if (!status || status === 'Completed') return 'complete';
        if (status === 'In Progress') return 'inprogress';
        return 'backlog';
    }
    function renderBoardSubtasks(subtasks) {
        if (!subtaskList) return;
        subtaskList.innerHTML = '';
        if (!subtasks || subtasks.length === 0) {
            if (subtaskEmpty) subtaskEmpty.style.display = 'block';
            return;
        }
        if (subtaskEmpty) subtaskEmpty.style.display = 'none';
        subtasks.forEach(function (st) {
            var completed = st.completed === true || st.completed === '1';
            var duration = st.duration || st.projecttaskhours || '';
            var owner = st.owner_name || '';
            var statusIcon = getStatusIconClass(st.projecttaskstatus);
            var statusHtml = '<span class="subtask-status-wrap"><button type="button" class="subtask-status-trigger" data-recordid="' + (st.recordid || '').toString().replace(/</g, '&lt;') + '" aria-expanded="false"><span class="subtask-status-icon ' + statusIcon + '"></span></button>';
            statusHtml += '<div class="subtask-status-dropdown hidden">';
            SUBTASK_STATUS_OPTIONS.forEach(function (opt) {
                var sel = (st.projecttaskstatus === opt.value) ? ' subtask-status-option-selected' : '';
                statusHtml += '<div class="subtask-status-option' + sel + '" data-value="' + (opt.value || '').replace(/"/g, '&quot;') + '"><span class="subtask-status-icon ' + opt.icon + '"></span><span class="subtask-status-label">' + (opt.label || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></div>';
            });
            statusHtml += '</div></span>';
            var item = document.createElement('li');
            item.className = 'task-list-row';
            item.setAttribute('data-recordid', st.recordid);
            item.innerHTML = '<span class="task-check-wrap"><input type="checkbox" class="task-checkbox" ' + (completed ? 'checked' : '') + ' /></span>' +
                statusHtml +
                '<span class="task-title' + (completed ? ' task-done' : '') + '">' + (st.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>' +
                (duration ? '<span class="task-duration">' + duration + '</span>' : '') +
                '<span class="task-assignee-wrap"><span class="task-assignee">' + (owner ? owner.substring(0, 2).toUpperCase() : '') + '</span></span>';
            subtaskList.appendChild(item);
        });
    }

    function addBoardSubtask() {
        if (!currentTask || !subtaskTitleInput || typeof app === 'undefined' || !app.request) return;
        var title = (subtaskTitleInput.value || '').trim();
        if (!title) return;
        if (subtaskSaveBtn) subtaskSaveBtn.disabled = true;
        app.request.post({
            data: {
                module: 'ProjectTask',
                action: 'SaveSubtask',
                parent_record: currentTask.recordid,
                projecttaskname: title,
                description: ''
            }
        }).then(function (err, data) {
            if (subtaskSaveBtn) subtaskSaveBtn.disabled = false;
            if (err) return;
            subtaskTitleInput.value = '';
            loadBoardSubtasks(currentTask.recordid);
            if (app.helper && app.helper.showSuccessNotification) {
                app.helper.showSuccessNotification({ message: (app.vtranslate && app.vtranslate('JS_RECORD_CREATED')) || 'Created' });
            }
        });
    }

    function openSubtaskInOverlay(recordId) {
        if (!recordId || typeof app === 'undefined' || !app.request) return;
        if (currentTask) parentStack.push(currentTask);
        app.request.post({ data: { module: 'ProjectTask', action: 'GetTaskDetail', record: recordId } }).then(function (err, data) {
            if (err) return;
            var res = (data && data.result) ? data.result : (data || {});
            var task = res.task;
            if (!task) return;
            fillTaskDetailPanel(task);
            panel.classList.remove('hidden');
            loadTaskComments(task.recordid);
            loadBoardSubtasks(task.recordid);
            switchTaskDetailTab('comments');
            updateTaskPanelUrl('subtask', recordId);
        });
    }

    if (backWrap) {
        backWrap.addEventListener('click', function (e) {
            if (!e.target.closest('.detail-back-link') || parentStack.length === 0) return;
            e.preventDefault();
            var parentTask = parentStack.pop();
            fillTaskDetailPanel(parentTask);
            loadTaskComments(parentTask.recordid);
            loadBoardSubtasks(parentTask.recordid);
            updateTaskPanelUrl('task', parentTask.recordid);
        });
    }

    function appendOneComment(c, optBlobUrl) {
        if (!commentList) return;
        var name = c.userName || '';
        var initial = name ? name.charAt(0).toUpperCase() : '?';
        var text = (c.comment_text || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var time = c.time || '';
        var attHtml = '';
        if (optBlobUrl) {
            attHtml = '<div class="ann-comment-attachment"><img src="' + optBlobUrl.replace(/"/g, '&quot;') + '" alt="" class="ann-comment-img" /></div>';
        } else {
            (c.attachments || []).forEach(function (a) {
                var ext = (a.name || '').split('.').pop().toLowerCase();
                var isImg = /^(jpg|jpeg|png|gif|webp|bmp|tiff|tif|svg|ico|heic|heif)$/.test(ext);
                var safeName = (a.name || 'file').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                var url = a.url || '#';
                var imgUrl = isImg ? url.replace('action=DownloadFile', 'action=InlineFile') : url;
                if (isImg) {
                    attHtml += '<div class="ann-comment-attachment"><a href="' + url + '" target="_blank"><img src="' + imgUrl + '" alt="" class="ann-comment-img" /></a></div>';
                } else {
                    attHtml += '<div class="ann-comment-attachment"><a href="' + url + '" target="_blank" class="ann-comment-file-link">' + safeName + '</a></div>';
                }
            });
        }
        var commentId = c.id || '';
        var deleteBtn = commentId ? '<button type="button" class="ann-comment-delete" data-comment-id="' + String(commentId).replace(/"/g, '&quot;') + '" title="Delete">×</button>' : '';
        var item = document.createElement('li');
        item.className = 'ann-comment-item';
        item.innerHTML = '<span class="ann-avatar ann-avatar-user ann-avatar-sm">' + initial + '</span>' +
            '<span class="ann-comment-meta">' + name + ' ' + time + '</span>' +
            (deleteBtn ? '<span class="ann-comment-meta-actions">' + deleteBtn + '</span>' : '') +
            '<div class="ann-comment-text">' + text + '</div>' +
            (attHtml ? '<div class="ann-comment-attachments">' + attHtml + '</div>' : '');
        commentList.appendChild(item);
    }

    var commentDeleteBound = false;
    function bindCommentDelete() {
        if (!commentList || commentDeleteBound) return;
        commentDeleteBound = true;
        commentList.addEventListener('click', function (e) {
            var btn = e.target.closest('.ann-comment-delete');
            if (!btn || !currentTask) return;
            var id = btn.getAttribute('data-comment-id');
            if (!id) return;
            if (!confirm('Xóa comment này?')) return;
            if (typeof app === 'undefined' || !app.request) return;
            app.request.post({
                data: { module: 'ModComments', action: 'Delete', record: id, ajaxDelete: 1 }
            }).then(function (err) {
                if (err) return;
                loadTaskComments(currentTask.recordid);
            });
        });
    }

    function renderTaskComments(comments) {
        if (!commentList) return;
        commentList.innerHTML = '';
        (comments || []).forEach(function (c) { appendOneComment(c); });
        if (commentsBadge) commentsBadge.textContent = (comments || []).length;
        if (!commentDeleteBound) bindCommentDelete();
    }

    function loadTaskComments(taskId, forceRefresh) {
        if (!taskId || typeof app === 'undefined' || !app.request) return;
        var data = { module: 'ProjectTask', action: 'GetComments', record: taskId };
        if (forceRefresh) data._t = Date.now();
        app.request.post({ data: data }).then(function (err, data) {
            if (err) return;
            var res = (data && data.result) ? data.result : (data || {});
            renderTaskComments(res.comments || []);
        });
    }

    board.addEventListener('click', function (e) {
        var card = e.target.closest('.board-card');
        if (!card) return;
        var data = card.getAttribute('data-task');
        if (!data) return;
        var task = JSON.parse(data);
        parentStack = [];
        fillTaskDetailPanel(task);
        panel.classList.remove('hidden');
        loadTaskComments(task.recordid);
        loadBoardSubtasks(task.recordid);
        switchTaskDetailTab('comments');
        updateTaskPanelUrl('task', task.recordid);
    });

    function switchTaskDetailTab(tab) {
        var commentsPanel = board.querySelector('#task-panel-comments');
        var historyPanel = board.querySelector('#task-panel-history');
        var tabs = board.querySelectorAll('.task-detail-tab');
        tabs.forEach(function (t) {
            t.classList.toggle('active', t.getAttribute('data-tab') === tab);
        });
        if (commentsPanel) commentsPanel.classList.toggle('hide', tab !== 'comments');
        if (historyPanel) historyPanel.classList.toggle('hide', tab !== 'history');
        if (tab === 'history' && currentTask && currentTask.recordid) {
            loadTaskHistory(currentTask.recordid);
        }
    }

    function loadTaskHistory(taskId) {
        var historyList = board.querySelector('.task-history-list');
        var historyEmpty = board.querySelector('.task-history-empty');
        if (!historyList || typeof app === 'undefined' || !app.request) return;
        app.request.post({ data: { module: 'ProjectTask', action: 'GetHistory', record: taskId } }).then(function (err, data) {
            if (err) return;
            var res = (data && data.result) ? data.result : (data || {});
            var history = res.history || [];
            historyList.innerHTML = '';
            if (historyEmpty) historyEmpty.style.display = history.length ? 'none' : 'block';
            history.forEach(function (h) {
                var name = h.userName || '';
                var initial = name ? name.charAt(0).toUpperCase() : '?';
                var time = h.time || '';
                var action = h.action || 'Updated';
                var changesHtml = '';
                (h.changes || []).forEach(function (c) {
                    changesHtml += '<div class="history-change">' + (c.field || '') + ': ' + (c.pre || '-') + ' → ' + (c.post || '-') + '</div>';
                });
                var item = document.createElement('li');
                item.className = 'ann-comment-item task-history-item';
                item.innerHTML = '<span class="ann-avatar ann-avatar-user ann-avatar-sm">' + initial + '</span>' +
                    '<span class="ann-comment-meta">' + name + ' · ' + action + ' · ' + time + '</span>' +
                    '<div class="ann-comment-text">' + changesHtml + '</div>';
                historyList.appendChild(item);
            });
        });
    }

    board.querySelectorAll('.task-detail-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var t = this.getAttribute('data-tab');
            if (t) switchTaskDetailTab(t);
        });
    });

    if (subtaskTitleInput) {
        subtaskTitleInput.addEventListener('keydown', function (e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                addBoardSubtask();
            }
        });
    }
    if (subtaskSaveBtn) {
        subtaskSaveBtn.addEventListener('click', function () { addBoardSubtask(); });
    }
    if (subtaskList) {
        subtaskList.addEventListener('click', function (e) {
            var item = e.target.closest('.task-list-row');
            if (!item) return;
            var recordId = item.getAttribute('data-recordid');
            if (e.target.closest('.task-checkbox')) {
                var checked = e.target.checked;
                if (recordId && typeof app !== 'undefined' && app.request) {
                    var status = checked ? 'Completed' : 'Open';
                    var progress = checked ? '100%' : '0%';
                    app.request.post({ data: { module: 'ProjectTask', action: 'SaveAjax', record: recordId, field: 'projecttaskstatus', value: status } }).then(function (err) {
                        if (err) return;
                        app.request.post({ data: { module: 'ProjectTask', action: 'SaveAjax', record: recordId, field: 'projecttaskprogress', value: progress } }).then(function () {
                            item.querySelector('.task-title').classList.toggle('task-done', checked);
                            var icon = item.querySelector('.subtask-status-trigger .subtask-status-icon');
                            if (icon) { icon.className = 'subtask-status-icon ' + getStatusIconClass(status); }
                        });
                    });
                }
            } else if (e.target.closest('.subtask-status-wrap')) {
                e.preventDefault();
                e.stopPropagation();
                var trigger = e.target.closest('.subtask-status-trigger');
                var wrap = e.target.closest('.subtask-status-wrap');
                var dropdown = wrap ? wrap.querySelector('.subtask-status-dropdown') : null;
                var option = e.target.closest('.subtask-status-option');
                if (option && dropdown && !dropdown.classList.contains('hidden')) {
                    var newStatus = option.getAttribute('data-value');
                    var rid = wrap.querySelector('.subtask-status-trigger').getAttribute('data-recordid');
                    if (rid && newStatus && typeof app !== 'undefined' && app.request) {
                        app.request.post({ data: { module: 'ProjectTask', action: 'SaveAjax', record: rid, field: 'projecttaskstatus', value: newStatus } }).then(function (err) {
                            if (err) return;
                            var progress = (newStatus === 'Completed') ? '100%' : '0%';
                            app.request.post({ data: { module: 'ProjectTask', action: 'SaveAjax', record: rid, field: 'projecttaskprogress', value: progress } }).then(function () {
                                var row = wrap.closest('.task-list-row');
                                if (row) {
                                    var trigIcon = wrap.querySelector('.subtask-status-trigger .subtask-status-icon');
                                    if (trigIcon) trigIcon.className = 'subtask-status-icon ' + getStatusIconClass(newStatus);
                                    var cb = row.querySelector('.task-checkbox');
                                    if (cb) cb.checked = (newStatus === 'Completed');
                                    row.querySelector('.task-title').classList.toggle('task-done', newStatus === 'Completed');
                                    wrap.querySelectorAll('.subtask-status-option').forEach(function (o) { o.classList.remove('subtask-status-option-selected'); if (o.getAttribute('data-value') === newStatus) o.classList.add('subtask-status-option-selected'); });
                                }
                                dropdown.classList.add('hidden');
                            });
                        });
                    }
                } else if (trigger && dropdown) {
                    board.querySelectorAll('.subtask-status-dropdown').forEach(function (d) { d.classList.add('hidden'); });
                    dropdown.classList.toggle('hidden');
                    trigger.setAttribute('aria-expanded', dropdown.classList.contains('hidden') ? 'false' : 'true');
                }
            } else {
                e.preventDefault();
                if (recordId) openSubtaskInOverlay(recordId);
            }
        });
        document.addEventListener('click', function (e) {
            if (e.target.closest && e.target.closest('.subtask-status-wrap')) return;
            board.querySelectorAll('.subtask-status-dropdown').forEach(function (d) { d.classList.add('hidden'); });
            board.querySelectorAll('.subtask-status-trigger').forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            panel.classList.add('hidden');
            updateTaskPanelUrl('', null);
        });
    }
    var cancelBtn = board.querySelector('.detail-cancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            panel.classList.add('hidden');
            updateTaskPanelUrl('', null);
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            if (!currentTask || typeof app === 'undefined' || !app.request) return;
            var progressVal = progressEl ? progressEl.value : '';
            if (progressVal !== '' && progressVal.toString().indexOf('%') === -1) {
                progressVal = progressVal + '%';
            }
            var payload = {
                module: 'ProjectTask',
                action: 'SaveTask',
                record: currentTask.recordid,
                projecttaskname: currentTask.name || '',
                projectid: currentTask.projectid || '',
                startdate: startEl ? startEl.value : '',
                enddate: endEl ? endEl.value : '',
                projecttaskstatus: statusSelect ? statusSelect.value : '',
                projecttaskprogress: progressVal,
                assigned_user_id: ownerSelect ? ownerSelect.value : '',
                description: descEl ? descEl.value : ''
            };
            app.request.post({ data: payload }).then(function (err) {
                if (err) return;
                if (statusEl && statusSelect) statusEl.textContent = statusSelect.value || '--';
                if (currentTask) {
                    currentTask.startdate = payload.startdate;
                    currentTask.enddate = payload.enddate;
                    currentTask.projecttaskstatus = payload.projecttaskstatus;
                    currentTask.projecttaskprogress = payload.projecttaskprogress;
                    currentTask.progress = payload.projecttaskprogress;
                    currentTask.smownerid = payload.assigned_user_id;
                    currentTask.description = payload.description;
                }
                if (app.helper && app.helper.showSuccessNotification) {
                    app.helper.showSuccessNotification({ message: 'Task updated.' });
                }
                panel.classList.add('hidden');
                updateTaskPanelUrl('', null);
            });
        });
    }

    if (commentEmojiBtn && commentEmojiPicker) {
        commentEmojiBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (commentEmojiPicker.classList.contains('hidden')) {
                ensureEmojiPickerContent();
                commentEmojiPicker.classList.remove('hidden');
            } else {
                commentEmojiPicker.classList.add('hidden');
            }
        });
    }
    board.addEventListener('click', function(e) {
        if (e.target.closest('.task-comment-upload-btn')) {
            e.preventDefault();
            var inp = board.querySelector('.task-comment-file-input');
            if (inp) inp.click();
        }
    });
    board.addEventListener('change', function(e) {
        if (e.target.classList && e.target.classList.contains('task-comment-file-input')) {
            var file = e.target.files && e.target.files[0];
            commentPendingFile = file || null;
            var fn = board.querySelector('.task-comment-file-name');
            var name = file ? file.name : '';
            if (fn) { fn.textContent = name; fn.classList.toggle('hidden', !name); }
        }
    });
    document.addEventListener('click', function(e) {
        if (e.target.closest('.task-comment-emoji-btn') || e.target.closest('.task-comment-emoji-picker')) return;
        if (commentEmojiPicker) commentEmojiPicker.classList.add('hidden');
    });
    board.addEventListener('click', function (e) {
        if (!e.target.closest('.task-comment-add')) return;
        e.preventDefault();
        if (!currentTask) return;
            var text = (commentInput ? commentInput.value : '').trim();
            var file = commentPendingFile || (commentFileInput && commentFileInput.files && commentFileInput.files[0]);
            if (!text && !file) return;
            if (commentInput) commentInput.value = '';
            commentPendingFile = null;
            if (!file) {
                if (commentFileInput) commentFileInput.value = '';
                if (commentFileName) { commentFileName.textContent = ''; commentFileName.classList.add('hidden'); }
            }
            var taskId = currentTask.recordid;
            function onSuccess(hadFile) {
                if (hadFile) {
                    if (commentFileInput) commentFileInput.value = '';
                    if (commentFileName) { commentFileName.textContent = ''; commentFileName.classList.add('hidden'); }
                    setTimeout(function () {
                        loadTaskComments(taskId, true);
                    }, 800);
                } else {
                    loadTaskComments(taskId);
                }
            }
            if (file) {
                var formData = new FormData();
                formData.append('module', 'ModComments');
                formData.append('action', 'SaveAjax');
                formData.append('commentcontent', text || ' ');
                formData.append('related_to', taskId);
                formData.append('filename', file, file.name || 'file');
                var jq = (typeof jQuery !== 'undefined') ? jQuery : (typeof $ !== 'undefined') ? $ : null;
                if (jq && jq.ajax) {
                    jq.ajax({
                        url: 'index.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json'
                    }).done(function (data) {
                        if (data && data.success === false && data.error) return;
                        onSuccess(true);
                    }).fail(function () { onSuccess(true); });
                } else if (typeof app !== 'undefined' && app.request && app.request.post) {
                    app.request.post({ data: formData, processData: false, contentType: false }).then(function (err) {
                        if (err) return;
                        onSuccess(true);
                    });
                } else {
                    onSuccess(true);
                }
            } else {
                if (typeof app !== 'undefined' && app.request && app.request.post) {
                    app.request.post({
                        data: { module: 'ModComments', action: 'SaveAjax', commentcontent: text, related_to: taskId }
                    }).then(function (err) {
                        if (err) return;
                        onSuccess(false);
                    });
                } else {
                    var jq = (typeof jQuery !== 'undefined') ? jQuery : (typeof $ !== 'undefined') ? $ : null;
                    if (jq && jq.ajax) {
                        jq.ajax({
                            url: 'index.php',
                            type: 'POST',
                            data: { module: 'ModComments', action: 'SaveAjax', commentcontent: text, related_to: taskId },
                            dataType: 'json'
                        }).done(function (data) {
                            if (data && data.success === false && data.error) return;
                            onSuccess(false);
                        });
                    }
                }
            }
    });
    /* Drag and drop tasks between columns */
    var statusMap = { 'Backlog': 'Open', 'In Progress': 'In Progress', 'Completed': 'Completed' };
    var draggedCard = null;
    var columns = board.querySelectorAll('.board-column');

    board.querySelectorAll('.board-card').forEach(function (card) {
        card.addEventListener('dragstart', function (e) {
            draggedCard = card;
            e.dataTransfer.setData('text/plain', card.getAttribute('data-task') || '{}');
            e.dataTransfer.effectAllowed = 'move';
            card.classList.add('dragging');
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('dragging');
            board.querySelectorAll('.board-column').forEach(function (c) { c.classList.remove('drop-over'); });
            draggedCard = null;
        });
    });

    columns.forEach(function (col) {
        var cardsContainer = col.querySelector('.board-cards');
        if (!cardsContainer) return;
        col.addEventListener('dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            col.classList.add('drop-over');
        });
        col.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'move';
        });
        col.addEventListener('dragleave', function (e) {
            if (!col.contains(e.relatedTarget)) col.classList.remove('drop-over');
        });
        col.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            col.classList.remove('drop-over');
            if (!draggedCard) return;
            var targetStatus = col.getAttribute('data-status');
            var newStatus = cardsContainer.getAttribute('data-status-value') || statusMap[targetStatus] || 'Open';
            var taskData = {};
            try {
                taskData = JSON.parse(draggedCard.getAttribute('data-task') || '{}');
            } catch (_) {}
            if (!taskData.recordid) return;
            cardsContainer.appendChild(draggedCard);
            taskData.projecttaskstatus = newStatus;
            draggedCard.setAttribute('data-task', JSON.stringify(taskData));
            updateColumnCounts();
            if (typeof app !== 'undefined' && app.request) {
                app.request.post({
                    data: {
                        module: 'ProjectTask',
                        action: 'SaveTask',
                        record: taskData.recordid,
                        projecttaskname: taskData.name || '',
                        projectid: taskData.projectid || '',
                        startdate: taskData.startdate || '',
                        enddate: taskData.enddate || '',
                        projecttaskstatus: newStatus,
                        projecttaskprogress: (taskData.progress || '').toString().replace('%', '') || '',
                        assigned_user_id: taskData.smownerid || '',
                        description: taskData.description || ''
                    }
                }).then(function (err) {
                    if (!err && app.helper && app.helper.showSuccessNotification) {
                        app.helper.showSuccessNotification({ message: 'Task moved.' });
                    }
                });
            }
        });
    });

    function updateColumnCounts() {
        board.querySelectorAll('.board-column').forEach(function (col) {
            var cards = col.querySelector('.board-cards');
            var count = cards ? cards.querySelectorAll('.board-card').length : 0;
            var badge = col.querySelector('.status-count');
            if (badge) badge.textContent = count;
        });
    }

    (function openPanelFromHash() {
        var hash = (window.location.hash || '').replace(/^#/, '');
        var m = hash.match(/^(task|subtask)=(\d+)$/);
        if (!m || typeof app === 'undefined' || !app.request) return;
        var id = m[2];
        parentStack = [];
        app.request.post({ data: { module: 'ProjectTask', action: 'GetTaskDetail', record: id } }).then(function (err, data) {
            if (err) return;
            var res = (data && data.result) ? data.result : (data || {});
            var task = res.task;
            if (!task) return;
            fillTaskDetailPanel(task);
            panel.classList.remove('hidden');
            loadTaskComments(task.recordid);
            loadBoardSubtasks(task.recordid);
            switchTaskDetailTab('comments');
        });
    })();
})();
</script>
{/strip}
