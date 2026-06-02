<?php
/**
 * Download / inline preview for Campaign attachments (vtiger_attachments).
 */

class Campaigns_DownloadCampaignFile_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$fileId = (int) $request->get('fileid');
		require_once 'modules/Campaigns/models/CampaignFilesHelper.php';
		$row = Campaigns_CampaignFiles_Helper::getAttachmentForDownload($fileId);
		if (empty($row) || empty($row['campaignid'])) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		$campaignId = (int) $row['campaignid'];
		if (!Users_Privileges_Model::isPermitted('Campaigns', 'DetailView', $campaignId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
		return true;
	}

	public function process(Vtiger_Request $request) {
		$fileId = (int) $request->get('fileid');
		$inline = (int) $request->get('inline') === 1;

		require_once 'modules/Campaigns/models/CampaignFilesHelper.php';
		$row = Campaigns_CampaignFiles_Helper::getAttachmentForDownload($fileId);
		if (empty($row)) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND', 'Vtiger'));
		}
		$campaignId = (int) $row['campaignid'];
		if ($campaignId <= 0 || !Users_Privileges_Model::isPermitted('Campaigns', 'DetailView', $campaignId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$path = Campaigns_CampaignFiles_Helper::getAttachmentFilesystemPath($row);
		if (empty($path) || !is_file($path) || !is_readable($path)) {
			throw new AppException(vtranslate('LBL_RECORD_NOT_FOUND', 'Vtiger'));
		}

		$orig = $row['name'];
		$mime = !empty($row['type']) ? $row['type'] : 'application/octet-stream';
		$isImage = Campaigns_CampaignFiles_Helper::isImageFilename($orig);

		if ($inline && $isImage) {
			header('Content-Type: ' . $mime);
			header('Content-Disposition: inline; filename="' . addslashes(basename($orig)) . '"');
		} else {
			header('Content-Type: ' . $mime);
			header('Content-Disposition: attachment; filename="' . addslashes(basename($orig)) . '"');
		}
		header('Content-Length: ' . filesize($path));
		header('X-Content-Type-Options: nosniff');
		readfile($path);
		exit;
	}
}
