<?php
/*+***********************************************************************************
 * Potentials List view override to protect "Internal Orders" with password.
 * - Không sửa core.
 * - Nếu filter hiện tại là 'Internal Orders' và chưa verify password,
 *   redirect sang view=InternalOrderAuth.
 *************************************************************************************/

class Potentials_List_View extends Vtiger_List_View {

    /**
     * Remove deprecated Project/Internal toggle injector.
     * The toggle is injected client-side by OrderCategoryFilter.js which may be registered
     * as a HEADERSCRIPT link in DB. We explicitly filter it out here so it never loads.
     */
    public function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);

        foreach ($headerScriptInstances as $key => $scriptModel) {
            try {
                $src = null;
                if (is_object($scriptModel) && method_exists($scriptModel, 'get')) {
                    $src = $scriptModel->get('src');
                    if (!$src) {
                        $src = $scriptModel->get('linkurl');
                    }
                }
                $src = (string)$src;
                if ($src !== '' && stripos($src, 'layouts/v7/modules/Potentials/resources/OrderCategoryFilter.js') !== false) {
                    unset($headerScriptInstances[$key]);
                }
            } catch (Exception $e) {
                // ignore filtering errors
            }
        }

        return $headerScriptInstances;
    }

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

