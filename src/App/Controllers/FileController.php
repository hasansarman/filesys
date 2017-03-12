<?php namespace Crip\Filesys\App\Controllers;

use Crip\Filesys\App\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class FileController
 * @package Crip\Filesys\App\Controllers
 */
class FileController extends BaseController
{
    /**
     * Upload file to server
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        if ($request->hasFile('file')) {
            // Configure manager path where file should be uploaded
            // and make sure that directory exists in file system
            $blob = $this->manager->parsePath($request->path);

            // Upload file to the server
            $this->manager->upload($blob, $request->file('file'));

            // If file is image, create all configured sizer for it
            if ($blob->file->isImage()) {
                $this->manager->resizeImage($blob);
                // Update file details after creating thumbs
                $blob->file->update();
            }

            // Return file public url to the uploaded file
            return $this->json(new File($blob));
        }

        return $this->json(['File not presented for upload.'], 422);
    }

    /**
     * Get file
     * @param Request $request
     * @param string $file Path to the requested file
     * @return JsonResponse|Response
     */
    public function show(Request $request, $file)
    {
        $blob = $this->manager->parsePath($file, $request->all());

        if ($blob->file->isDefined() && $this->manager->exists($blob)) {
            return new Response($this->manager->fileContent($blob), 200, [
                'Content-Type' => $blob->file->getMimeType()]);
        }

        return $this->json('File not found.', 404);
    }

    /**
     * Rename file
     * @param Request $request
     * @param string $file
     * @return JsonResponse
     */
    public function update(Request $request, $file)
    {
        if (empty($request->name)) {
            return $this->json('Name property is required.', 422);
        }

        $blob = $this->manager->parsePath($file);

        if (!$this->manager->exists($blob)) {
            return $this->json('File not found.', 404);
        }

        $this->manager->rename($blob, $request->name);

        return $this->json(new File($blob));
    }

    /**
     * Delete file
     * @param string $file
     * @return JsonResponse
     */
    public function destroy($file)
    {
        $blob = $this->manager->parsePath($file);

        if (!$this->manager->exists($blob)) {
            return $this->json('File not found.', 404);
        }

        $isRemoved = $this->manager->delete($blob);

        return $this->json($isRemoved, $isRemoved ? 200 : 500);
    }
}