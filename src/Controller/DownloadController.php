<?php declare(strict_types=1);

namespace Statistics\Controller;

use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;

/**
 * The download controller class.
 *
 * Count direct download of a file.
 *
 * @see \AccessResource\Controller\AccessResourceController
 */
class DownloadController extends AbstractActionController
{
    /**
     * @var string
     */
    protected $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Forward to action "file".
     *
     * @see self::filesAction()
     */
    public function indexAction()
    {
        $params = $this->params()->fromRoute();
        $params['action'] = 'file';
        return $this->forward()->dispatch('Statistics\Controller\Download', $params);
    }

    /**
     * Check file and prepare it to be sent.
     *
     * Unlike Omeka Classic, the current file is already logged in main event
     * "view.layout" (see main Module and HitAdapter::currentRequest()).
     * So, just send the file.
     *
     * @todo Manage other storage than local one.
     */
    public function fileAction()
    {
        $params = $this->params()->fromRoute();
        $storageType = $params['type'] ?? '';
        $filename = $params['filename'] ?? '';
        $filepath = sprintf('%s/%s/%s', $this->basePath, $storageType, $filename);
        $result = $this->sendFile($filepath);
        // Log the url even if the file is missing.
        $this->logCurrentUrl();
        if (empty($result)) {
            return $this->notFoundAction();
        }
        // Return response to avoid default view rendering and to manage events.
        // Since there is no more event in Omeka, the url is logged directly.
        return $result;
    }

    /**
     * Send file as stream. The file should exist.
     *
     * @todo Use Laminas stream response.
     */
    protected function sendFile(string $filepath): ?HttpResponse
    {
        // A security. Don't check the realpath to avoid issue on some configs.
        if (strpos($filepath, '../') !== false
            || !file_exists($filepath)
            || !is_readable($filepath)
        ) {
            return null;
        }

        $fileSize = filesize($filepath);
        if (!$fileSize) {
            return null;
        }

        $filename = pathinfo($filepath, PATHINFO_BASENAME);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mediaType = $finfo->file($filepath);
        $mediaType = \Omeka\File\TempFile::MEDIA_TYPE_ALIASES[$mediaType] ?? $mediaType;

        // Everything has been checked.
        $dispositionMode = 'inline';

        /** @var \Laminas\Http\PhpEnvironment\Response $response */
        $response = $this->getResponse();
        // Write headers.
        $response->getHeaders()
            ->addHeaderLine(sprintf('Content-Type: %s', $mediaType))
            ->addHeaderLine(sprintf('Content-Disposition: %s; filename="%s"', $dispositionMode, $filename))
            ->addHeaderLine(sprintf('Content-Length: %s', $fileSize))
            ->addHeaderLine('Content-Transfer-Encoding: binary')
            // Use this to open files directly.
            // Cache for 30 days.
            ->addHeaderLine('Cache-Control: private, max-age=2592000, post-check=2592000, pre-check=2592000')
            ->addHeaderLine(sprintf('Expires: %s', gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT'));

        // Send headers separately to handle large files.
        $response->sendHeaders();

        // Clears all active output buffers to avoid memory overflow.
        $response->setContent('');
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile($filepath);

        // TODO Fix issue with session. See readme of module XmlViewer.
        ini_set('display_errors', '0');

        // Return response to avoid default view rendering and to manage events.
        return $response;
    }
}
