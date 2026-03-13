<?php
/*+***********************************************************************************
 * Simple password gate for "Internal Orders" list view in Potentials.
 * - URL: index.php?module=Potentials&view=InternalOrderAuth&viewname=<cvid>
 * - Password: internal@123
 * - On success: set $_SESSION['internal_order_verified'] = true and redirect back.
 *************************************************************************************/

class Potentials_InternalOrderAuth_View extends Vtiger_Index_View {

    protected $correctPassword = 'internal@123';

    public function process(Vtiger_Request $request) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $moduleName = $request->getModule();
        $viewId     = $request->get('viewname');
        $error      = '';

        if (strtoupper($request->getRequestMethod()) === 'POST') {
            $password = (string)$request->get('password');

            if ($password === $this->correctPassword) {
                $_SESSION['internal_order_verified'] = true;

                // Redirect back to List with same viewname (Internal Orders)
                $redirectUrl = 'index.php?module=' . urlencode($moduleName)
                    . '&view=List';
                if (!empty($viewId)) {
                    $redirectUrl .= '&viewname=' . urlencode($viewId);
                }

                if (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        }

        if (ob_get_level() === 0) {
            ob_start();
        }

        echo '<!DOCTYPE html><html><head>';
        echo '<meta charset="UTF-8">';
        echo '<title>Internal Orders Authentication</title>';
        echo '<link rel="stylesheet" href="layouts/v7/lib/bootstrap/css/bootstrap.min.css" />';
        echo '</head><body class="container" style="margin-top:50px;max-width:480px;">';
        echo '<h3>Internal Orders</h3>';
        echo '<p>Please enter password to view Internal Orders.</p>';

        if (!empty($error)) {
            echo '<div class="alert alert-danger" role="alert">' . htmlentities($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        echo '<form method="post" action="">';
        echo '<div class="form-group">';
        echo '<label for="password">Password</label>';
        echo '<input type="password" name="password" id="password" class="form-control" autofocus />';
        echo '</div>';

        if (!empty($viewId)) {
            echo '<input type="hidden" name="viewname" value="' . htmlspecialchars($viewId, ENT_QUOTES, 'UTF-8') . '"/>';
        }

        echo '<button type="submit" class="btn btn-primary">Submit</button> ';
        echo '<a href="index.php?module=' . urlencode($moduleName) . '&view=List" class="btn btn-default">Cancel</a>';
        echo '</form>';

        echo '</body></html>';
    }
}

