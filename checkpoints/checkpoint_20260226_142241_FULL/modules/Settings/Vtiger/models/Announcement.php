<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Vtiger_Announcement_Model extends Vtiger_Base_Model {

    const tableName  = 'vtiger_announcement';

    /** Ensure announcement table has id as PK (multiple announcements per user). */
    protected static function ensureAnnouncementIdColumn() {
        static $done = false;
        if ($done) return;
        $db = PearDatabase::getInstance();
        $res = $db->pquery("SHOW COLUMNS FROM ".self::tableName." LIKE 'id'", array());
        if ($db->num_rows($res) == 0) {
            $db->pquery("ALTER TABLE ".self::tableName." DROP PRIMARY KEY", array());
            $db->pquery("ALTER TABLE ".self::tableName." ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST", array());
        }
        $done = true;
    }

    /** Ensure subscriber columns exist (for existing installs). */
    protected static function ensureSubscriberColumns() {
        static $done = false;
        if ($done) {
            return;
        }
        $db = PearDatabase::getInstance();
        $res = $db->pquery("SHOW COLUMNS FROM ".self::tableName." LIKE 'subscriber_ids'", array());
        if ($db->num_rows($res) == 0) {
            $db->pquery("ALTER TABLE ".self::tableName." ADD COLUMN subscriber_ids TEXT NULL AFTER time", array());
        }
        $res = $db->pquery("SHOW COLUMNS FROM ".self::tableName." LIKE 'subscriber_group_ids'", array());
        if ($db->num_rows($res) == 0) {
            $db->pquery("ALTER TABLE ".self::tableName." ADD COLUMN subscriber_group_ids TEXT NULL AFTER subscriber_ids", array());
        }
        $done = true;
    }

    protected static function normalizeIds($value) {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            return implode(',', array_map('intval', array_filter($value)));
        }
        $ids = array_map('intval', array_filter(explode(',', (string)$value)));
        return implode(',', $ids);
    }

    public function save() {
        self::ensureAnnouncementIdColumn();
        self::ensureSubscriberColumns();
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $currentDate = date('Y-m-d H:i:s');
        $title = $this->get('title');
        if ($title === null || $title === '') {
            $title = 'announcement';
        }
        $subscriberIds = self::normalizeIds($this->get('subscriber_ids'));
        $subscriberGroupIds = self::normalizeIds($this->get('subscriber_group_ids'));
        $existingId = $this->get('id');
        if (!empty($existingId)) {
            $query = 'UPDATE '.self::tableName.' SET announcement=?,title=?,time=?,subscriber_ids=?,subscriber_group_ids=? WHERE id=?';
            $params = array($this->get('announcement'), $title, $db->formatDate($currentDate, true), $subscriberIds, $subscriberGroupIds, $existingId);
            $db->pquery($query, $params);
        } else {
            $query = 'INSERT INTO '.self::tableName.' (creatorid,announcement,title,time,subscriber_ids,subscriber_group_ids) VALUES(?,?,?,?,?,?)';
            $params = array($currentUser->getId(), $this->get('announcement'), $title, $db->formatDate($currentDate, true), $subscriberIds, $subscriberGroupIds);
            $db->pquery($query, $params);
        }
    }
    
    public static function getInstanceByCreator(Users_Record_Model $user) {
        self::ensureAnnouncementIdColumn();
        $db = PearDatabase::getInstance();
        $query = 'SELECT * FROM '.self::tableName.' WHERE creatorid=? ORDER BY time DESC LIMIT 1';
        $result = $db->pquery($query, array($user->getId()));
        $instance = new self();
        if ($db->num_rows($result) > 0) {
            $row = $db->query_result_rowdata($result, 0);
            $instance->setData($row);
        }
        return $instance;
    }

    /**
     * Get announcements for display (e.g. Main Page). Filter by current user: visible if creator or (no users/groups set) or user in list or in selected group.
     */
    public static function getAllForDisplay($limit = 50, $currentUserId = null) {
        self::ensureAnnouncementIdColumn();
        self::ensureSubscriberColumns();
        $list = array();
        $db = PearDatabase::getInstance();
        $sql = 'SELECT a.id, a.creatorid, a.announcement, a.title, a.time, a.subscriber_ids, a.subscriber_group_ids FROM '.self::tableName.' a
                INNER JOIN vtiger_users u ON u.id = a.creatorid AND u.deleted = 0 AND u.status = ?
                WHERE TRIM(COALESCE(a.announcement,\'\')) != \'\' OR TRIM(COALESCE(a.title,\'\')) != \'\'
                ORDER BY a.time DESC LIMIT ?';
        $res = $db->pquery($sql, array('Active', (int)$limit * 3));
        $currentUserId = $currentUserId !== null ? (int)$currentUserId : null;
        $currentUserGroupIds = array();
        if ($currentUserId !== null && class_exists('Users_Record_Model')) {
            $grp = Users_Record_Model::getUserGroups($currentUserId);
            $currentUserGroupIds = is_array($grp) ? array_map('intval', $grp) : array();
        }
        $count = 0;
        while ($row = $db->fetchByAssoc($res)) {
            if ($count >= $limit) {
                break;
            }
            $subscriberIds = isset($row['subscriber_ids']) ? trim($row['subscriber_ids']) : '';
            $subscriberGroupIds = isset($row['subscriber_group_ids']) ? trim($row['subscriber_group_ids']) : '';
            $userIds = $subscriberIds !== '' ? array_map('intval', array_filter(explode(',', $subscriberIds))) : array();
            $groupIds = $subscriberGroupIds !== '' ? array_map('intval', array_filter(explode(',', $subscriberGroupIds))) : array();

            if ($currentUserId !== null) {
                $creatorId = (int)$row['creatorid'];
                $visible = ($creatorId === $currentUserId)
                    || (empty($userIds) && empty($groupIds))
                    || in_array($currentUserId, $userIds, true)
                    || (!empty($groupIds) && !empty(array_intersect($currentUserGroupIds, $groupIds)));
                if (!$visible) {
                    continue;
                }
            }

            $assignedToUsers = array();
            foreach ($userIds as $uid) {
                if ($uid) {
                    $assignedToUsers[] = trim(getUserFullName($uid));
                }
            }
            $assignedToGroups = array();
            foreach ($groupIds as $gid) {
                if ($gid) {
                    $assignedToGroups[] = trim(self::getGroupName($gid));
                }
            }
            $list[] = array(
                'id' => isset($row['id']) ? $row['id'] : null,
                'creatorid' => $row['creatorid'],
                'title' => $row['title'] ?: '',
                'announcement' => $row['announcement'] ?: '',
                'creatorName' => trim(getUserFullName($row['creatorid'])),
                'time' => $row['time'],
                'timeAgo' => self::timeAgo($row['time']),
                'assignedToUsersStr' => implode(', ', $assignedToUsers),
                'assignedToGroupsStr' => implode(', ', $assignedToGroups),
            );
            $count++;
        }
        return $list;
    }

    /** Return relative time e.g. 1m, 2h, 3d for display. */
    public static function timeAgo($datetime) {
        if (empty($datetime)) return '';
        $ts = is_numeric($datetime) ? $datetime : strtotime($datetime);
        if (!$ts) return $datetime;
        $diff = time() - $ts;
        if ($diff < 60) return '1m';
        if ($diff < 3600) return floor($diff / 60) . 'm';
        if ($diff < 86400) return floor($diff / 3600) . 'h';
        if ($diff < 2592000) return floor($diff / 86400) . 'd';
        if ($diff < 31536000) return floor($diff / 2592000) . 'mo';
        return floor($diff / 31536000) . 'y';
    }

    /** Get one announcement by id for detail view. */
    public static function getById($id, $currentUserId = null) {
        self::ensureAnnouncementIdColumn();
        self::ensureSubscriberColumns();
        $db = PearDatabase::getInstance();
        $id = (int)$id;
        $currentUserId = $currentUserId !== null ? (int)$currentUserId : null;
        $res = $db->pquery('SELECT a.id, a.creatorid, a.announcement, a.title, a.time, a.subscriber_ids, a.subscriber_group_ids FROM '.self::tableName.' a
            INNER JOIN vtiger_users u ON u.id = a.creatorid AND u.deleted = 0 AND u.status = ?
            WHERE a.id = ?', array('Active', $id));
        if ($db->num_rows($res) == 0) return null;
        $row = $db->fetchByAssoc($res);
        if ($currentUserId !== null) {
            $userIds = (isset($row['subscriber_ids']) && $row['subscriber_ids'] !== '') ? array_map('intval', array_filter(explode(',', $row['subscriber_ids']))) : array();
            $groupIds = (isset($row['subscriber_group_ids']) && $row['subscriber_group_ids'] !== '') ? array_map('intval', array_filter(explode(',', $row['subscriber_group_ids']))) : array();
            $creatorId = (int)$row['creatorid'];
            $visible = ($creatorId === $currentUserId) || (empty($userIds) && empty($groupIds))
                || in_array($currentUserId, $userIds, true);
            if (!$visible && !empty($groupIds) && class_exists('Users_Record_Model')) {
                $grp = Users_Record_Model::getUserGroups($currentUserId);
                $visible = !empty(array_intersect(is_array($grp) ? array_map('intval', $grp) : array(), $groupIds));
            }
            if (!$visible) return null;
        }
        $userIds = (isset($row['subscriber_ids']) && $row['subscriber_ids'] !== '') ? array_map('intval', array_filter(explode(',', $row['subscriber_ids']))) : array();
        $groupIds = (isset($row['subscriber_group_ids']) && $row['subscriber_group_ids'] !== '') ? array_map('intval', array_filter(explode(',', $row['subscriber_group_ids']))) : array();
        $assignedToUsers = array(); foreach ($userIds as $uid) { if ($uid) $assignedToUsers[] = trim(getUserFullName($uid)); }
        $assignedToGroups = array(); foreach ($groupIds as $gid) { if ($gid) $assignedToGroups[] = trim(self::getGroupName($gid)); }
        $subscribersList = array();
        foreach ($userIds as $uid) { if ($uid) $subscribersList[] = array('type' => 'user', 'id' => $uid, 'name' => trim(getUserFullName($uid))); }
        foreach ($groupIds as $gid) { if ($gid) $subscribersList[] = array('type' => 'group', 'id' => $gid, 'name' => trim(self::getGroupName($gid))); }
        return array(
            'id' => $row['id'],
            'creatorid' => $row['creatorid'],
            'title' => $row['title'] ?: '',
            'announcement' => $row['announcement'] ?: '',
            'creatorName' => trim(getUserFullName($row['creatorid'])),
            'time' => $row['time'],
            'timeAgo' => self::timeAgo($row['time']),
            'assignedToUsersStr' => implode(', ', $assignedToUsers),
            'assignedToGroupsStr' => implode(', ', $assignedToGroups),
            'subscriber_ids' => $row['subscriber_ids'],
            'subscriber_group_ids' => $row['subscriber_group_ids'],
            'subscribers' => $subscribersList,
        );
    }

    public static function deleteById($id, $currentUserId) {
        self::ensureAnnouncementIdColumn();
        $db = PearDatabase::getInstance();
        $id = (int)$id;
        $r = $db->pquery('SELECT creatorid FROM '.self::tableName.' WHERE id=?', array($id));
        if ($db->num_rows($r) == 0) return false;
        $creatorId = $db->query_result($r, 0, 'creatorid');
        if ($creatorId != $currentUserId) return false;
        $db->pquery('DELETE FROM '.self::commentsTable.' WHERE announcement_id=?', array($id));
        $db->pquery('DELETE FROM '.self::tableName.' WHERE id=?', array($id));
        return true;
    }

    public static function updateSubscribers($id, $currentUserId, $subscriberIds, $subscriberGroupIds) {
        self::ensureAnnouncementIdColumn();
        self::ensureSubscriberColumns();
        $db = PearDatabase::getInstance();
        $id = (int)$id;
        $r = $db->pquery('SELECT creatorid FROM '.self::tableName.' WHERE id=?', array($id));
        if ($db->num_rows($r) == 0) return false;
        if ($db->query_result($r, 0, 'creatorid') != $currentUserId) return false;
        $subscriberIds = self::normalizeIds($subscriberIds);
        $subscriberGroupIds = self::normalizeIds($subscriberGroupIds);
        $db->pquery('UPDATE '.self::tableName.' SET subscriber_ids=?, subscriber_group_ids=? WHERE id=?', array($subscriberIds, $subscriberGroupIds, $id));
        return true;
    }

    protected static function getGroupName($groupId) {
        $db = PearDatabase::getInstance();
        $r = $db->pquery('SELECT groupname FROM vtiger_groups WHERE groupid=?', array((int)$groupId));
        return ($r && $db->num_rows($r) > 0) ? $db->query_result($r, 0, 'groupname') : ('Group #'.$groupId);
    }

    const commentsTable = 'vtiger_announcement_comments';

    protected static function ensureCommentsTable() {
        static $done = false;
        if ($done) return;
        $db = PearDatabase::getInstance();
        $r = $db->pquery("SHOW TABLES LIKE '".self::commentsTable."'", array());
        if ($db->num_rows($r) == 0) {
            $db->pquery("CREATE TABLE ".self::commentsTable." (
                id INT AUTO_INCREMENT PRIMARY KEY,
                announcement_id INT NOT NULL,
                userid INT NOT NULL,
                comment_text TEXT,
                filename VARCHAR(255) NULL,
                createdtime DATETIME,
                KEY idx_announcement (announcement_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8", array());
        } else {
            $col = $db->pquery("SHOW COLUMNS FROM ".self::commentsTable." LIKE 'announcement_id'", array());
            if ($db->num_rows($col) == 0) {
                $db->pquery("ALTER TABLE ".self::commentsTable." ADD COLUMN announcement_id INT NULL AFTER id", array());
                $db->pquery("UPDATE ".self::commentsTable." c INNER JOIN ".self::tableName." a ON a.creatorid = c.announcement_creatorid SET c.announcement_id = a.id WHERE c.announcement_creatorid IS NOT NULL", array());
            }
            $colFile = $db->pquery("SHOW COLUMNS FROM ".self::commentsTable." LIKE 'filename'", array());
            if ($db->num_rows($colFile) == 0) {
                $db->pquery("ALTER TABLE ".self::commentsTable." ADD COLUMN filename VARCHAR(255) NULL AFTER comment_text", array());
            }
        }
        $done = true;
    }

    /**
     * Upload a file for announcement comment. Creates vtiger_crmentity + vtiger_attachments.
     * @param array $file $_FILES['filename']
     * @return int|null attachment id or null on failure
     */
    public static function uploadCommentFile($file) {
        global $adb, $current_user, $upload_badext;
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
        $fileName = isset($file['original_name']) ? $file['original_name'] : $file['name'];
        $fileName = sanitizeUploadFileName($fileName, $upload_badext);
        $fileName = ltrim(basename(' ' . $fileName));
        $filetype = isset($file['type']) ? $file['type'] : 'application/octet-stream';
        $filetmp = $file['tmp_name'];
        $uploadPath = decideFilePath();
        $encryptName = Vtiger_Util_Helper::getEncryptedFileName($fileName);
        $currentId = $adb->getUniqueID('vtiger_crmentity');
        if (!copy($filetmp, $uploadPath . $currentId . '_' . $encryptName)) return null;
        $dateVar = date('Y-m-d H:i:s');
        $adb->pquery('INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) VALUES (?,?,?,?,?,?,?)',
            array($currentId, $current_user->id, $current_user->id, 'ModComments Attachment', '', $adb->formatDate($dateVar, true), $adb->formatDate($dateVar, true)));
        $adb->pquery('INSERT INTO vtiger_attachments (attachmentsid,name,description,type,path,storedname) VALUES (?,?,?,?,?,?)',
            array($currentId, $fileName, '', $filetype, $uploadPath, $encryptName));
        return (int)$currentId;
    }

    public static function getComments($announcementId) {
        self::ensureCommentsTable();
        $db = PearDatabase::getInstance();
        $list = array();
        $res = $db->pquery('SELECT id, userid, comment_text, createdtime, filename FROM '.self::commentsTable.'
            WHERE announcement_id = ? ORDER BY createdtime ASC', array((int)$announcementId));
        while ($row = $db->fetchByAssoc($res)) {
            $attachments = array();
            $filenameField = isset($row['filename']) ? trim($row['filename']) : '';
            if ($filenameField) {
                $ids = array_filter(array_map('intval', preg_split('/\s*,\s*/', $filenameField, -1, PREG_SPLIT_NO_EMPTY)));
                foreach ($ids as $aid) {
                    $r = $db->pquery('SELECT name FROM vtiger_attachments WHERE attachmentsid=?', array($aid));
                    if ($db->num_rows($r) > 0) {
                        $name = $db->query_result($r, 0, 'name');
                        $url = 'index.php?module=Home&action=DownloadAnnouncementCommentFile&record=' . (int)$row['id'] . '&fileid=' . $aid;
                        $attachments[] = array('url' => $url, 'name' => $name ?: 'file');
                    }
                }
            }
            $list[] = array(
                'id' => $row['id'],
                'userid' => $row['userid'],
                'userName' => trim(getUserFullName($row['userid'])),
                'comment_text' => $row['comment_text'] ?: '',
                'createdtime' => $row['createdtime'],
                'timeAgo' => self::timeAgo($row['createdtime']),
                'attachments' => $attachments,
            );
        }
        return $list;
    }

    public static function addComment($announcementId, $userId, $commentText, $attachmentId = null) {
        self::ensureCommentsTable();
        $db = PearDatabase::getInstance();
        $filenameVal = $attachmentId ? (string)(int)$attachmentId : null;
        $db->pquery('INSERT INTO '.self::commentsTable.' (announcement_id, userid, comment_text, filename, createdtime) VALUES (?,?,?,?,?)',
            array((int)$announcementId, (int)$userId, $commentText, $filenameVal, date('Y-m-d H:i:s')));
        return $db->getLastInsertID();
    }
}