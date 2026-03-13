<?php
/*+***********************************************************************************
 * Potentials List view override to protect "Internal Orders" with password.
 * - Không sửa core.
 * - Nếu filter hiện tại là 'Internal Orders' và chưa verify password,
 *   redirect sang view=InternalOrderAuth.
 *************************************************************************************/

class Potentials_List_View extends Vtiger_List_View {

    /**
     * Check before rendering list.
     */
    public function preProcess(Vtiger_Request $request, $display = true) {
        if ($this->shouldBlockInternalOrders($request)) {
            $this->redirectToInternalOrderAuth($request);
            // Do not call parent::preProcess; we are redirecting.
            exit;
        }

        parent::preProcess($request, $display);
    }

    /**
     * Also guard process in case some flows bypass preProcess.
     */
    public function process(Vtiger_Request $request) {
        if ($this->shouldBlockInternalOrders($request)) {
            $this->redirectToInternalOrderAuth($request);
            exit;
        }
        parent::process($request);
    }

    /**
     * Determine if current list view is "Internal Orders" and password not verified.
     */
    protected function shouldBlockInternalOrders(Vtiger_Request $request) {
        // Only block standard list view (avoid popups etc.)
        if (strtolower($request->get('view')) !== 'list') {
            return false;
        }

        $moduleName = $request->getModule();
        if ($moduleName !== 'Potentials') {
            return false;
        }

        // Session flag (reset on logout by Vtiger)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (!empty($_SESSION['internal_order_verified']) && $_SESSION['internal_order_verified'] === true) {
            return false;
        }

        // Find cvid of 'Internal Orders'
        $potentialsModule = Vtiger_Module::getInstance('Potentials');
        $internalFilter = Vtiger_Filter::getInstance('Internal Orders', $potentialsModule);
        if (!$internalFilter) {
            // Filter not defined; nothing to protect.
            return false;
        }
        $internalCvid = $internalFilter->id;

        // Current filter (viewname)
        $currentViewId = $request->get('viewname');
        if (empty($currentViewId)) {
            // When no viewname, vtiger uses default filter; ta chỉ chặn khi
            // user chọn rõ ràng Internal Orders.
            return false;
        }

        if ((string)$currentViewId === (string)$internalCvid) {
            return true;
        }
        return false;
    }

    /**
     * Redirect user to password form for Internal Orders.
     */
    protected function redirectToInternalOrderAuth(Vtiger_Request $request) {
        $moduleName = $request->getModule();
        $viewId     = $request->get('viewname');

        $url = 'index.php?module=' . urlencode($moduleName)
            . '&view=InternalOrderAuth';
        if (!empty($viewId)) {
            $url .= '&viewname=' . urlencode($viewId);
        }

        if (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Location: ' . $url);
    }
}

