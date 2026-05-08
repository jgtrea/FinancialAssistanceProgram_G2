<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    protected $helpers = ['url', 'form', 'html', 'asset'];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    protected function writeAuditLog(string $action, string $description, ?int $voucherId = null): void
    {
        try {
            $userAgent = $this->request->getUserAgent();

            (new AuditLogModel())->insert([
                'user_id' => session()->get('user_id') ?? 1,
                'voucher_id' => $voucherId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $userAgent ? $userAgent->getAgentString() : '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Audit log failed: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
